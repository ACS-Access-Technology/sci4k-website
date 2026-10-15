#!/usr/bin/env bash
#
# Change le mot de passe racine d'un conteneur MySQL JETABLE (par defaut sci4k-restauration).
# Voir docs/OPERATIONS_SECURISATION_PREPAREES.md, §4.
#
#   tools/changer-mot-de-passe-conteneur-jetable.sh [conteneur]
#
# A LANCER PAR L'UTILISATEUR. Les deux mots de passe sont saisis a la demande (saisie masquee) :
# ils ne sont JAMAIS un argument, jamais ecrits dans un fichier, jamais affiches.
#
#   - ils ne transitent que par l'entree standard et par l'environnement (MYSQL_PWD), jamais par
#     la ligne de commande d'un processus (lisible par tout processus de la machine) ;
#   - le SQL est envoye par l'entree standard ;
#   - les dependances sont recherchees SANS afficher la valeur : le motif est lu depuis un
#     descripteur de fichier, pas depuis un argument.
#
# Ce que le script NE fait PAS : il ne supprime rien, ne cree rien, ne purge pas l'ancienne valeur de la
# configuration des conteneurs (seule la suppression de ces conteneurs, operation separee, la retire).

set -euo pipefail

CONTENEUR="${1:-sci4k-restauration}"
LONGUEUR_MIN=20

echec() { echo "ARRET : $*" >&2; exit 1; }

command -v docker >/dev/null || echec "docker requis"
[[ "$CONTENEUR" == sci4k-* ]] || echec "ce script ne traite que les conteneurs sci4k-* (recu : $CONTENEUR)"
docker inspect "$CONTENEUR" >/dev/null 2>&1 || echec "conteneur $CONTENEUR introuvable"
[[ "$(docker inspect -f '{{.Config.Image}}' "$CONTENEUR")" == mysql:* ]] || echec "$CONTENEUR n'est pas un conteneur MySQL"
lire() { # variable, invite : saisie masquee, sans echo
    local cible="$1" invite="$2" v
    if [[ -t 0 ]]; then read -rs -p "$invite" v; echo >&2; else read -r v; fi
    printf -v "$cible" '%s' "$v"
}
lire ANCIEN "Ancien mot de passe : "
lire NOUVEAU "Nouveau mot de passe (>= ${LONGUEUR_MIN} caracteres) : "
if [[ -t 0 ]]; then lire CONFIRMATION "Nouveau mot de passe, encore une fois : "; else CONFIRMATION="$NOUVEAU"; fi

[[ "$NOUVEAU" = "$CONFIRMATION" ]] || echec "les deux saisies du nouveau mot de passe different"
(( ${#NOUVEAU} >= LONGUEUR_MIN )) || echec "nouveau mot de passe trop court (minimum ${LONGUEUR_MIN} caracteres)"
[[ "$NOUVEAU" != "$ANCIEN" ]] || echec "le nouveau mot de passe doit differer de l'ancien"
[[ "$NOUVEAU" =~ ^[A-Za-z0-9+/=_.-]+$ ]] || echec "caracteres admis : lettres, chiffres et + / = _ . -  (evite toute injection dans le SQL)"

demarre_par_nous=0
if [[ "$(docker inspect -f '{{.State.Running}}' "$CONTENEUR")" != "true" ]]; then
    docker start "$CONTENEUR" >/dev/null
    demarre_par_nous=1
fi
arreter() { (( demarre_par_nous )) && docker stop "$CONTENEUR" >/dev/null 2>&1 || true; unset ANCIEN NOUVEAU CONFIRMATION MYSQL_PWD; }
trap arreter EXIT

for _ in $(seq 1 60); do
    MYSQL_PWD="$ANCIEN" docker exec -e MYSQL_PWD "$CONTENEUR" mysqladmin -uroot ping >/dev/null 2>&1 && break
    sleep 2
done

export MYSQL_PWD="$ANCIEN"
docker exec -e MYSQL_PWD "$CONTENEUR" mysql -uroot -N -e 'select 1' >/dev/null 2>&1 || echec "l'ancien mot de passe est refuse : rien n'a ete modifie"

printf "ALTER USER 'root'@'%%' IDENTIFIED BY '%s'; ALTER USER 'root'@'localhost' IDENTIFIED BY '%s'; FLUSH PRIVILEGES;\n" "$NOUVEAU" "$NOUVEAU" \
    | docker exec -i -e MYSQL_PWD "$CONTENEUR" mysql -uroot >/dev/null 2>&1 || echec "la modification a echoue"

export MYSQL_PWD="$NOUVEAU"
docker exec -e MYSQL_PWD "$CONTENEUR" mysql -uroot -N -e 'select 1' >/dev/null 2>&1 || echec "le nouveau mot de passe n'est PAS accepte"
echo "  nouveau mot de passe : accepte"

export MYSQL_PWD="$ANCIEN"
if docker exec -e MYSQL_PWD "$CONTENEUR" mysql -uroot -N -e 'select 1' >/dev/null 2>&1; then
    echec "l'ancien mot de passe est ENCORE accepte"
fi
echo "  ancien mot de passe : refuse"

echo "== Conteneurs sci4k-* dont la configuration contient encore l'ANCIENNE valeur (a traiter par la suppression, operation separee)"
trouves=0
for c in $(docker ps -a --format '{{.Names}}' | grep '^sci4k-'); do
    if docker inspect "$c" --format '{{range .Config.Env}}{{println .}}{{end}}' | grep -qFf <(printf '%s\n' "$ANCIEN"); then
        echo "  $c"; trouves=$((trouves + 1))
    fi
done
(( trouves > 0 )) || echo "  aucun"
echo
echo "FAIT. Le nouveau mot de passe est dans votre gestionnaire de mots de passe, nulle part ailleurs."
