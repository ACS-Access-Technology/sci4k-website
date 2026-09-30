#!/usr/bin/env bash
#
# A lancer AVANT chaque mise en ligne :
#
#     ./tools/verifier-avant-deploiement.sh && railway up --service sci4k
#
# « railway up » televerse le DOSSIER DE TRAVAIL du poste, pas un commit : ce
# qui part en ligne est ce qui se trouve sur le disque, quelle que soit la
# branche, qu'il y ait des fichiers modifies ou non, que la CI ait reussi ou
# non. Ce script refuse tant que ce dossier n'est pas exactement master, tel
# que la CI l'a valide :
#
#   1. la branche courante est master ;
#   2. l'arbre de travail est propre, fichiers non suivis compris — ils
#      partiraient avec le reste ;
#   3. master local est identique a origin/master, relu a l'instant ;
#   4. le commit est celui attendu (celui d'origin/master, ou celui passe par
#      --commit) ;
#   5. les checks obligatoires de la CI sont verts POUR CE COMMIT.
#
# TOUT CE QUI NE PEUT PAS ETRE VERIFIE EST UN REFUS : un reseau coupe, une API
# qui ne repond pas, un outil absent ne valent jamais « vert ».
#
# L'etat de la CI est lu dans l'API publique de GitHub (le depot est public).
# GITHUB_TOKEN, s'il est pose, est joint a la requete : il leve la limite de
# requetes et ouvre un depot devenu prive. Voir docs/BRANCHES_ET_DEPLOIEMENT.md.

set -euo pipefail

readonly BRANCHE=master

# Les checks du commit deploye. Les noms sont ceux des jobs des workflows
# (verification.yml, audit-dependances.yml), tels que GitHub les affiche.
# « Flux des branches » n'y figure pas : il ne tourne que sur les demandes de
# fusion, pas sur le commit de master. SonarCloud n'y figure pas : il est
# informatif (voir la documentation).
readonly CHECKS_REQUIS=(
    "Contrôles de non-régression"
    "Tests Laravel"
    "Paquets PHP"
    "Paquets npm"
)

usage() {
    cat <<'AIDE'
Usage : tools/verifier-avant-deploiement.sh [--commit <empreinte>]

Verifie que le dossier de travail peut etre mis en ligne par « railway up » :
branche master, arbre propre, identique a origin/master, CI verte pour ce
commit. Repond 0 si tout est bon, 1 sinon — ne rien deployer dans ce cas.

  --commit <empreinte>  exige en plus que le commit soit celui-ci (debut de
                        l'empreinte accepte, 7 caracteres au moins)
  -h, --aide            cette aide

Variables facultatives :
  GITHUB_TOKEN          jeton GitHub (lecture seule suffit) joint a la requete
  SCI4K_DEPOT_GITHUB    « proprietaire/depot », si origin ne pointe pas sur GitHub

Utilisation :
  ./tools/verifier-avant-deploiement.sh && railway up --service sci4k
AIDE
}

refuser() {
    echo "" >&2
    echo "ERREUR : $1" >&2
    shift
    local ligne
    for ligne in "$@"; do
        echo "  $ligne" >&2
    done
    echo "" >&2
    echo "Deploiement REFUSE : ne pas lancer « railway up »." >&2
    echo "Que faire : docs/BRANCHES_ET_DEPLOIEMENT.md, « Si le script refuse »." >&2
    exit 1
}

commit_attendu=""
while [[ $# -gt 0 ]]; do
    case "$1" in
        -h | --aide | --help)
            usage
            exit 0
            ;;
        --commit)
            [[ $# -ge 2 && -n "$2" ]] || { usage >&2; exit 2; }
            commit_attendu="$2"
            shift 2
            ;;
        *)
            echo "Argument inconnu : $1" >&2
            usage >&2
            exit 2
            ;;
    esac
done

if [[ -n "$commit_attendu" && ! "$commit_attendu" =~ ^[0-9a-fA-F]{7,40}$ ]]; then
    refuser "« $commit_attendu » n'est pas une empreinte de commit (7 a 40 caracteres hexadecimaux)."
fi

echo "== Verification avant deploiement =="

# --- 0. Les outils ------------------------------------------------------------

for outil in git curl node; do
    if ! command -v "$outil" >/dev/null 2>&1; then
        refuser "Outil requis absent : $outil." \
            "git lit l'etat du depot, curl interroge GitHub, node lit sa reponse." \
            "L'installer, puis relancer."
    fi
done

# --- 1. Le depot ----------------------------------------------------------------

if ! racine="$(git rev-parse --show-toplevel 2>/dev/null)"; then
    refuser "Ce dossier n'est pas un depot git." \
        "Lancer le script depuis le depot SCI4K."
fi
cd "$racine"

# --- 2. La branche ------------------------------------------------------------

branche="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo '(aucune : HEAD detache)')"
if [[ "$branche" != "$BRANCHE" ]]; then
    refuser "Branche courante : $branche. Seule $BRANCHE se deploie." \
        "git switch $BRANCHE && git pull, puis relancer."
fi
echo "  branche       : $branche"

# --- 3. L'arbre de travail ----------------------------------------------------

etat="$(git status --porcelain --untracked-files=all)"
if [[ -n "$etat" ]]; then
    refuser "L'arbre de travail n'est pas propre : « railway up » enverrait ces fichiers." \
        "$(printf '%s\n' "$etat" | head -n 10)" \
        "Les valider sur dev et les faire suivre jusqu'a master, ou les ecarter (git stash -u)."
fi
echo "  arbre         : propre"

# --- 4. Identique a origin/master ---------------------------------------------

if ! git fetch --quiet origin "$BRANCHE" 2>/dev/null; then
    refuser "Impossible de relire origin/$BRANCHE (reseau, droits d'acces ?)." \
        "Sans cette lecture, rien ne dit que ce poste deploie la derniere version validee."
fi

local_sha="$(git rev-parse HEAD)"
distant_sha="$(git rev-parse "origin/$BRANCHE")"
if [[ "$local_sha" != "$distant_sha" ]]; then
    read -r en_avance en_retard < <(git rev-list --left-right --count "HEAD...origin/$BRANCHE")
    refuser "$BRANCHE local differe de origin/$BRANCHE : $en_avance commit(s) en avance, $en_retard en retard." \
        "En retard : git pull. En avance : ces commits n'ont pas suivi le flux dev -> preprod -> master ; ils ne se deploient pas."
fi

if [[ -n "$commit_attendu" && "$local_sha" != "$(printf '%s' "$commit_attendu" | tr 'A-F' 'a-f')"* ]]; then
    refuser "Le commit courant ($local_sha) n'est pas celui attendu ($commit_attendu)."
fi
echo "  commit        : $local_sha — $(git log -1 --format='%s (%ad)' --date=short)"

# --- 5. La CI de ce commit ----------------------------------------------------

depot="${SCI4K_DEPOT_GITHUB:-}"
if [[ -z "$depot" ]]; then
    url="$(git remote get-url origin 2>/dev/null || true)"
    if [[ "$url" =~ github\.com[:/]([^/]+)/([^/]+)$ ]]; then
        depot="${BASH_REMATCH[1]}/${BASH_REMATCH[2]%.git}"
    else
        refuser "origin ne pointe pas sur GitHub ($url) : impossible de lire la CI." \
            "Poser SCI4K_DEPOT_GITHUB=proprietaire/depot si le depot vit ailleurs."
    fi
fi

entetes=(-H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28")
if [[ -n "${GITHUB_TOKEN:-}" ]]; then
    entetes+=(-H "Authorization: Bearer $GITHUB_TOKEN")
fi

adresse="https://api.github.com/repos/$depot/commits/$local_sha/check-runs?per_page=100"
if ! reponse="$(curl --silent --show-error --fail --max-time 20 "${entetes[@]}" "$adresse" 2>&1)"; then
    refuser "L'API GitHub n'a pas repondu : l'etat de la CI ne peut pas etre verifie." \
        "$reponse" \
        "Limite de requetes atteinte ? Poser GITHUB_TOKEN. Reseau coupe ? Relancer plus tard."
fi

# node ne fait que traduire la reponse en lignes « nom, id, statut, conclusion »
# separees par des tabulations. La comparaison des noms reste ici, en bash :
# les noms portent des accents, que la ligne de commande de Windows abime.
if ! lignes="$(printf '%s' "$reponse" | node -e '
    let texte = "";
    process.stdin.setEncoding("utf8");
    process.stdin.on("data", (morceau) => { texte += morceau; });
    process.stdin.on("end", () => {
        const donnees = JSON.parse(texte);
        if (!donnees || !Array.isArray(donnees.check_runs)) { process.exit(3); }
        for (const c of donnees.check_runs) {
            process.stdout.write([c.name, c.id, c.status, c.conclusion ?? ""].join("\t") + "\n");
        }
    });
')"; then
    refuser "La reponse de l'API GitHub est illisible : l'etat de la CI ne peut pas etre verifie."
fi

problemes=()
for nom in "${CHECKS_REQUIS[@]}"; do
    # La derniere execution fait foi : un check relance a la main, ou rejoue
    # par le passage hebdomadaire de l'audit, remplace le precedent.
    derniere="$(printf '%s\n' "$lignes" | awk -F'\t' -v nom="$nom" '$1 == nom && $2 + 0 > max { max = $2 + 0; ligne = $0 } END { print ligne }')"

    if [[ -z "$derniere" ]]; then
        problemes+=("$nom : absent — la CI n'a pas tourne pour ce commit, ou le check a change de nom.")
        continue
    fi

    statut="$(printf '%s' "$derniere" | cut -f3)"
    conclusion="$(printf '%s' "$derniere" | cut -f4)"
    if [[ "$statut" != "completed" ]]; then
        problemes+=("$nom : en cours ($statut) — attendre la fin, puis relancer.")
    elif [[ "$conclusion" != "success" ]]; then
        problemes+=("$nom : $conclusion.")
    else
        echo "  CI            : $nom — vert"
    fi
done

if [[ ${#problemes[@]} -gt 0 ]]; then
    refuser "La CI de $local_sha n'est pas verte." "${problemes[@]}"
fi

echo ""
echo "Tout est vert : ce dossier est master, tel que la CI l'a valide."
echo "Deployer : railway up --service sci4k"
