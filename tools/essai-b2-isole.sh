#!/usr/bin/env bash
#
# Essai ISOLE de la sauvegarde externe, contre un bucket d'ESSAI et une COPIE
# de la base. Voir docs/SAUVEGARDES.md, §5, dont ce script est la mise en acte.
#
#   ESSAI_CONFIRME=oui ./tools/essai-b2-isole.sh
#
# Rien n'est lu dans un fichier : tout vient de l'ENVIRONNEMENT du terminal, a
# poser avant (jamais dans le depot, jamais dans un journal) :
#
#   SAUVEGARDE_S3_ENDPOINT  SAUVEGARDE_S3_REGION  SAUVEGARDE_S3_BUCKET
#   SAUVEGARDE_S3_KEY       SAUVEGARDE_S3_SECRET  SAUVEGARDE_CLE
#   SAUVEGARDE_PING_URL     (ou SAUVEGARDE_SANS_SURVEILLANCE=true pour un essai local)
#   DB_HOST  DB_PORT  DB_DATABASE  DB_USERNAME  DB_PASSWORD   -> la COPIE jetable
#   RESTAURATION_CONTENEUR  nom du conteneur MySQL 9.7.x jetable qui restaure
#   RESTAURATION_MOT_DE_PASSE  mot de passe root de ce conteneur
#
# Ce que fait le script, dans l'ordre, et s'arrete a la premiere anomalie :
#   0. garde-fous : bucket d'essai, base locale, pas de production ;
#   1. droits de la cle : ecriture et lecture acceptees, SUPPRESSION REFUSEE ;
#   2. depot reel (sauvegarde:externe), relu et compare par l'application ;
#   3. releve independant des objets (SDK direct) et controle des empreintes ;
#   4. dechiffrement par l'outil autonome ;
#   5. restauration dans une base NEUVE du conteneur jetable, et comparaison
#      des comptes de lignes, table par table, avec l'export.
#
# Il ne touche NI la base de production, NI Railway, NI le DNS, NI la messagerie.

set -euo pipefail

racine="$(cd "$(dirname "$0")/.." && pwd)"
app="$racine/app-laravel"
php="${PHP:-php}"
# Extensions a charger si elles ne le sont pas deja (ex. poste Windows : "-d extension=sodium -d extension=pdo_mysql").
opts="${PHP_OPTIONS:-}"

echec() { echo "ARRET : $*" >&2; exit 1; }
etape() { printf '\n== %s\n' "$*"; }

# --- 0. garde-fous -----------------------------------------------------------
etape "0. Garde-fous"

[[ "${ESSAI_CONFIRME:-}" = "oui" ]] || echec "poser ESSAI_CONFIRME=oui pour confirmer qu'il s'agit d'un essai isole."

for v in SAUVEGARDE_S3_ENDPOINT SAUVEGARDE_S3_BUCKET SAUVEGARDE_S3_KEY SAUVEGARDE_S3_SECRET SAUVEGARDE_CLE \
         DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD RESTAURATION_CONTENEUR RESTAURATION_MOT_DE_PASSE; do
    [[ -n "${!v:-}" ]] || echec "variable $v absente (jamais dans un fichier : l'environnement du terminal)."
done

# Un bucket de production ne doit pas servir a un essai : le nom doit le dire.
[[ "$SAUVEGARDE_S3_BUCKET" == *essai* ]] || echec "le nom du bucket doit contenir « essai » (un bucket de production ne sert pas ici)."

# La base lue doit etre locale : jamais celle de Railway.
case "$DB_HOST" in
    127.0.0.1 | localhost | 172.17.* | host.docker.internal) ;;
    *) echec "DB_HOST=$DB_HOST n'est pas une base locale : l'essai lit une COPIE jetable, jamais la production." ;;
esac

[[ "${APP_ENV:-local}" != "production" ]] || echec "APP_ENV=production refuse."

if [[ -z "${SAUVEGARDE_PING_URL:-}" && "${SAUVEGARDE_SANS_SURVEILLANCE:-}" != "true" ]]; then
    echec "SAUVEGARDE_PING_URL absent (surveillance obligatoire). SAUVEGARDE_SANS_SURVEILLANCE=true ne vaut que pour un essai local."
fi

command -v docker >/dev/null || echec "docker est requis pour la restauration."
docker inspect "$RESTAURATION_CONTENEUR" >/dev/null 2>&1 || echec "conteneur $RESTAURATION_CONTENEUR introuvable."

export DB_CONNECTION=mysql
export SAUVEGARDE_ACTIVE=false   # l'essai lance la commande a la main, rien n'est planifie
cd "$app"

# --- 1. droits de la cle -----------------------------------------------------
etape "1. Droits de la cle (suppression refusee attendue)"

if [[ "${ESSAI_SANS_CONTROLE_DROITS:-}" = "1" ]]; then
    case "$SAUVEGARDE_S3_ENDPOINT" in
        http://127.0.0.1* | http://localhost*) echo "  !! controle des droits SAUTE : stockage LOCAL de repetition, cela ne prouve rien sur B2." ;;
        *) echec "ESSAI_SANS_CONTROLE_DROITS=1 n'est admis que sur un stockage local (127.0.0.1)." ;;
    esac
else
    "$php" $opts artisan sauvegarde:verifier-config || echec "controle de configuration en echec (voir ci-dessus). Si la cle PEUT supprimer, elle est trop puissante : ne pas continuer."
fi

# --- 2. depot reel -----------------------------------------------------------
etape "2. Depot, relecture et verification par l'application"
"$php" $opts artisan sauvegarde:externe || echec "la sauvegarde a echoue : rien n'est a considerer comme sauvegarde."

# --- 3. releve independant ---------------------------------------------------
etape "3. Releve independant des objets"
travail="$(mktemp -d)"
trap 'rm -rf "$travail"' EXIT
"$php" "$racine/tools/essai-b2-lecture.php" "$travail"

base="$(ls "$travail"/base-*.sql.gz.enc 2>/dev/null | head -1)"
fich="$(ls "$travail"/fichiers-*.tar.gz.enc 2>/dev/null | head -1)"
man="$travail/manifeste.json"
[[ -n "$base" && -n "$fich" && -f "$man" ]] || echec "objets attendus absents (base, fichiers, manifeste)."

attendu() { "$php" -r '$m = json_decode(file_get_contents($argv[1]), true); echo $m["objets"][$argv[2]][$argv[3]];' "$man" "$1" "$2"; }

for g in base fichiers; do
    f="$base"; [[ "$g" = fichiers ]] && f="$fich"
    reel="$(sha256sum "$f" | cut -c1-64)"
    [[ "$reel" = "$(attendu "$g" sha256)" ]] || echec "empreinte du chiffre differente pour $g (manifeste vs objet telecharge)."
    echo "  $g : empreinte du chiffre conforme au manifeste"
done

# --- 4. dechiffrement --------------------------------------------------------
etape "4. Dechiffrement par l'outil autonome"
"$php" $opts "$racine/tools/dechiffrer-sauvegarde.php" "$base" "$travail/base.sql.gz" >/dev/null
"$php" $opts "$racine/tools/dechiffrer-sauvegarde.php" "$fich" "$travail/fichiers.tar.gz" >/dev/null

for g in base fichiers; do
    f="$travail/base.sql.gz"; [[ "$g" = fichiers ]] && f="$travail/fichiers.tar.gz"
    [[ "$(sha256sum "$f" | cut -c1-64)" = "$(attendu "$g" sha256_clair)" ]] || echec "empreinte du CLAIR differente pour $g."
    echo "  $g : contenu dechiffre identique a l'export"
done

# --- 5. restauration isolee --------------------------------------------------
etape "5. Restauration dans une base NEUVE du conteneur jetable"
nouvelle="restauration_b2_$(date +%Y%m%d_%H%M%S)"
mysql() { docker exec -i "$RESTAURATION_CONTENEUR" mysql -uroot -p"$RESTAURATION_MOT_DE_PASSE" "$@" 2> >(grep -v "Using a password" >&2); }

mysql -e "create database $nouvelle character set utf8mb4"
zcat "$travail/base.sql.gz" | mysql "$nouvelle"

zcat "$travail/base.sql.gz" | grep '^-- lignes:' | sed 's/-- lignes: //; s/=/ /' | tr -d '\r' | sort > "$travail/attendu.txt"
mysql -N -e "select table_name from information_schema.tables where table_schema='$nouvelle'" | tr -d '\r' | while read -r t; do
    # </dev/null : docker exec -i lirait sinon la liste des tables que « read » consomme.
    echo "$t $(mysql -N "$nouvelle" -e "select count(*) from \`$t\`" </dev/null | tr -d '\r')"
done | sort > "$travail/restaure.txt"

if diff -q "$travail/attendu.txt" "$travail/restaure.txt" >/dev/null; then
    echo "  $(wc -l < "$travail/restaure.txt") tables : comptes de lignes identiques a l'export"
else
    diff "$travail/attendu.txt" "$travail/restaure.txt" | head -10
    echo "  (base laissee en place pour examen : $nouvelle)" >&2
    echec "comptes de lignes differents apres restauration."
fi

mkdir "$travail/f" && tar -xzf "$travail/fichiers.tar.gz" -C "$travail/f"
n="$(find "$travail/f" -type f | wc -l)"
echo "  fichiers : $n extraits"

mysql -e "drop database $nouvelle"
echo
echo "ESSAI REUSSI. A consigner dans docs/SAUVEGARDES.md : date, bucket (nom), tables, lignes, fichiers."
echo "Rien de ceci ne prouve l'execution dans le conteneur Railway ni l'alerte externe (voir §5, etapes 5 a 7)."
