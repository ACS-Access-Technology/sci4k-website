#!/usr/bin/env bash
#
# Demarrage du conteneur : ce qui ne peut se faire qu'une fois la base joignable.
#
# La construction de l'image tourne sur une machine qui n'atteint pas la base de
# donnees ; les migrations doivent donc attendre le demarrage. C'est le seul
# endroit ou elles peuvent s'executer sans qu'on s'y connecte a la main.

set -euo pipefail

app=/app/app-laravel
cd "$app"

# L'utilisateur du site. Voir le Dockerfile : il possede storage/ et
# bootstrap/cache/, et rien d'autre.
utilisateur=www-data

# --- Sous root : rendre le volume, puis s'effacer ----------------------------
#
# Le conteneur demarre sous root pour UNE raison : le volume monte par la
# plateforme sur storage/app/public appartient a root, et www-data ne pourrait
# pas y ecrire. On le lui rend — seulement ce qui ne lui appartient pas deja,
# pour ne pas reparcourir des milliers de photos a chaque demarrage — puis le
# script se RELANCE sous www-data. Tout ce qui suit, migrations et serveur
# compris, tourne donc sans privileges.
#
# setpriv et non su : il remplace le processus au lieu d'en ouvrir un second
# sous root, ne demande pas de shell a l'utilisateur, et --no-new-privs
# interdit a tout programme lance ensuite de redevenir root, meme par un
# binaire setuid.
if [[ "$(id -u)" = "0" ]]; then
    find storage bootstrap/cache \! -user "$utilisateur" \
        -exec chown "$utilisateur:$utilisateur" {} + 2>/dev/null || true

    exec setpriv --reuid="$utilisateur" --regid="$utilisateur" --init-groups \
        --no-new-privs "$0" "$@"
fi

echo "== Demarrage de SCI4K (utilisateur $(id -un), uid $(id -u)) =="

# Un volume que root n'a pas pu rendre — lecture seule, systeme de fichiers
# distant qui refuse chown — laisserait le site demarrer, puis echouer a chaque
# televersement sans rien dire. Mieux vaut l'ecrire ici, dans les journaux.
for dossier in storage/app/public storage/logs storage/framework bootstrap/cache; do
    if [[ ! -w "$dossier" ]]; then
        echo "AVERTISSEMENT : $dossier n'est pas accessible en ecriture a $(id -un)." >&2
    fi
done

# APP_KEY manquante : l'application demarre mais ne dechiffre plus ni cookies ni
# sessions, et l'erreur qui s'ensuit n'a rien d'evident. Mieux vaut la dire ici.
if [[ -z "${APP_KEY:-}" ]]; then
    echo "ERREUR : APP_KEY est vide." >&2
    echo "  Generer une cle avec « php artisan key:generate --show » sur un poste" >&2
    echo "  de developpement, puis la poser en variable d'environnement." >&2
    exit 1
fi

if [[ "${APP_DEBUG:-false}" = "true" ]] && [[ "${APP_ENV:-}" = "production" ]]; then
    echo "AVERTISSEMENT : APP_DEBUG=true avec APP_ENV=production." >&2
    echo "  La page d'erreur exposera la trace d'execution, les requetes SQL et" >&2
    echo "  les variables d'environnement au premier visiteur venu." >&2
fi

# Le lien des fichiers televerses. Sans lui, toute image ajoutee depuis le
# backoffice est ecrite mais ne s'affiche jamais. L'image le pose a la
# construction (www-data ne peut plus ecrire dans public/) ; cette ligne ne
# sert qu'a une image construite autrement.
if [[ ! -L public/storage ]]; then
    php artisan storage:link 2>/dev/null || echo "AVERTISSEMENT : lien public/storage absent." >&2
fi

# Les migrations tournent AU DEMARRAGE, faute de pouvoir tourner ailleurs.
#
# C'est acceptable pour un environnement d'essai, et discutable en production :
# deux instances qui demarrent ensemble les lanceraient en meme temps. Laravel
# pose un verrou, mais le jour ou ce site tournera sur plusieurs instances, il
# faudra les sortir d'ici.
if [[ "${MIGRER_AU_DEMARRAGE:-true}" = "true" ]]; then
    echo "== Migrations =="
    php artisan migrate --force --no-interaction
fi

# La version de MySQL, comparee a celle que le projet teste. AVERTISSEMENT
# seulement : un ecart ne doit pas empecher le site de demarrer, mais il doit
# se lire dans les journaux — celui qu'a releve l'audit (CI en 8.0, production
# en 9.4) n'etait ecrit nulle part.
echo "== Base de donnees =="
php artisan base:verifier-version || echo "AVERTISSEMENT : version de MySQL non supportee (voir ci-dessus)." >&2

# Les caches sont poses ici et non a la construction : la configuration depend
# de variables que la plateforme n'injecte qu'a l'execution.
echo "== Caches =="
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Le planificateur, a cote du serveur.
#
# Sur un hebergement ordinaire, une ligne de crontab appelle « schedule:run »
# chaque minute. Un conteneur n'a pas de crontab : schedule:work tient ce role,
# en appelant le planificateur lui-meme au debut de chaque minute.
#
# RELANCE S'IL S'ARRETE. Il tournait seul, en arriere-plan : tue par manque de
# memoire ou par une erreur fatale, il disparaissait, et l'entretien de nuit
# avec lui, jusqu'au deploiement suivant — sans une ligne nulle part. La boucle
# le relance apres dix secondes, et l'ecrit dans les journaux.
#
# SA SORTIE VA DANS LES JOURNAUX, et non plus dans /dev/null : chaque passe y
# laisse sa ligne « Running [...] DONE » ou « FAIL ». --whisper retire
# seulement le « No scheduled commands are ready to run » de chaque minute,
# qui noierait le reste.
#
# UNE SEULE INSTANCE avec ce planificateur. Deux ne feraient pas tourner les
# taches en double — onOneServer() l'empeche, voir routes/console.php — mais
# PLANIFICATEUR_INTEGRE=false le desactive, pour le jour ou un service dedie
# prendra le relais.
if [[ "${PLANIFICATEUR_INTEGRE:-true}" = "true" ]]; then
    echo "== Planificateur =="
    # « || code=$? », et non la commande seule : ce script tourne sous
    # « set -e », dont la boucle herite. Sans cette capture, le premier arret
    # du planificateur — code non nul — tuait la boucle elle-meme au lieu de le
    # relancer. Constate en conteneur, ou un kill l'a mis en evidence.
    (
        while true; do
            code=0
            php artisan schedule:work --whisper || code=$?
            echo "AVERTISSEMENT : le planificateur s'est arrete (code $code). Relance dans 10 s." >&2
            sleep 10
        done
    ) &
    echo "  demarre (pid $!)"
fi

echo "== Pret =="

exec frankenphp run --config /etc/frankenphp/Caddyfile
