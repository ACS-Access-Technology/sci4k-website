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

echo "== Demarrage de SCI4K =="

# APP_KEY manquante : l'application demarre mais ne dechiffre plus ni cookies ni
# sessions, et l'erreur qui s'ensuit n'a rien d'evident. Mieux vaut la dire ici.
if [ -z "${APP_KEY:-}" ]; then
    echo "ERREUR : APP_KEY est vide." >&2
    echo "  Generer une cle avec « php artisan key:generate --show » sur un poste" >&2
    echo "  de developpement, puis la poser en variable d'environnement." >&2
    exit 1
fi

if [ "${APP_DEBUG:-false}" = "true" ] && [ "${APP_ENV:-}" = "production" ]; then
    echo "AVERTISSEMENT : APP_DEBUG=true avec APP_ENV=production." >&2
    echo "  La page d'erreur exposera la trace d'execution, les requetes SQL et" >&2
    echo "  les variables d'environnement au premier visiteur venu." >&2
fi

# Le lien des fichiers televerses. Sans lui, toute image ajoutee depuis le
# backoffice est ecrite mais ne s'affiche jamais.
php artisan storage:link 2>/dev/null || echo "  lien de storage deja en place"

# Les migrations tournent AU DEMARRAGE, faute de pouvoir tourner ailleurs.
#
# C'est acceptable pour un environnement d'essai, et discutable en production :
# deux instances qui demarrent ensemble les lanceraient en meme temps. Laravel
# pose un verrou, mais le jour ou ce site tournera sur plusieurs instances, il
# faudra les sortir d'ici.
if [ "${MIGRER_AU_DEMARRAGE:-true}" = "true" ]; then
    echo "== Migrations =="
    php artisan migrate --force --no-interaction
fi

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
# en appelant le planificateur lui-meme a intervalle regulier.
#
# UNE SEULE INSTANCE, sinon chacune aurait son planificateur et les taches
# d'entretien tourneraient en double. PLANIFICATEUR_INTEGRE=false le desactive,
# pour le jour ou le site tournera derriere plusieurs instances et ou un service
# de cron dedie prendra le relais.
if [ "${PLANIFICATEUR_INTEGRE:-true}" = "true" ]; then
    echo "== Planificateur =="
    php artisan schedule:work >/dev/null 2>&1 &
    echo "  demarre (pid $!)"
fi

echo "== Pret =="

exec frankenphp run --config /etc/frankenphp/Caddyfile
