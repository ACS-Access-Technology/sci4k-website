#!/usr/bin/env bash
#
# Le flux des branches : dev → preprod → master, et aucun autre chemin.
#
#     ./tools/verifier-flux-des-branches.sh <source> <cible> [<depot-source> <depot-cible>]
#
# Appele par le workflow « Flux des branches » sur chaque demande de fusion
# (PR) vers preprod ou master. Il repond 0 si la fusion suit le flux, 1 sinon.
#
# LES REGLES :
#
#   vers preprod : seule dev est admise ;
#   vers master  : seule preprod est admise ;
#   vers une autre branche (dev, une branche de travail) : rien a controler,
#     le flux ne porte que sur les deux branches protegees. Le script le dit
#     et repond 0.
#
# Les correctifs urgents suivent le meme chemin : il n'existe aucune exception.
#
# LES DEPOTS : une demande venue d'un fork porte le nom de SA branche. Une
# branche « dev » d'un fork n'est pas la dev de ce depot : quand les deux
# depots sont fournis, ils doivent etre le meme.

set -euo pipefail

usage() {
    cat <<'AIDE'
Usage : tools/verifier-flux-des-branches.sh <source> <cible> [<depot-source> <depot-cible>]

Verifie qu'une demande de fusion suit le flux dev -> preprod -> master.

  <source>        branche d'origine de la demande (ex. dev)
  <cible>         branche visee (ex. preprod)
  <depot-source>  depot de la branche d'origine (ex. ACS-Access-Technology/sci4k-website)
  <depot-cible>   depot vise ; les deux doivent etre le meme

Codes de retour : 0 flux respecte ou cible non controlee, 1 flux refuse, 2 usage.
AIDE
}

if [[ "${1:-}" = "-h" || "${1:-}" = "--aide" || "${1:-}" = "--help" ]]; then
    usage
    exit 0
fi

if [[ $# -ne 2 && $# -ne 4 ]]; then
    usage >&2
    exit 2
fi

source_fusion="$1"
cible="$2"
depot_source="${3:-}"
depot_cible="${4:-}"

refuser() {
    echo "ERREUR : fusion refusee — « $source_fusion » vers « $cible »." >&2
    echo "  $1" >&2
    echo "  Le flux obligatoire est : dev -> preprod -> master." >&2
    echo "  Fusionner d'abord dans la branche qui precede, puis ouvrir la demande" >&2
    echo "  depuis celle-ci. Voir docs/BRANCHES_ET_DEPLOIEMENT.md." >&2
    exit 1
}

case "$cible" in
    preprod) attendue=dev ;;
    master) attendue=preprod ;;
    *)
        echo "Flux non controle : « $cible » n'est pas une branche protegee (seules preprod et master le sont)."
        exit 0
        ;;
esac

if [[ -n "$depot_source" && "$depot_source" != "$depot_cible" ]]; then
    refuser "La branche vient d'un autre depot ($depot_source) : seule la branche $attendue de $depot_cible peut viser $cible."
fi

if [[ "$source_fusion" != "$attendue" ]]; then
    refuser "Vers $cible, seule la branche $attendue est admise."
fi

echo "Flux respecte : $source_fusion -> $cible."
