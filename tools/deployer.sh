#!/usr/bin/env bash
#
# Deploie le site sur un hebergement PHP classique.
#
#     ./tools/deployer.sh
#
# Rejoue la sequence de docs/MISE_EN_LIGNE.md dans l'ordre, chaque etape ayant
# une raison d'etre avant la suivante. Le script est REJOUABLE : c'est la meme
# commande a la premiere mise en ligne et a chaque mise a jour.
#
# CE QU'IL NE FAIT PAS : ecrire le fichier .env. Il le lit, le verifie, et
# s'arrete s'il manque. Ecraser la configuration d'un site en production serait
# le seul geste de cette sequence qu'on ne pourrait pas defaire.
#
# LE GARDE-FOU QUI COMPTE est la verification d'APP_DEBUG. Un deploiement qui
# copie .env.example — le geste naturel, et celui que fait la CI — laisse
# APP_DEBUG=true, et le premier visiteur qui declenche une erreur lit la trace
# d'execution complete : requetes SQL et variables d'environnement comprises.
# Le script refuse de continuer plutot que de publier cela.

set -euo pipefail

racine="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app="$racine/app-laravel"

echo "== Deploiement de SCI4K =="

# --- Verifications prealables, avant de toucher a quoi que ce soit -----------

if [ ! -f "$app/.env" ]; then
    echo "ERREUR : $app/.env est absent." >&2
    echo "  Copier le gabarit de PRODUCTION, et non celui de developpement :" >&2
    echo "    cp app-laravel/.env.production.example app-laravel/.env" >&2
    echo "    php artisan key:generate" >&2
    echo "  puis renseigner ce qui y est marque « A RENSEIGNER »." >&2
    exit 1
fi

valeur_env() {
    # Lit une cle du .env sans charger tout le fichier dans le shell : une
    # valeur contenant une espace ou un point-virgule s'y executerait.
    sed -n "s/^$1=//p" "$app/.env" | head -1 | tr -d '"'"'"'' | tr -d '\r'
}

debug="$(valeur_env APP_DEBUG)"
environnement="$(valeur_env APP_ENV)"
cle="$(valeur_env APP_KEY)"

if [ "$debug" != "false" ]; then
    echo "ERREUR : APP_DEBUG vaut « ${debug:-<vide>} » et doit valoir false." >&2
    echo "  En production, la page d'erreur de Laravel expose la trace" >&2
    echo "  d'execution, les requetes SQL et les variables d'environnement" >&2
    echo "  au premier visiteur venu." >&2
    exit 1
fi

if [ "$environnement" != "production" ]; then
    echo "ERREUR : APP_ENV vaut « ${environnement:-<vide>} » et doit valoir production." >&2
    exit 1
fi

if [ -z "$cle" ]; then
    echo "ERREUR : APP_KEY est vide. Lancer « php artisan key:generate »." >&2
    echo "  Elle dechiffre les cookies et les sessions : sans elle, rien ne tient." >&2
    exit 1
fi

php_version="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
if [ "$(printf '%s\n8.3\n' "$php_version" | sort -V | head -1)" != "8.3" ]; then
    echo "ERREUR : PHP $php_version. composer.json exige 8.3 ou plus." >&2
    exit 1
fi

echo "  Configuration verifiee : PHP $php_version, APP_ENV=production, APP_DEBUG=false."

# --- Dependances et ressources ----------------------------------------------

echo "== Dependances PHP =="
cd "$app"
composer install --no-dev --optimize-autoloader --no-interaction

# Sans cette etape, toute page d'administration echoue sur « Vite manifest not
# found » : les vues appellent @vite et public/build n'est pas versionne.
echo "== Ressources front =="
npm ci
npm run build

# --- Base et fichiers --------------------------------------------------------

echo "== Migrations =="
php artisan migrate --force

# Sans ce lien, toute image ajoutee depuis le backoffice est ecrite mais ne
# s'affiche jamais : le code ecrit systematiquement sur le disque « public »,
# qui vit dans storage/.
echo "== Lien des fichiers televerses =="
php artisan storage:link || echo "  (deja en place)"

# Depose dans public/ les styles, le script, les images et les pages non encore
# portees en Blade. A rejouer apres chaque mise a jour de maquettes-frontoffice/.
echo "== Ressources du site statique =="
"$racine/tools/sync-frontoffice.sh"

# --- Caches ------------------------------------------------------------------

echo "== Caches de production =="
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo
echo "== Deploiement termine =="
echo
echo "Il reste deux choses que ce script ne peut pas faire :"
echo
echo "  1. La racine du serveur web doit pointer sur app-laravel/public/,"
echo "     et non sur la racine du depot. Autrement .env, storage/ et le code"
echo "     source deviennent telechargeables."
echo
echo "  2. Une ligne de cron doit appeler le planificateur, sans quoi les deux"
echo "     taches d'entretien ne tournent jamais et deux tables grossissent"
echo "     sans borne :"
echo
echo "     * * * * * cd $app && php artisan schedule:run >> /dev/null 2>&1"
echo
