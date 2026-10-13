#!/usr/bin/env bash
#
# Copie de recuperation chiffree de l'etat de production du 12 octobre 2026.
# Voir docs/SECURISATION_COPIES_V2.md et docs/OPERATIONS_SECURISATION_PREPAREES.md.
#
#   tools/securiser-copie-recuperation.sh controles   # lecture seule : rien n'est ecrit
#   tools/securiser-copie-recuperation.sh chiffrer    # cree le coffre et les .gpg (la phrase est demandee par gpg)
#   tools/securiser-copie-recuperation.sh verifier    # empreintes en flux, extraction de controle (la phrase est redemandee)
#   tools/securiser-copie-recuperation.sh restaurer   # restauration dans un conteneur SANS RESEAU, base en memoire
#
# A LANCER PAR L'UTILISATEUR, dans son terminal. Rien ne l'appelle automatiquement.
#
# LA PHRASE SECRETE n'est ni un argument, ni une variable, ni un fichier : gpg la
# demande lui-meme (fenetre pinentry) et ne la garde pas (--no-symkey-cache).
# Ce script ne l'ecrit, ne la lit et ne l'affiche jamais.
#
# GARANTIES, dans l'ordre ou elles s'appliquent :
#   - la source n'est JAMAIS ouverte en ecriture : elle n'est que lue (cp, tar, gpg en lecture) ;
#   - le coffre est hors de la source, et un fichier de destination existant n'est pas ecrase ;
#   - le coffre n'est accessible qu'a l'utilisateur courant (icacls) ;
#   - aucun clair n'est ecrit sur le disque, sauf l'extraction de controle des fichiers
#     televerses, dans un dossier temporaire supprime a la fin ;
#   - la restauration n'a aucun reseau, aucun volume, aucun port : elle ne peut atteindre
#     ni la production ni les conteneurs existants.
#
# Variables facultatives : SAUVEGARDES_DIR, COFFRE_DIR, EXPORT_SUPPLEMENTAIRE (export SQL du 6/10
# a chiffrer aussi ; vide pour l'ignorer), GPG (essais : remplace la commande gpg).

set -euo pipefail

SRC="${SAUVEGARDES_DIR:-$HOME/sauvegardes-sci4k}"
COFFRE="${COFFRE_DIR:-$HOME/coffre-sci4k}"
EXPORT_SUPPLEMENTAIRE="${EXPORT_SUPPLEMENTAIRE-${TEMP:-/tmp}/full.sql}"
GPG="${GPG:-gpg}"
BASE="base-20261012-1554-point-de-retour-avant-corrections.sql.gz"
FICHIERS="fichiers-20261012-1554-point-de-retour"
CONTENEUR="sci4k-coffre-essai"
OPTIONS_GPG=(--symmetric --cipher-algo AES256 --s2k-digest-algo SHA512 --s2k-count 65011712 --no-symkey-cache)

echec() { echo "ARRET : $*" >&2; exit 1; }
etape() { printf '\n== %s\n' "$*"; }
chemin_reel() { (cd "$1" 2>/dev/null && pwd -P) || echo "$1"; }

controles() {
    etape "Source : la copie du 12/10 est-elle identifiable sans ambiguite ?"
    [[ -f "$SRC/$BASE" && -f "$SRC/$BASE.sha256" && -d "$SRC/$FICHIERS" ]] || echec "elements du 12/10 introuvables dans $SRC"
    [[ "$(cut -c1-64 "$SRC/$BASE.sha256")" = "$(sha256sum "$SRC/$BASE" | cut -c1-64)" ]] || echec "l'empreinte de $BASE ne correspond pas a son .sha256"
    gzip -t "$SRC/$BASE" || echec "archive $BASE corrompue"
    echo "  $BASE : $(stat -c %s "$SRC/$BASE") o, empreinte conforme au .sha256, archive integre"
    echo "  $FICHIERS : $(find "$SRC/$FICHIERS" -type f | wc -l) fichiers, $(find "$SRC/$FICHIERS" -type f -printf '%s\n' | awk '{s+=$1} END {print s}') o"
    echo "  tables dans l'export : $(zcat "$SRC/$BASE" | grep -c '^CREATE TABLE')"
    if [[ -n "$EXPORT_SUPPLEMENTAIRE" ]]; then
        [[ -f "$EXPORT_SUPPLEMENTAIRE" ]] || echec "export supplementaire introuvable : $EXPORT_SUPPLEMENTAIRE (EXPORT_SUPPLEMENTAIRE= pour l'ignorer)"
        echo "  export supplementaire : $(stat -c %s "$EXPORT_SUPPLEMENTAIRE") o"
    fi

    etape "Destination : capacite et emplacement"
    mkdir -p "$(dirname "$COFFRE")"
    local besoin libre
    besoin=$(( ( $(stat -c %s "$SRC/$BASE") + $(find "$SRC/$FICHIERS" -type f -printf '%s\n' | awk '{s+=$1} END {print s}') + $( [[ -n "$EXPORT_SUPPLEMENTAIRE" ]] && stat -c %s "$EXPORT_SUPPLEMENTAIRE" || echo 0 ) ) * 3 ))
    libre=$(df -k "$(dirname "$COFFRE")" | awk 'NR==2 {print $4*1024}')
    echo "  besoin (x3 par prudence) : $besoin o ; libre : $libre o"
    (( libre > besoin )) || echec "espace insuffisant"
    local s d
    s="$(chemin_reel "$SRC")"; d="$(chemin_reel "$(dirname "$COFFRE")")/$(basename "$COFFRE")"
    [[ "$d" != "$s"* && "$s" != "$d"* ]] || echec "le coffre ($d) et la source ($s) ne doivent pas s'imbriquer"
    echo "  coffre : $d (distinct de la source)"
    for f in "$BASE.gpg" "$FICHIERS.tar.gpg" "export-supplementaire.sql.gpg"; do
        [[ ! -e "$COFFRE/$f" ]] || echec "$COFFRE/$f existe deja : il ne sera pas ecrase"
    done

    etape "Outils"
    command -v "$GPG" >/dev/null || echec "gpg introuvable"
    "$GPG" --version | head -1
    command -v pinentry >/dev/null || command -v pinentry-w32 >/dev/null || echo "  AVERTISSEMENT : pinentry introuvable, la saisie de la phrase pourrait echouer"
    command -v docker >/dev/null && echo "  docker present (restauration)" || echo "  docker absent : la restauration isolee sera impossible"
    echo; echo "Controles termines : RIEN n'a ete ecrit."
}

restreindre_coffre() {
    local w n
    w="$(cygpath -w "$COFFRE")"
    powershell.exe -NoProfile -Command "icacls '$w' /inheritance:r /grant:r \"\${env:USERNAME}:(OI)(CI)F\" | Out-Null"
    n="$(powershell.exe -NoProfile -Command "(Get-Acl '$w').Access | Measure-Object | Select-Object -ExpandProperty Count" | tr -d '\r')"
    [[ "$n" = "1" ]] || echec "le coffre doit n'avoir qu'une seule entree de droits (trouve : $n)"
    echo "  droits du coffre : une seule identite (l'utilisateur courant)"
}

chiffrer() {
    controles
    etape "Coffre aux droits restreints"
    mkdir -p "$COFFRE"
    restreindre_coffre

    etape "Empreintes des ORIGINAUX (avant tout chiffrement)"
    ( cd "$SRC" && sha256sum "$BASE" ) > "$COFFRE/EMPREINTES-origine.txt"
    ( cd "$SRC" && find "$FICHIERS" -type f -print0 | sort -z | xargs -0 sha256sum ) > "$COFFRE/EMPREINTES-fichiers-origine.txt"
    [[ -z "$EXPORT_SUPPLEMENTAIRE" ]] || sha256sum "$EXPORT_SUPPLEMENTAIRE" | awk '{print $1"  export-supplementaire.sql"}' >> "$COFFRE/EMPREINTES-origine.txt"
    wc -l "$COFFRE"/EMPREINTES-*.txt

    etape "Chiffrement : gpg va demander la phrase secrete (deux fois), dans une fenetre"
    "$GPG" "${OPTIONS_GPG[@]}" --output "$COFFRE/$BASE.gpg" "$SRC/$BASE"
    tar -cf - -C "$SRC" "$FICHIERS" | tee >(sha256sum | cut -c1-64 > "$COFFRE/EMPREINTE-flux-tar.txt") \
        | "$GPG" "${OPTIONS_GPG[@]}" --output "$COFFRE/$FICHIERS.tar.gpg"
    [[ -z "$EXPORT_SUPPLEMENTAIRE" ]] || "$GPG" "${OPTIONS_GPG[@]}" --output "$COFFRE/export-supplementaire.sql.gpg" "$EXPORT_SUPPLEMENTAIRE"
    ls -l "$COFFRE" | awk 'NR>1 {print "  " $5 " o  " $9}'
    echo; echo "CHIFFREMENT FAIT. Rien n'est valide tant que « verifier » puis « restaurer » n'ont pas reussi."
}

verifier() {
    [[ -d "$COFFRE" ]] || echec "coffre introuvable : $COFFRE"
    etape "Algorithme et integrite de chaque fichier"
    for f in "$COFFRE"/*.gpg; do
        local p; p="$("$GPG" --list-packets --pinentry-mode cancel "$f" 2>&1 || true)"
        grep -q 'cipher 9' <<<"$p" && grep -qE 'mdc_method:? 2' <<<"$p" || echec "$(basename "$f") : AES-256 et controle d'integrite non constates"
        echo "  $(basename "$f") : AES-256 (cipher 9), integrite (mdc_method 2)"
    done

    etape "Empreintes en flux : le contenu dechiffre doit egaler l'original (la phrase est redemandee)"
    local attendu reel
    attendu="$(awk -v b="$BASE" '{n = $2; sub(/^\*/, "", n)} n == b {print $1}' "$COFFRE/EMPREINTES-origine.txt")"
    reel="$("$GPG" --no-symkey-cache -d "$COFFRE/$BASE.gpg" | sha256sum | cut -c1-64)"
    [[ -n "$attendu" && "$attendu" = "$reel" ]] || echec "empreinte de $BASE : differente apres dechiffrement"
    echo "  $BASE : identique ($reel)"
    if [[ -f "$COFFRE/export-supplementaire.sql.gpg" ]]; then
        attendu="$(awk '$2 == "export-supplementaire.sql" {print $1}' "$COFFRE/EMPREINTES-origine.txt")"
        reel="$("$GPG" --no-symkey-cache -d "$COFFRE/export-supplementaire.sql.gpg" | sha256sum | cut -c1-64)"
        [[ "$attendu" = "$reel" ]] || echec "export supplementaire : empreinte differente"
        echo "  export supplementaire : identique ($reel)"
    fi
    attendu="$(cat "$COFFRE/EMPREINTE-flux-tar.txt")"
    reel="$("$GPG" --no-symkey-cache -d "$COFFRE/$FICHIERS.tar.gpg" | sha256sum | cut -c1-64)"
    [[ "$attendu" = "$reel" ]] || echec "archive des fichiers : empreinte du flux differente"
    echo "  $FICHIERS (flux tar) : identique ($reel)"

    etape "Extraction de controle des fichiers televerses (dossier temporaire, supprime ensuite)"
    local v; v="$(mktemp -d)"; trap 'rm -rf "$v"' RETURN
    "$GPG" --no-symkey-cache -d "$COFFRE/$FICHIERS.tar.gpg" | tar -xf - -C "$v"
    ( cd "$v" && find "$FICHIERS" -type f -print0 | sort -z | xargs -0 sha256sum ) | diff - "$COFFRE/EMPREINTES-fichiers-origine.txt" >/dev/null \
        || echec "fichiers extraits differents des originaux"
    echo "  $(find "$v/$FICHIERS" -type f | wc -l) fichiers extraits, tous identiques aux originaux"
    echo; echo "Reste a faire a la main : saisir UNE FOIS une mauvaise phrase et constater le refus ; puis « restaurer »."
}

restaurer() {
    [[ -d "$COFFRE" ]] || echec "coffre introuvable : $COFFRE"
    command -v docker >/dev/null || echec "docker requis"
    docker image inspect mysql:9.7.2 >/dev/null 2>&1 || echec "image mysql:9.7.2 absente (aucun telechargement n'est lance ici)"
    local w; w="$(mktemp -d)"
    trap 'docker rm -f "$CONTENEUR" >/dev/null 2>&1 || true; rm -rf "$w"' RETURN
    docker rm -f "$CONTENEUR" >/dev/null 2>&1 || true

    etape "Conteneur isole : aucun reseau, aucun port, base en memoire"
    docker run -d --rm --name "$CONTENEUR" --network none --tmpfs /var/lib/mysql:rw,size=1g -e MYSQL_ALLOW_EMPTY_PASSWORD=yes mysql:9.7.2 >/dev/null
    local reseaux ports
    reseaux="$(docker inspect -f '{{range $k,$v := .NetworkSettings.Networks}}{{$k}} {{end}}' "$CONTENEUR")"
    ports="$(docker port "$CONTENEUR" 2>/dev/null || true)"
    [[ "$reseaux" = "none " && -z "$ports" ]] || echec "isolation non constatee (reseaux : $reseaux ; ports : $ports)"
    echo "  reseaux : $reseaux ; ports publies : aucun"
    for _ in $(seq 1 90); do
        docker logs "$CONTENEUR" 2>&1 | grep -q 'MySQL init process done' && docker exec "$CONTENEUR" mysql -uroot -e 'select 1' >/dev/null 2>&1 && break
        sleep 2
    done

    etape "Restauration depuis la copie chiffree (la phrase est redemandee)"
    "$GPG" --no-symkey-cache -d "$COFFRE/$BASE.gpg" | zcat | tee >(grep -c '^CREATE TABLE' > "$w/attendu") | docker exec -i "$CONTENEUR" mysql -uroot
    local base n_attendu n_restaure lignes
    base="$(docker exec "$CONTENEUR" mysql -uroot -N -e "select schema_name from information_schema.schemata where schema_name not in ('information_schema','mysql','performance_schema','sys')" | head -1 | tr -d '\r')"
    n_attendu="$(cat "$w/attendu")"
    n_restaure="$(docker exec "$CONTENEUR" mysql -uroot -N -e "select count(*) from information_schema.tables where table_schema='$base'" | tr -d '\r')"
    [[ "$n_attendu" = "$n_restaure" ]] || echec "tables : $n_restaure restaurees pour $n_attendu attendues"
    lignes=0
    for t in $(docker exec "$CONTENEUR" mysql -uroot -N -e "select table_name from information_schema.tables where table_schema='$base'" | tr -d '\r'); do
        lignes=$(( lignes + $(docker exec "$CONTENEUR" mysql -uroot -N "$base" -e "select count(*) from \`$t\`" </dev/null | tr -d '\r') ))
    done
    echo "  base « $base » : $n_restaure tables sur $n_attendu attendues, $lignes lignes"
    docker stop "$CONTENEUR" >/dev/null
    sleep 3
    [[ -z "$(docker ps -a --format '{{.Names}}' | grep -x "$CONTENEUR" || true)" ]] || echec "le conteneur isole existe encore"
    echo "  conteneur isole arrete et supprime ; la base en memoire a disparu"
    echo; echo "RESTAURATION ISOLEE REUSSIE."
}

case "${1:-}" in
    controles) controles ;;
    chiffrer) chiffrer ;;
    verifier) verifier ;;
    restaurer) restaurer ;;
    *) sed -n '3,12p' "$0"; exit 2 ;;
esac
