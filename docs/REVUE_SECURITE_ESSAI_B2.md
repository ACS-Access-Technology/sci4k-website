# Revue de sécurité — `tools/essai-b2-isole.sh` (13 octobre 2026)

Relue **intégralement** avant toute utilisation sur un fournisseur réel. Le
script ne doit servir que sur un bucket d'essai et une copie jetable.
**Aucun essai n'a été fait sur Backblaze B2** : aucun compte n'existe.

Fichiers : `tools/essai-b2-isole.sh`, `tools/essai-b2-lecture.php`.

## 1. Peut-il lire, écrire ou restaurer dans la base de production ?

**Non, par construction, sous réserve de la limite ci-dessous.** Constaté :

| Risque | Garde-fou | Vérifié par un essai refusé |
|---|---|---|
| `DB_HOST` distant (Railway, proxy public) | refusé : seuls `127.0.0.1`, `localhost`, `172.17.*` passent | — (test par lecture du code) |
| Tunnel vers la production ouvert sur `127.0.0.1` | `DB_HOST:DB_PORT` doit être **le port publié du conteneur jetable**, lu dans Docker (`docker port`), ou son adresse interne ; le conteneur doit tourner | oui : `DB_PORT=33999` et `DB_HOST=172.17.0.9` refusés |
| Base nommée comme la production | `DB_DATABASE=railway` refusé | oui |
| `APP_ENV=production` | refusé | par lecture du code |
| Conteneur de restauration absent ou arrêté | refusé | oui |
| Restauration dans une base existante | la base cible est **nouvelle** (`restauration_b2_<horodatage>`) ; le dump ne contient ni `USE` ni `CREATE DATABASE` (vérifié dans `DumpSql.php`), il s'exécute dans la base nommée en argument | par lecture du code |
| `drop database` d'une autre base | le nom supprimé est celui que le script a créé | par lecture du code |

Côté application, la lecture se fait par une transaction `START TRANSACTION
WITH CONSISTENT SNAPSHOT` : **aucune écriture** dans la base source.

**Limite résiduelle.** Si quelqu'un redirige à la main le port publié du
conteneur jetable vers une autre base, ou y charge lui-même la production, le
script ne le voit pas. Les variables `DB_*` de l'environnement l'emportent sur le
fichier `.env` (Laravel ne réécrit pas une variable déjà posée) ; un `.env` de
production n'est donc pas lu pour la base. `APP_ENV` est lu dans l'environnement
seulement.

## 2. Ce que le script écrit ou supprime hors de son bucket

- **Aucune suppression de son fait.** Il n'appelle jamais `delete`, sauf via
  `sauvegarde:verifier-config`, qui **tente de supprimer un seul objet qu'il vient
  de créer** (`<préfixe>/_essais/controle-<12 hex>.txt`) pour prouver que la
  clé ne le peut pas.
- Écritures dans le bucket : `<préfixe>/quotidien/AAAA-MM-JJ/` (base, fichiers,
  manifeste), et `<préfixe>/hebdomadaire/…` le dimanche, et l'objet de contrôle.
  Rien en dehors du bucket nommé par `SAUVEGARDE_S3_BUCKET`.
- **Le garde-fou sur le nom du bucket** (« essai ») est une **convention**, pas une
  preuve : un bucket de production nommé « …essai… » passerait. À l'inverse, la
  clé d'essai, limitée à ce bucket, **ne peut pas** toucher un autre bucket.
- Fichiers locaux : un dossier `mktemp` (supprimé à la sortie, y compris sur
  Ctrl-C ou échec), contenant la sauvegarde **déchiffrée** (données de la copie) ;
  aucune autre écriture. `rm -rf` ne s'applique qu'à ce dossier.
- Conteneur jetable : crée puis supprime sa propre base. **En cas d'échec de la
  comparaison, la base est laissée pour examen** (à supprimer à la main).

## 3. Les secrets ne sont-ils jamais affichés ?

Constaté sur une exécution réelle, avec des valeurs distinctives : la sortie
complète a été cherchée pour la clé d'accès, le secret d'accès, la clé de
chiffrement et le mot de passe : **0 occurrence**.

Points relevés et traités :

1. **Corrigé** : le mot de passe MySQL passait en argument (`-p…`), lisible dans
   la liste des processus. Il passe désormais par `MYSQL_PWD` (environnement).
2. **Corrigé** : le test du point d'accès local (`http://127.0.0.1*`) laissait
   passer `http://127.0.0.1.exemple.com` ; il exige maintenant `:`, `/` ou la fin
   de chaîne (essai refusé constaté).
3. **Restant** : `bash -x` ou `set -x` affichent les variables : **ne pas les
   utiliser**. Les messages d'erreur du SDK peuvent citer le point d'accès et le
   nom du bucket (jamais les clés, tronqués à 300 caractères par la commande).
4. **Restant** : les variables sont dans l'environnement du shell ; les poser
   avec un préfixe d'espace pour qu'elles ne restent pas dans l'historique, et
   fermer le terminal après l'essai.
5. Aucun secret n'est écrit dans le dépôt, dans un fichier ni dans un journal.

## 4. Pourquoi le contrôle de suppression est ignoré sur le S3 local — et comment le vérifier sur B2

Le S3 local de répétition (SeaweedFS lancé sans authentification) **accepte toute
clé et toute opération, suppression comprise**. Le contrôle de configuration y
répond donc `LA CLE PEUT SUPPRIMER` et le script s'arrête (constaté le 13
octobre). Pour répéter le **reste** de la chaîne, `ESSAI_SANS_CONTROLE_DROITS=1`
saute cette seule étape, et **uniquement** vers `127.0.0.1` ou `localhost`. Il
affiche un avertissement : la répétition ne prouve rien sur les droits.

**Sur B2, la vérification réelle comporte deux preuves indépendantes :**

1. **Par le script** : `sauvegarde:verifier-config` écrit, relit, liste, puis tente
   de supprimer son objet de contrôle. Attendu : « Suppression : REFUSÉE par le
   fournisseur ». Tout autre résultat arrête l'essai.
2. **Par la console B2** (⚠ intitulés non vérifiés, aucun compte) : relever les
   capacités de la clé d'application : *listFiles*, *readFiles*, *writeFiles*,
   **sans *deleteFiles***, restreinte à ce seul bucket. Confirmer que l'objet de
   contrôle est toujours présent sous `_essais/`.

Une seule des deux preuves ne suffit pas : la première prouve le comportement,
la seconde la configuration. **Remarque propre à B2 (⚠ à vérifier)** : B2 « masque »
une version à la suppression ; si l'API refuse `deleteFiles` mais accepte
`writeFiles` sur un nom existant, une écrasure crée une nouvelle version sans
détruire l'ancienne. Le contrôle d'objet immuable (Object Lock) est une option
du bucket, à décider **à sa création**.

## 5. Documentation des commandes et variables

Le en-tête du script liste toutes les variables obligatoires et facultatives,
chaque garde-fou et l'ordre des étapes. `docs/SAUVEGARDES.md`, §5, y renvoie ;
`docs/PROCEDURE_DEPLOIEMENT_PROGRESSIF.md`, §8, donne la commande de lancement.

## 6. Défauts trouvés pendant la répétition

1. `docker exec -i` lisait la liste des tables que `read` consommait : la
   comparaison ne voyait qu'une table. Corrigé (`</dev/null`).
2. Plusieurs essais le même jour partagent un dossier : le relevé prenait un
   objet d'un autre essai. Corrigé : le relevé suit le **manifeste** le plus récent.

## 7. Verdict

Utilisable sur un bucket d'essai B2 et la copie jetable, **après** les points
suivants, que seul le propriétaire peut faire : compte B2 au nom de
l'organisation, bucket **d'essai séparé**, clé limitée à ce bucket sans
*deleteFiles* (contrôlée dans la console), clé de chiffrement **d'essai** jetable
(jamais celle de la production), point d'alerte d'essai.

**Rien ici ne vaut validation de B2** tant que l'essai n'a pas été exécuté et
constaté.
