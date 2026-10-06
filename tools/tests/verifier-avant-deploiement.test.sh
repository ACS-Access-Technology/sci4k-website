#!/usr/bin/env bash
#
# Essais de tools/verifier-avant-deploiement.sh, sur de VRAIS depots git
# temporaires (un depot « origin » nu, un clone de travail) et un faux curl qui
# joue l'API GitHub. Chaque protection doit refuser pour de bon : code 1, et le
# message qui dit pourquoi. Lance par la suite Laravel
# (tests/Feature/ScriptsDeDeploiementTest.php), donc aussi par la CI.

set -uo pipefail

script="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/verifier-avant-deploiement.sh"
tmp="$(mktemp -d)"
trap 'rm -rf "$tmp"' EXIT
echecs=0

export GIT_AUTHOR_NAME=Essai GIT_AUTHOR_EMAIL=essai@exemple.invalid
export GIT_COMMITTER_NAME=Essai GIT_COMMITTER_EMAIL=essai@exemple.invalid
unset GITHUB_TOKEN
export SCI4K_DEPOT_GITHUB=essai/depot
# Les depots d'essai ignorent le reglage de fins de ligne du poste.
export GIT_CONFIG_COUNT=1 GIT_CONFIG_KEY_0=core.autocrlf GIT_CONFIG_VALUE_0=false

# --- Le faux curl : rend le fichier designe par FAUX_CURL_REPONSE, ou echoue.
mkdir -p "$tmp/bin"
cat > "$tmp/bin/curl" <<'FAUX'
#!/usr/bin/env bash
if [[ -n "${FAUX_CURL_ECHEC:-}" ]]; then
    echo "curl: (7) Failed to connect to api.github.com port 443" >&2
    exit 7
fi
cat "$FAUX_CURL_REPONSE"
FAUX
chmod +x "$tmp/bin/curl"
export PATH="$tmp/bin:$PATH"

# --- Les reponses de l'API ----------------------------------------------------
check() { printf '{"name":"%s","id":%s,"status":"%s","conclusion":%s}' "$1" "$2" "$3" "$4"; }
reponse() {
    local fichier="$tmp/$1"
    shift
    local IFS=,
    printf '{"total_count":%s,"check_runs":[%s]}' "$#" "$*" > "$fichier"
}
vert() { check "$1" "$2" completed '"success"'; }

reponse tout-vert.json "$(vert 'Contrôles de non-régression' 10)" "$(vert 'Tests Laravel' 11)" "$(vert 'Paquets PHP' 12)" "$(vert 'Paquets npm' 13)" \
    "$(check 'SonarCloud Code Analysis' 14 completed '"failure"')"
reponse aucun-check.json
reponse un-manquant.json "$(vert 'Contrôles de non-régression' 10)" "$(vert 'Tests Laravel' 11)" "$(vert 'Paquets PHP' 12)"
reponse en-echec.json "$(vert 'Contrôles de non-régression' 10)" "$(check 'Tests Laravel' 11 completed '"failure"')" "$(vert 'Paquets PHP' 12)" "$(vert 'Paquets npm' 13)"
reponse en-cours.json "$(vert 'Contrôles de non-régression' 10)" "$(check 'Tests Laravel' 11 in_progress null)" "$(vert 'Paquets PHP' 12)" "$(vert 'Paquets npm' 13)"
reponse neutre.json "$(vert 'Contrôles de non-régression' 10)" "$(vert 'Tests Laravel' 11)" "$(check 'Paquets PHP' 12 completed '"neutral"')" "$(vert 'Paquets npm' 13)"
reponse relance-reussie.json "$(check 'Paquets PHP' 5 completed '"failure"')" "$(vert 'Contrôles de non-régression' 10)" "$(vert 'Tests Laravel' 11)" "$(vert 'Paquets PHP' 12)" "$(vert 'Paquets npm' 13)"
reponse relance-echouee.json "$(vert 'Contrôles de non-régression' 10)" "$(vert 'Tests Laravel' 11)" "$(vert 'Paquets PHP' 12)" "$(check 'Paquets PHP' 20 completed '"failure"')" "$(vert 'Paquets npm' 13)"
printf '<html>502 Bad Gateway</html>' > "$tmp/illisible.json"
printf '{"message":"Not Found"}' > "$tmp/sans-checks.json"

# --- Les depots ---------------------------------------------------------------
git init --quiet --bare --initial-branch=master "$tmp/origine.git"
git clone --quiet "$tmp/origine.git" "$tmp/depot" 2>/dev/null
cd "$tmp/depot"
git switch --quiet -c master 2>/dev/null || git switch --quiet master
echo "site" > page.txt
git add page.txt && git commit --quiet -m "premier commit"
git push --quiet origin master 2>/dev/null
sha="$(git rev-parse HEAD)"

# --- Un essai : dossier, reponse de l'API, code attendu, motif attendu ---------
essai() {
    local nom="$1" dossier="$2" api="$3" attendu="$4" motif="$5"
    shift 5
    local sortie code
    sortie="$(cd "$dossier" && FAUX_CURL_REPONSE="$tmp/$api" bash "$script" "$@" 2>&1)"
    code=$?
    if [[ "$code" -eq "$attendu" && "$sortie" == *"$motif"* ]]; then
        echo "OK     $nom"
    else
        echo "ECHEC  $nom : attendu $attendu et « $motif », obtenu $code :"
        printf '%s\n' "$sortie" | sed 's/^/         /'
        echecs=$((echecs + 1))
    fi
}

d="$tmp/depot"

# Le cas nominal, puis chaque protection.
essai "tout est vert : deploiement admis" "$d" tout-vert.json 0 "Tout est vert"
essai "SonarCloud en echec ne bloque pas" "$d" tout-vert.json 0 "Tout est vert"
essai "commit attendu donne et exact" "$d" tout-vert.json 0 "Tout est vert" --commit "${sha:0:12}"
essai "commit attendu different" "$d" tout-vert.json 1 "pas celui attendu" --commit 0000000
essai "commit attendu mal forme" "$d" tout-vert.json 1 "n'est pas une empreinte" --commit "master"

git switch --quiet -c autre
essai "branche differente de master" "$d" tout-vert.json 1 "Branche courante : autre"
git switch --quiet --detach master
essai "HEAD detache" "$d" tout-vert.json 1 "HEAD detache"
git switch --quiet master

echo "modifie" >> page.txt
essai "fichier suivi modifie" "$d" tout-vert.json 1 "pas propre"
git checkout --quiet -- page.txt
echo "nouveau" > non-suivi.txt
essai "fichier non suivi (partirait avec railway up)" "$d" tout-vert.json 1 "pas propre"
rm non-suivi.txt

echo "local" >> page.txt && git commit --quiet -am "commit local non pousse"
essai "master en avance sur origin" "$d" tout-vert.json 1 "1 commit(s) en avance"
git reset --quiet --hard "$sha"

git clone --quiet "$tmp/origine.git" "$tmp/autre" 2>/dev/null
(cd "$tmp/autre" && echo "ailleurs" >> page.txt && git commit --quiet -am "pousse depuis un autre poste" && git push --quiet origin master 2>/dev/null)
essai "master en retard sur origin" "$d" tout-vert.json 1 "1 en retard"
git pull --quiet origin master 2>/dev/null

git remote set-url origin "$tmp/introuvable.git"
essai "origin injoignable" "$d" tout-vert.json 1 "Impossible de relire origin/master"
git remote set-url origin "$tmp/origine.git"

essai "aucun check pour ce commit" "$d" aucun-check.json 1 "absent"
essai "un check obligatoire manquant" "$d" un-manquant.json 1 "Paquets npm : absent"
essai "un check en echec" "$d" en-echec.json 1 "Tests Laravel : failure"
essai "un check en cours" "$d" en-cours.json 1 "Tests Laravel : en cours"
essai "un check neutre n'est pas vert" "$d" neutre.json 1 "Paquets PHP : neutral"
essai "relance reussie apres un echec" "$d" relance-reussie.json 0 "Tout est vert"
essai "relance echouee apres une reussite" "$d" relance-echouee.json 1 "Paquets PHP : failure"
FAUX_CURL_ECHEC=1 essai "API GitHub injoignable" "$d" tout-vert.json 1 "n'a pas repondu"
essai "reponse de l'API illisible" "$d" illisible.json 1 "illisible"
essai "reponse de l'API sans checks" "$d" sans-checks.json 1 "illisible"

(unset SCI4K_DEPOT_GITHUB; essai "origin hors GitHub, depot non precise" "$d" tout-vert.json 1 "ne pointe pas sur GitHub")

mkdir -p "$tmp/pas-un-depot"
essai "hors d'un depot git" "$tmp/pas-un-depot" tout-vert.json 1 "n'est pas un depot git"

# node absent : TOUS les dossiers qui en contiennent un sont retires du PATH —
# un poste peut en avoir plusieurs. git, awk et le faux curl doivent rester
# joignables, sinon l'essai ne prouverait rien.
chemin_sans_node="$PATH"
for _ in 1 2 3 4 5; do
    trouve="$(PATH="$chemin_sans_node" command -v node 2>/dev/null)" || break
    chemin_sans_node="$(printf '%s' "$chemin_sans_node" | tr ':' '\n' | grep -vxF "$(dirname "$trouve")" | paste -sd: -)"
done
if PATH="$chemin_sans_node" command -v node >/dev/null 2>&1 \
    || ! PATH="$chemin_sans_node" command -v git >/dev/null 2>&1 \
    || ! PATH="$chemin_sans_node" command -v awk >/dev/null 2>&1; then
    echo "ECHEC  outil absent : impossible d'isoler node des autres outils, l'essai ne prouverait rien."
    echecs=$((echecs + 1))
else
    PATH="$chemin_sans_node" essai "outil requis absent (node)" "$d" tout-vert.json 1 "Outil requis absent : node"
fi

if [[ "$echecs" -gt 0 ]]; then
    echo "$echecs essai(s) en echec."
    exit 1
fi
echo "Tous les essais de la verification avant deploiement passent."
