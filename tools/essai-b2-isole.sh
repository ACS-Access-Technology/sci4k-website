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
# Facultatives :
#   PHP, PHP_OPTIONS        executable PHP et extensions a charger (poste Windows)
#   SAUVEGARDE_PREFIXE      prefixe des objets, « sci4k » par defaut
#   ESSAI_SANS_CONTROLE_DROITS=1   repetition sur un S3 LOCAL (127.0.0.1) qui
#                           autorise la suppression ; refuse sur tout autre point
#                           d'acces. Ne prouve RIEN sur B2.
#
# La base lue (DB_HOST/DB_PORT) doit etre celle du conteneur RESTAURATION_CONTENEUR :
# le script le verifie dans Docker et refuse sinon (un tunnel vers la production
# ouvert en local ne passe pas).
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
    127.0.0.1 | localhost | 172.17.*) ;;
    *) echec "DB_HOST=$DB_HOST n'est pas une base locale : l'essai lit une COPIE jetable, jamais la production." ;;
esac

[[ "$DB_DATABASE" != "railway" ]] || echec "DB_DATABASE=railway est le nom de la base de production : l'essai lit une copie."
[[ "${APP_ENV:-local}" != "production" ]] || echec "APP_ENV=production refuse."

# La base lue doit etre CELLE DU CONTENEUR JETABLE, et non « quelque chose qui
# ecoute en local » : un tunnel vers la production ouvert sur 127.0.0.1 passerait
# la verification du nom d'hote, pas celle-ci. Le conteneur doit tourner, et
# DB_HOST/DB_PORT doivent designer son port publie (ou son adresse interne).
command -v docker >/dev/null || echec "docker est requis (restauration, et controle de la base lue)."
[[ "$(docker inspect -f '{{.State.Running}}' "$RESTAURATION_CONTENEUR" 2>/dev/null)" = "true" ]] \
    || echec "conteneur $RESTAURATION_CONTENEUR introuvable ou arrete."
ip_conteneur="$(docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}} {{end}}' "$RESTAURATION_CONTENEUR")"
ports_publies="$(docker port "$RESTAURATION_CONTENEUR" 3306/tcp 2>/dev/null || true)"
if [[ "$DB_HOST" = "127.0.0.1" || "$DB_HOST" = "localhost" ]]; then
    grep -qE "(127\.0\.0\.1|0\.0\.0\.0|\[::\]):$DB_PORT\$" <<<"$ports_publies" \
        || echec "$DB_HOST:$DB_PORT n'est pas le port publie de $RESTAURATION_CONTENEUR : la base lue n'est pas celle du conteneur jetable."
else
    [[ " $ip_conteneur" == *" $DB_HOST "* && "$DB_PORT" = "3306" ]] \
        || echec "$DB_HOST:$DB_PORT n'est pas l'adresse de $RESTAURATION_CONTENEUR : la base lue n'est pas celle du conteneur jetable."
fi

if [[ -z "${SAUVEGARDE_PING_URL:-}" && "${SAUVEGARDE_SANS_SURVEILLANCE:-}" != "true" ]]; then
    echec "SAUVEGARDE_PING_URL absent (surveillance obligatoire). SAUVEGARDE_SANS_SURVEILLANCE=true ne vaut que pour un essai local."
fi

export DB_CONNECTION=mysql
export SAUVEGARDE_ACTIVE=false   # l'essai lance la commande a la main, rien n'est planifie
cd "$app"

# --- 1. droits de la cle -----------------------------------------------------
etape "1. Droits de la cle (suppression refusee attendue)"

if [[ "${ESSAI_SANS_CONTROLE_DROITS:-}" = "1" ]]; then
    # « http://127.0.0.1* » laisserait passer « http://127.0.0.1.exemple.com » : le
    # port ou la barre suivent obligatoirement l'adresse.
    case "$SAUVEGARDE_S3_ENDPOINT" in
        http://127.0.0.1:* | http://127.0.0.1/ | http://127.0.0.1 | http://localhost:* | http://localhost/ | http://localhost)
            echo "  !! controle des droits SAUTE : stockage LOCAL de repetition, cela ne prouve rien sur B2." ;;
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

attendu() { local genre="$1" champ="$2"; "$php" -r '$m = json_decode(file_get_contents($argv[1]), true); echo $m["objets"][$argv[2]][$argv[3]];' "$man" "$genre" "$champ"; }

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
# Le mot de passe passe par l'ENVIRONNEMENT, jamais par un argument (« -p… » ou « -e MYSQL_PWD=valeur » :
# un argument se lit dans la liste des processus de la machine). « -e MYSQL_PWD » sans valeur fait
# transmettre par docker la variable exportee ici.
export MYSQL_PWD="$RESTAURATION_MOT_DE_PASSE"
mysql() { docker exec -i -e MYSQL_PWD "$RESTAURATION_CONTENEUR" mysql -uroot "$@"; }

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
