# Premier déploiement — procédure, sauvegardes, retour arrière

La mise en ligne du code de `master` sur le service Railway `sci4k`, à la
place de l'ancien code qui y tourne. Ce document est la liste à suivre ce
jour-là, puis à chaque mise en ligne : la §4 à la §7 se rejouent à
l'identique.

**Une seule stratégie de déploiement** : un conteneur sur Railway, mis en
ligne par `railway up` lancé à la main depuis `master`, après
`tools/verifier-avant-deploiement.sh`. Aucune poussée sur GitHub ne déploie
le site. La plateforme : `DEPLOIEMENT_RAILWAY.md`. Les branches et le
script : `BRANCHES_ET_DEPLOIEMENT.md`. Le lancement sur le domaine définitif,
plus tard : `MISE_EN_LIGNE.md`.

Chaque affirmation sur la production porte sa nature : **vérifié** (lu sur
Railway, dans son API ou ses journaux), **déduit** (conclu de faits
vérifiés), **non vérifiable** (hors de portée sans action à décider).

---

## 1. L'état de départ (relevé le 1er octobre 2026)

Projet `energetic-courtesy`, environnement `production`.

| Élément | État | Nature |
|---|---|---|
| Service `sci4k` | sans source : alimenté par `railway up` ; domaine `sci4k-production.up.railway.app` ; dernier déploiement le 23 septembre, **ancien code** (sans politique de contenu, `/mentions-legales.html` encore servi) | vérifié |
| Service `MySQL-1Luf` | **MySQL 9.4.0** (journal de démarrage) ; image épinglée sur `mysql:9.4.0` le 1er octobre, même empreinte qu'avant | vérifié |
| Mise à jour automatique de MySQL | **armée par Railway** pour la faille CVE-2026-21964 (gravité « HIGH ») vers `mysql:9`, soit 9.7.2 ; **suspendue jusqu'au 15 octobre 2026, 10 h 05 UTC** (§2) | vérifié |
| Service `sci4k-website` | **supprimé le 1er octobre** : reliquat relié au dépôt GitHub, sans variable, sans volume, sans usage | vérifié |
| Volume `sci4k-volume` | fichiers téléversés, 39 Mo sur 500, monté sur `sci4k` | vérifié |
| Volume `mysql-volume-gNQa` | données MySQL, 184 Mo sur 500, monté sur `MySQL-1Luf` | vérifié |
| Volume `mysql-volume` | 147 Mo, rattaché à aucun service, créé le 7 septembre à 19 h 25 — onze minutes avant celui de `MySQL-1Luf` | vérifié |
| Contenu de `mysql-volume` | un premier service MySQL, remplacé au bout de onze minutes ; 147 Mo est la taille d'un répertoire MySQL 9 tout juste initialisé : vraisemblablement vide de données du site | déduit |
| Plan Railway | **Hobby** | vérifié (API) |
| Sauvegardes Railway | **indisponibles** : le plan en autorise zéro (`maxBackupsCount: 0`) | vérifié (API) |
| Retour arrière Railway | disponible sur le déploiement en cours (`canRollback: true`) | vérifié (API) |
| Variables absentes | `SENTRY_*`, `MAIL_FROM_ADDRESS`, `PASSKEYS_USER_HANDLE_SECRET` ; `MAIL_MAILER=log` | vérifié |
| Contenu de la base | réglages SMTP, Analytics, tawk.to saisis dans le backoffice, passkeys enregistrées | non vérifiable |

Les limites du plan, lues dans l'API de Railway :

| Limite | Valeur | Ce qu'elle implique |
|---|---|---|
| Volumes | 3 par projet, 500 Mo chacun, sans agrandissement | les trois emplacements sont pris ; la base occupe 37 % du sien |
| Sauvegardes de volume | 0 | **la seule sauvegarde de la base est `mysqldump`** (§4) |
| Mémoire par conteneur | 1 Go | MySQL tourne à 380 Mo ; son tampon InnoDB est réglé à 1 Go (`--innodb-buffer-pool-size=1G`) : il ne doit jamais se remplir |
| Journaux | 7 jours | une panne se lit dans la semaine, ou se perd |
| Retour arrière | 72 h après le remplacement d'un déploiement | documentation de Railway, « Image retention policy » |
| Domaines personnalisés | 1 | suffisant pour le domaine définitif |

**Ce que le premier déploiement migrera** : une seule migration nouvelle,
`2026_09_29_120000_cree_les_executions_d_entretien` — elle crée une table, ne
modifie ni ne supprime rien. Les 46 migrations du dépôt ont chacune leur
retour arrière (`down()`).

## 2. Les bloquants, avant le premier déploiement

1. **Une clé SSH enregistrée auprès de Railway** — faite le 1er octobre
   2026 (`~/.ssh/id_ed25519`, sans passphrase, pour que les sauvegardes
   tournent sans saisie). Sans elle, aucune sauvegarde n'est possible : le
   plan n'en fait pas, et `mysqldump` passe par `railway ssh`. Sur un autre
   poste, une fois :

   ```bash
   ssh-keygen -t ed25519        # Entrée à chaque question
   railway ssh keys add
   ```

   À la première question de `ssh-keygen` (l'emplacement du fichier),
   répondre Entrée : un nom tapé là crée la clé privée dans le dossier
   courant — dans le dépôt, si c'est de là qu'on la lance.

2. **Une sauvegarde de la base, restaurée une fois** (§4, puis §6 point 8).
   Fait le 1er octobre 2026 : sauvegarde de la production (47 tables,
   archive intacte), restaurée dans un MySQL 9.4.0 local — données
   identiques à l'octet près, après réexport.
3. **La faille de MySQL 9.4.0, avant le 15 octobre.** Railway signale
   CVE-2026-21964, gravité « HIGH », et a programmé la montée vers 9.7.2 —
   la version que le projet supporte et teste. Elle est suspendue jusqu'au
   15 octobre, 10 h 05 UTC, parce qu'elle se serait faite sans sauvegarde. À
   cette date, si rien n'est décidé, elle repart au créneau suivant (samedi
   10 h – 24 h, dimanche 0 h – 18 h UTC). Le bon chemin : sauvegarder, puis
   faire cette montée de version volontairement, en surveillant le
   démarrage. Une nouvelle suspension (14 jours au plus) ne fait que la
   repousser.
4. **`PASSKEYS_USER_HANDLE_SECRET`** = la valeur **actuelle** de `APP_KEY`, à
   l'identique (voir `.env.production.example`). Les passkeys déjà créées
   continuent de fonctionner, et `APP_KEY` peut ensuite changer sans les
   détruire.
5. **Les règles GitHub** du § 13 de `BRANCHES_ET_DEPLOIEMENT.md`.

Recommandés avant le premier déploiement, bloquants avant le domaine
définitif :

- **le courrier** : `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`,
  `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` (une adresse du
  domaine de l'agence — vide, c'est `hello@example.com`). `MAIL_MAILER=log`
  n'envoie rien : ni accusé de contact, ni alerte de visite, ni
  réinitialisation de mot de passe. Identifiants dans les variables du
  service, jamais dans un fichier ;
- **Sentry** : `SENTRY_LARAVEL_DSN` et `SENTRY_ENVIRONMENT=essai` (puis
  `production` sur le domaine définitif). Le code est prêt et n'envoie
  aucune donnée personnelle ; sans DSN, personne n'est prévenu d'une erreur
  ni d'un entretien de nuit manqué. `SENTRY_TRACES_SAMPLE_RATE` reste à `0` ;
- **les réglages de `railway.json`**, à reporter dans l'écran avant le
  1er décembre 2026 (`DEPLOIEMENT_RAILWAY.md`, §1).

À décider, sans urgence : **`mysql-volume`** (147 Mo, détaché). Le lire
demande de le rattacher à un service MySQL temporaire ; le supprimer libère
le troisième emplacement de volume. Ni l'un ni l'autre n'a été fait.

## 3. La veille

- La CI de `master` est verte : `./tools/verifier-avant-deploiement.sh`
  répond « Tout est vert ».
- Les journaux de `sci4k` et de `MySQL-1Luf` sont lus : rien d'anormal avant
  de commencer.
- Un créneau creux est choisi : le service a un volume, Railway arrête
  l'ancien conteneur avant de démarrer le nouveau — une courte coupure est
  inévitable.

## 4. Sauvegarder, avant chaque mise en ligne

**Les sauvegardes ne se rangent JAMAIS dans le dossier du dépôt** : elles y
partiraient avec le prochain `railway up`, ou y seraient commitées. Un dossier
à part, hors du dépôt, par exemple `~/sauvegardes-sci4k/`.

### 4.1 La base, par `mysqldump`

C'est la seule sauvegarde possible sur ce plan. Elle demande la clé SSH du
§2.

```bash
mkdir -p ~/sauvegardes-sci4k && cd ~/sauvegardes-sci4k
horodatage=$(date +%Y%m%d-%H%M)

railway ssh --service MySQL-1Luf -- sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction --routines --triggers --events --hex-blob --set-gtid-purged=OFF --databases railway | gzip | base64 -w0' > "base-$horodatage.b64"

base64 -di "base-$horodatage.b64" > "base-$horodatage.sql.gz"
gzip -t "base-$horodatage.sql.gz" && echo "archive intacte"
zcat "base-$horodatage.sql.gz" | tail -1      # doit afficher « -- Dump completed on … »
```

Pourquoi ainsi :

- le mot de passe ne quitte pas le conteneur MySQL : `$MYSQL_ROOT_PASSWORD`
  y est lu par le shell distant, entre apostrophes ; `MYSQL_PWD` le tient
  hors de la ligne de commande ;
- `--single-transaction` : une copie cohérente sans bloquer le site ;
- `gzip | base64` : le transport par `railway ssh` peut passer par un
  pseudo-terminal, qui altérerait un flux binaire ; `base64 -di` ignore les
  retours chariot qu'il ajouterait. `gzip -t` et la ligne finale prouvent que
  rien n'est tronqué.

**Éprouvé le 30 septembre 2026** sur un poste, contre l'image `mysql:9.4`
(empreinte identique à la production), le schéma réel migré et semé, avec
`docker exec` à la place de `railway ssh` : archive intacte, restauration
dans une base neuve, `CHECKSUM TABLE` identique sur les 48 tables, site servi
depuis la base restaurée.

**Faite en production le 1er octobre 2026** : `base-20261001-1039.sql.gz`,
85 Ko, 47 tables, archive intacte, « Dump completed » en dernière ligne. Le
transport par `railway ssh` est donc éprouvé.

**Restaurée le même jour** dans un MySQL 9.4.0 local et jetable : rechargée
sans erreur, puis réexportée. Les 38 lignes de données ont la même empreinte
dans les deux exports ; le schéma aussi, à une mention près — ce serveur
écrit `CHARACTER SET utf8mb4` en toutes lettres devant la même collation.
L'image du site a ensuite démarré sur cette copie, en **répétition générale
du premier déploiement** : la seule migration nouvelle a tourné (49 ms), les
pages publiques ont répondu, les 6 biens étaient là. Les conteneurs, et avec
eux la copie des données, ont été supprimés aussitôt.

### 4.2 Les fichiers téléversés

```bash
MSYS_NO_PATHCONV=1 railway volume files --volume sci4k-volume download / "fichiers-$horodatage"
```

Même clé SSH. `MSYS_NO_PATHCONV=1` ne sert que sous Git Bash, sur Windows :
sans lui, le `/` devient `C:/Program Files/Git/` avant d'atteindre Railway,
et la commande échoue.

**Faite le 1er octobre 2026** : 23 fichiers, 4,4 Mo, le compte exact du
volume relevé dans le conteneur (`find`, `du`). Les 39 Mo qu'affiche Railway
pour ce volume sont ceux du système de fichiers, pas des fichiers. Les
fichiers ne sont pas modifiés par un déploiement ; les sauvegarder protège
d'une perte du volume, pas d'une mise en ligne ratée.

### 4.3 Ce que Railway ne fait pas sur ce plan

Ni sauvegarde planifiée, ni sauvegarde manuelle, ni sauvegarde automatique
avant une mise à jour d'image : le plan en autorise zéro (vérifié dans l'API
le 1er octobre). L'onglet *Backups* d'un service ne peut donc rien créer. Un
plan supérieur les ouvrirait ; changer de plan est une décision de
facturation.

## 5. Mettre en ligne

```bash
git switch master && git pull
./tools/verifier-avant-deploiement.sh && railway up --service sci4k
```

Puis suivre les journaux du déploiement (`railway logs --service sci4k`, ou
l'écran). Dans l'ordre, on doit lire :

```
== Demarrage de SCI4K (utilisateur www-data, uid 33) ==
== Migrations ==
  2026_09_29_120000_cree_les_executions_d_entretien ........ DONE
== Base de donnees ==
MySQL 9.4.0 : le projet supporte et teste MySQL 9.7. …
AVERTISSEMENT : version de MySQL non supportee (voir ci-dessus).
== Caches ==
== Planificateur ==
== Pret ==
```

L'avertissement sur MySQL 9.4.0 est **attendu** tant que la base n'a pas été
montée en 9.7. Tout autre `AVERTISSEMENT` ou `ERREUR` arrête la procédure :
§7.

Le conteneur **refuse de démarrer** — et le déploiement échoue, l'ancien
restant en ligne — si `APP_KEY` est vide ou si `APP_DEBUG` vaut autre chose
que `false`.

## 6. Vérifier

Sur l'adresse du service :

1. `/up` répond 200.
2. Accueil, `/en`, `/biens`, une fiche, `/contact`, `/faq`, une actualité :
   200, visuels présents.
3. Les en-têtes d'une page portent `Content-Security-Policy` (avec un
   `nonce-`), `X-Frame-Options: DENY`, `Strict-Transport-Security`.
4. La console du navigateur ne signale aucune violation de la politique de
   contenu venant du site (une extension de sécurité peut en ajouter : elles
   ne portent pas l'origine du site).
5. `/storage/<n'importe quoi>.php` répond 404 sans rien exécuter.
6. Connexion au backoffice ; le tableau de bord affiche le panneau
   « Entretien automatique » (« pas encore de passe » le premier jour).
7. Le lendemain : les deux tâches de nuit apparaissent en vert au panneau.
8. La sauvegarde de la §4.1 se restaure (au premier déploiement, et après
   tout changement de version de MySQL) :

   ```bash
   docker run -d --name restauration -e MYSQL_ROOT_PASSWORD=essai-local mysql:9.4.0
   # attendre « ready for connections » dans « docker logs restauration »
   zcat "base-$horodatage.sql.gz" | docker exec -i restauration sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot'
   docker exec restauration sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -e "SELECT COUNT(*) FROM railway.migrations; SELECT COUNT(*) FROM railway.biens"'
   docker rm -f restauration
   ```

   Le mot de passe est local, inventé pour l'essai ; la copie contient des
   données réelles (messages de contact, comptes) : supprimer le conteneur et
   ne garder l'archive que dans le dossier des sauvegardes.

## 7. Revenir en arrière

Trois niveaux, du plus rapide au plus lourd. **Aucun ne touche la base ni les
fichiers** : revenir au code précédent ne défait pas une migration.

### 7.1 Le retour arrière de Railway (le code)

*sci4k* → *Deployments* → les trois points du déploiement précédent →
*Rollback*. Railway remet **l'image et les variables** de ce déploiement,
sans reconstruire : une à deux minutes.

Disponible pendant 72 h après le remplacement du déploiement (plan Hobby).
Passé ce délai, l'option disparaît de l'écran. Vérifié le 1er octobre : le
déploiement en cours (23 septembre) porte `canRollback: true` dans l'API ;
les précédents, remplacés depuis plus de 72 h, ne le portent plus.

**Au premier déploiement, il est sans risque pour la base** : la seule
migration nouvelle crée une table que l'ancien code ignore. Pour une mise en
ligne future qui modifie ou supprime des colonnes, l'ancien code ne
fonctionnera plus sur la base migrée : le retour arrière du code exige alors
celui de la base (§7.3).

### 7.2 Le retour par commit (quand Railway ne le permet plus)

Pas de raccourci : le correctif suit le flux, comme tout changement.

```bash
git switch dev && git pull
git revert -m 1 <commit de fusion fautif>     # ou le commit lui-même
git push origin dev
# demande de fusion dev -> preprod, puis preprod -> master, CI verte
git switch master && git pull
./tools/verifier-avant-deploiement.sh && railway up --service sci4k
```

`railway up` d'un ancien commit, hors de `master`, est refusé par le script :
c'est voulu. Un `git revert` produit un nouveau commit, que la CI valide
comme les autres.

### 7.3 Restaurer la base (destructif)

**Remplace les données en ligne par celles de la sauvegarde** : tout ce qui a
été saisi depuis — messages de contact, demandes de visite, modifications du
backoffice — est perdu. À décider explicitement, jamais par réflexe.

Sur ce plan, la seule source est l'archive de la §4.1 : la rejouer dans
`MySQL-1Luf`, comme au point 8 de la §6 mais vers la base en ligne, l'archive
passée en entrée de `railway ssh`. Non éprouvé en production. Sauvegarder
l'état courant avant, même dégradé.

## 8. Points d'attention

- **Une seule réplique** pour `sci4k` : le volume l'impose, et le
  planificateur intégré y compte.
- **`APP_KEY` ne change pas** avant le point 4 de la §2. Le domaine définitif
  invalidera de toute façon les passkeys créées sur l'adresse d'essai.
- **Une commande lancée à la main** dans le conteneur doit l'être sous
  `www-data` (`DEPLOIEMENT_RAILWAY.md`, §4).
- **`CSP_OBSERVATION=true`**, posé un temps pour vérifier l'activation du chat
  ou des statistiques, se retire ensuite : il désarme la politique de contenu.
- **`railway domain` sans sous-commande CRÉE un domaine public.** Pour
  consulter : `railway domain list`. Plus généralement, lire le `--help` d'une
  commande Railway avant de la croire en lecture seule.
- **`railway autoupdate`** règle les mises à jour de la CLI elle-même, pas
  celles d'une image : la politique de MySQL se lit dans la configuration de
  l'environnement.
