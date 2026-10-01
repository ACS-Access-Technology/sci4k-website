#!/usr/bin/env bash
#
# Depose dans app-laravel/public/ les ressources du site statique : feuilles de
# style, script et images. Les pages HTML des maquettes sont toutes portees en
# Blade et exclues de la copie (liste plus bas) ; une page ajoutee aux
# maquettes sans y figurer serait copiee, et servie avant Laravel.
#
#     ./tools/sync-frontoffice.sh
#
# POURQUOI CES COPIES NE SONT PAS VERSIONNEES
# -------------------------------------------
# maquettes-frontoffice/ est la seule source de verite. Verser ces 2,5 Mo une seconde
# fois dans le depot creerait deux exemplaires des memes fichiers : a la
# premiere retouche d'un style ou d'une image, l'un serait corrige et l'autre
# oublie, sans que rien ne le signale. Les copies sont donc ignorees par git
# (voir app-laravel/.gitignore) et refaites par ce script, qui est versionne.
# Meme raisonnement que pour tools/extraction-articles.py : on versionne
# l'outil, pas son produit.
#
# A LANCER APRES CHAQUE CLONAGE, et apres toute modification de maquettes-frontoffice/.
#
# PAGES VOLONTAIREMENT EXCLUES
# ----------------------------
# index.html, actualites.html, actualite-detail.html, services.html, faq.html,
# biens.html
# et presentation.html ne sont pas copiees : Laravel sert desormais ces six
# pages depuis la base. Les copier
# ferait coexister deux adresses rendant deux versions divergentes du meme
# contenu — celle de la base et celle, figee, du fichier statique.
#
# sitemap.xml suit la meme regle. Le serveur sert un fichier de public/ avant
# d'entrer dans PHP : le copier masquerait la route qui rend le plan du site
# depuis la base, et le site continuerait d'annoncer des adresses redirigees
# sans connaitre aucun article.
#
# robots.txt aussi, et pour exactement la meme raison : il est desormais rendu
# depuis le champ « Fichier robots.txt » de l'ecran Configuration, et il declare
# le plan du site. Une copie figee dans public/ reprendrait la main et
# l'editeur ne comprendrait pas pourquoi son reglage ne change rien.

set -euo pipefail

racine="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

if [[ ! -d "$racine/maquettes-frontoffice" || ! -d "$racine/app-laravel/public" ]]; then
    echo "Erreur : lancer ce script depuis la racine du depot." >&2
    echo "  maquettes-frontoffice/ et app-laravel/public/ doivent exister." >&2
    exit 1
fi

source_fo="$racine/maquettes-frontoffice"
cible="$racine/app-laravel/public"

# Les deux pages legales en font partie. Copiees dans public/, elles etaient
# servies par le serveur AVANT Laravel : /politique-confidentialite.html
# montrait une ancienne version du texte, corrigee depuis en base, et aucune
# redirection vers /mentions-legales ne pouvait s'appliquer. Leur source reste
# dans maquettes-frontoffice/ : c'est la que PagePubliqueController va chercher
# la page d'origine tant que la version en base n'est pas publiee.
#
# 404.html et 500.html aussi. Les pages d'erreur du site sont des vues Blade
# (resources/views/errors/) ; ces deux fichiers n'etaient servis que comme
# pages ORDINAIRES, a leur propre adresse, avec une reponse 200 — une fausse
# page d'erreur que rien ne distinguait d'un contenu indexable. Exclues, ces
# adresses repondent desormais par la vraie page introuvable, en 404.
exclues=("index.html" "actualites.html" "actualite-detail.html" "services.html" "faq.html" "presentation.html" "biens.html" "contact.html" "mentions-legales.html" "politique-confidentialite.html" "404.html" "500.html")

echo "Synchronisation depuis $source_fo"

# Feuilles de style, script et images. rsync --delete garde la cible alignee :
# un fichier retire de maquettes-frontoffice/ disparait aussi de public/.
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
        [[ "$nom" = "$exclue" ]] && ignoree=1
    done
    if [[ "$ignoree" -eq 1 ]]; then
        echo "  $nom : exclue, Laravel repond a cette adresse"
        rm -f "$cible/$nom"
        continue
    fi
    cp "$page" "$cible/$nom"
    copiees=$((copiees + 1))
done
echo "  pages statiques : $copiees copiees"

# Le plan du site est rendu par Laravel depuis la base. Une copie laissee dans
# public/ par une synchronisation anterieure masquerait la route, le serveur
# servant un fichier avant d'entrer dans PHP.
for fige in sitemap.xml robots.txt; do
    if [[ -e "$cible/$fige" ]]; then
        rm -f "$cible/$fige"
        echo "  $fige : copie retiree, servi par Laravel"
    fi
done

# Lien vers storage/app/public, ou vivent les couvertures televersees depuis
# l'administration. Sans lui elles repondent 404, sans erreur cote serveur :
# le defaut ne se voit qu'a l'image cassee sur le site public.
if [[ ! -e "$cible/storage" ]]; then
    (cd "$racine/app-laravel" && php artisan storage:link >/dev/null)
    echo "  storage/ : lien cree"
else
    echo "  storage/ : lien deja present"
fi

echo "Termine. Ces copies ne sont pas versionnees."
