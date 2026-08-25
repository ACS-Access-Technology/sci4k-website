#!/usr/bin/env bash
#
# Depose dans app-laravel/public/ les ressources du site statique : feuilles de
# style, script, images, et les pages non encore portees en Blade.
#
#     ./tools/sync-frontoffice.sh
#
# POURQUOI CES COPIES NE SONT PAS VERSIONNEES
# -------------------------------------------
# frontoffice/ est la seule source de verite. Verser ces 2,5 Mo une seconde
# fois dans le depot creerait deux exemplaires des memes fichiers : a la
# premiere retouche d'un style ou d'une image, l'un serait corrige et l'autre
# oublie, sans que rien ne le signale. Les copies sont donc ignorees par git
# (voir app-laravel/.gitignore) et refaites par ce script, qui est versionne.
# Meme raisonnement que pour tools/extraction-articles.py : on versionne
# l'outil, pas son produit.
#
# A LANCER APRES CHAQUE CLONAGE, et apres toute modification de frontoffice/.
#
# PAGES VOLONTAIREMENT EXCLUES
# ----------------------------
# actualites.html et actualite-detail.html ne sont pas copiees : Laravel sert
# desormais ces deux pages depuis la base. Les copier ferait coexister deux
# adresses rendant deux versions divergentes du meme contenu — celle de la base
# et celle, figee, du fichier statique.

set -euo pipefail

racine="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [ ! -d "$racine/frontoffice" ] || [ ! -d "$racine/app-laravel/public" ]; then
    echo "Erreur : lancer ce script depuis la racine du depot." >&2
    echo "  frontoffice/ et app-laravel/public/ doivent exister." >&2
    exit 1
fi

source_fo="$racine/frontoffice"
cible="$racine/app-laravel/public"

exclues=("actualites.html" "actualite-detail.html")

echo "Synchronisation depuis $source_fo"

# Feuilles de style, script et images. rsync --delete garde la cible alignee :
# un fichier retire de frontoffice/ disparait aussi de public/.
for dossier in assets images; do
    rsync -a --delete "$source_fo/$dossier/" "$cible/$dossier/"
    echo "  $dossier/ : $(find "$cible/$dossier" -type f | wc -l | tr -d ' ') fichiers"
done

# Pages statiques encore servies telles quelles.
copiees=0
for page in "$source_fo"/*.html; do
    nom="$(basename "$page")"
    ignoree=0
    for exclue in "${exclues[@]}"; do
        [ "$nom" = "$exclue" ] && ignoree=1
    done
    if [ "$ignoree" -eq 1 ]; then
        echo "  $nom : exclue, servie par Laravel"
        rm -f "$cible/$nom"
        continue
    fi
    cp "$page" "$cible/$nom"
    copiees=$((copiees + 1))
done
echo "  pages statiques : $copiees copiees"

echo "Termine. Ces copies ne sont pas versionnees."
