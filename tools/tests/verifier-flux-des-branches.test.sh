#!/usr/bin/env bash
#
# Essais de tools/verifier-flux-des-branches.sh : chaque combinaison d'origine
# et de cible, et le code de retour attendu. Lance par la suite Laravel
# (tests/Feature/ScriptsDeDeploiementTest.php), donc aussi par la CI.

set -uo pipefail

script="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/verifier-flux-des-branches.sh"
depot="ACS-Access-Technology/sci4k-website"
echecs=0

essai() {
    local nom="$1" attendu="$2" motif="$3"
    shift 3
    local sortie code
    sortie="$(bash "$script" "$@" 2>&1)"
    code=$?
    if [[ "$code" -eq "$attendu" && "$sortie" == *"$motif"* ]]; then
        echo "OK     $nom"
    else
        echo "ECHEC  $nom : attendu $attendu et « $motif », obtenu $code :"
        printf '%s\n' "$sortie" | sed 's/^/         /'
        echecs=$((echecs + 1))
    fi
}

essai "dev -> preprod : admis" 0 "Flux respecte" dev preprod "$depot" "$depot"
essai "preprod -> master : admis" 0 "Flux respecte" preprod master "$depot" "$depot"
essai "dev -> master : refuse" 1 "seule la branche preprod est admise" dev master "$depot" "$depot"
essai "master -> preprod : refuse" 1 "seule la branche dev est admise" master preprod "$depot" "$depot"
essai "branche de travail -> preprod : refuse" 1 "seule la branche dev est admise" correctif-urgent preprod "$depot" "$depot"
essai "branche de travail -> master : refuse" 1 "seule la branche preprod est admise" correctif-urgent master "$depot" "$depot"
essai "branche inconnue -> master : refuse" 1 "dev -> preprod -> master" inconnue master "$depot" "$depot"
essai "dev d'un fork -> preprod : refuse" 1 "vient d'un autre depot" dev preprod "quelquun/sci4k-website" "$depot"
essai "preprod d'un fork -> master : refuse" 1 "vient d'un autre depot" preprod master "quelquun/sci4k-website" "$depot"
essai "vers dev : non controle" 0 "Flux non controle" correctif dev "$depot" "$depot"
essai "vers une branche de travail : non controle" 0 "Flux non controle" dev essai "$depot" "$depot"
essai "sans les depots : admis" 0 "Flux respecte" dev preprod
essai "arguments manquants : usage" 2 "Usage" dev

if [[ "$echecs" -gt 0 ]]; then
    echo "$echecs essai(s) en echec."
    exit 1
fi
echo "Tous les essais du flux passent."
