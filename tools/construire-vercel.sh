#!/usr/bin/env bash
#
# Construit le site pour Vercel.
#
# Appele par vercel.json (buildCommand). Ne sert QU'A CETTE PLATEFORME : un
# hebergement ordinaire passe par tools/deployer.sh, qui fait davantage —
# migrations, storage:link, caches — parce qu'il dispose d'un disque et d'une
# ligne de commande.
#
# CE QU'IL NE FAIT PAS, ET POURQUOI
#
# Pas de « php artisan migrate » : la construction tourne sur une machine
# jetable, sans garantie d'atteindre la base, et une migration lancee a chaque
# deploiement d'apercu toucherait la base de production. Les migrations se
# lancent a la main, une fois, depuis un poste connecte a la base.
#
# Pas de « storage:link » : le disque est en lecture seule, et les fichiers
# televerses vivent dans un stockage objet. Voir STOCKAGE_PUBLIC_DISTANT.
#
# Pas de « config:cache » : la configuration figerait les variables au moment
# de la construction. Sans lui, env() est lu a l'execution, et changer une
# variable dans la console Vercel prend effet sans reconstruire.

set -euo pipefail

racine="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
app="$racine/app-laravel"

echo "== Construction pour Vercel =="

cd "$app"

echo "== Dependances PHP =="
composer install --no-dev --optimize-autoloader --no-interaction

# --ignore-scripts : un paquet installe peut executer du code arbitraire par ses
# scripts de cycle de vie. Verifie : le build de ce projet n'en a besoin d'aucun.
echo "== Ressources front =="
npm ci --ignore-scripts
npm run build

# Depose dans public/ les styles, le script, les images et les pages non encore
# portees en Blade. Ces fichiers sont servis tels quels par la plateforme.
echo "== Ressources du site statique =="
"$racine/tools/sync-frontoffice.sh"

# Les routes sont compilees ICI, pendant que le disque est encore inscriptible.
# A l'execution, Laravel lit bootstrap/cache sans y ecrire.
#
# LES VUES, NON, et c'est deliberé. Le point d'entree redirige les vues
# compilees vers /tmp, seul emplacement inscriptible : les compiler ici les
# deposerait dans storage/framework/views, que l'execution ne regarderait meme
# pas. Les compiler a la volee coute une fraction de seconde au premier appel
# d'une instance froide, et tolere qu'une vue ait echappe a view:cache — sur un
# disque en lecture seule, cet oubli serait une erreur fatale et opaque.
echo "== Compilation des routes =="
php artisan route:cache

echo "== Construction terminee =="
