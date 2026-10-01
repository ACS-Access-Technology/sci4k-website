# Railway : la plateforme du site

**Le site se déploie en conteneur sur Railway, par `railway up` lancé à la
main depuis `master`.** C'est la seule stratégie de déploiement du projet :
l'environnement d'essai actuel et le futur site définitif la suivent tous
deux. Ce document décrit la plateforme ; la procédure d'une mise en ligne,
sauvegardes et retour arrière compris, est dans `PREMIER_DEPLOIEMENT.md`.

`MISE_EN_LIGNE.md` garde, en plan de repli, la séquence d'un hébergement
classique — pour le jour où la plateforme changerait. Elle ne sert pas
aujourd'hui.

**Le plan Railway compte.** Le projet est sur le plan **Hobby** (lu dans
l'API le 1er octobre 2026), avec des limites serrées : trois volumes de
500 Mo, 1 Go de mémoire par conteneur, journaux gardés sept jours, retour
arrière possible 72 h — et **aucune sauvegarde de volume**. La seule
sauvegarde de la base est donc `mysqldump`. Le tableau complet et ses
conséquences : `PREMIER_DEPLOIEMENT.md`, §1.

## Pourquoi Railway plutôt que Vercel

Railway fournit **MySQL et un disque persistant**, et exécute un conteneur qui
tourne en continu — ce qui permet au planificateur de tourner à côté du
serveur (voir §5). Le projet s'y déploie donc tel quel : aucune bascule vers un
stockage objet, aucune route de cron, aucun point d'entrée particulier.

Tout ce qui a été écrit pour Vercel — `vercel.json`, `app-laravel/api/`,
`STOCKAGE_PUBLIC_DISTANT` — reste dans le dépôt et **demeure inerte** : rien de
cela ne s'active sans les variables correspondantes.

## Ce que fait l'image

Le `Dockerfile` est à la racine, et n'a rien de propre à Railway : la même
image tourne sur Fly.io, Render, Koyeb ou un VPS. Une plateforme qui déçoit se
remplace alors sans rien réécrire.

Il construit en deux temps. Node produit les ressources front dans une première
étape, puis **disparaît** : le garder ajouterait une centaine de mégaoctets et
une surface d'attaque à une image qui n'en a plus besoin. La seconde étape
installe les dépendances PHP de production, copie le code, et dépose les
ressources du site statique.

FrankenPHP sert l'application : un processus, une configuration, ni nginx ni
php-fpm à assembler.

## Les étapes

### 1. Créer le projet et le service

Sur [railway.com](https://railway.com) : *New Project* → *Empty Project*, puis
*New* → *Empty Service*, nommé `sci4k`.

**Le service n'est relié à AUCUN dépôt GitHub.** Son code n'arrive que par
`railway up`, lancé après `tools/verifier-avant-deploiement.sh` (voir « Chaque
mise en ligne »). Un service relié à GitHub déploierait à chaque poussée sur
la branche suivie, sans passer par ce contrôle : une modification qui n'a pas
suivi `dev` → `preprod` → `master`, ou dont la CI a échoué, partirait en ligne.

`railway up` téléverse le dossier de travail ; Railway y détecte le
`Dockerfile` et le `railway.json`. **Ne pas définir de racine** : la
construction a besoin de `maquettes-frontoffice/`, qui vit à la racine du
dépôt.

État vérifié le 1er octobre 2026 (projet `energetic-courtesy`, environnement
`production`) : le service `sci4k` n'a pas de source — conforme. Un second
service, `sci4k-website`, relié au dépôt GitHub (branche `master`), reliquat
de la première mise en place sans variable ni volume, a été **supprimé** ce
jour-là. Il ne doit pas être recréé.

#### `railway.json` cesse d'être lu le 1er décembre 2026

Railway l'annonce : « Existing Config as Code files stop being read on
2026-12-01 (hard cutoff) ». Passé cette date, les réglages que porte le fichier
disparaissent sans bruit. **Ils se reportent à la main** sur le service web,
**avant** de retirer le fichier — tant qu'il est là, il prime sur les réglages
de l'écran :

| Réglage | Où | Valeur | Défaut de Railway sans le fichier |
|---|---|---|---|
| Chemin du healthcheck | *Settings* du service, champ du healthcheck | `/up` | aucun healthcheck |
| Délai du healthcheck | variable `RAILWAY_HEALTHCHECK_TIMEOUT_SEC` | `120` | 300 secondes |
| Politique de redémarrage | *Settings* du service, liste déroulante | `On Failure` | `On Failure` |
| Nombre maximal de redémarrages | *Settings* du service, avec la politique | `3` | 10 |

Pourquoi ces valeurs : `/up` ne répond 200 qu'une fois l'application démarrée,
migrations et caches compris — un déploiement ne reçoit pas le trafic avant ;
120 secondes leur laissent le temps ; au-delà de trois redémarrages, le service
reste arrêté plutôt que de masquer une panne par une boucle.

Le reste ne demande rien : la construction par le `Dockerfile` est détectée
d'elle-même, et la commande de démarrage est déjà celle de l'image
(`CMD ["/usr/local/bin/demarrer"]`). Une fois les quatre réglages saisis et
vérifiés sur un déploiement, `railway.json` peut être retiré du dépôt.

### 2. Ajouter MySQL

*New* → *Database* → *Add MySQL*. Railway injecte alors les variables de
connexion dans le projet.

**Épingler l'image sur une version exacte**, jamais sur `mysql:9` ni
`mysql:latest` : une étiquette flottante fait monter la base de version au
premier redéploiement venu, sans que personne l'ait décidé. Et désactiver les
mises à jour automatiques de l'image (*Settings* → *Source* → *Configure Auto
Updates*).

Le projet supporte et teste **MySQL 9.7** (LTS). Au démarrage, le conteneur de
l'application compare la version jointe à celle-ci et écrit un
**AVERTISSEMENT** dans les journaux en cas d'écart ; il démarre quand même.

État vérifié le 1er octobre 2026 sur le service `MySQL-1Luf`, après la
migration du jour :

- **MySQL 9.7.2** (LTS), journal de démarrage : « Version: '9.7.2' » ;
- **image épinglée sur `mysql:9.7.2`**, empreinte `sha256:e2bde46db656…`
  (`railway service source connect --image mysql:9.7.2 --service MySQL-1Luf`),
  la même que celle de la CI (`verification.yml`) ;
- **mises à jour automatiques de l'image désactivées** (`type: disabled`). Un
  avis de faille armé par Railway (CVE-2026-21964, sur 9.4.0, vers
  `mysql:9`) a été écarté après la migration, qui en était la correction
  (mutation `serviceInstanceVulnRemediationDismiss`). La politique se lit dans
  la configuration de l'environnement (`railway api`, champ `config` —
  filtrer : elle contient les variables), pas dans `railway autoupdate`, qui
  ne concerne que la CLI.

La migration elle-même — 9.4.0 vers 9.7.2, sauvegarde, répétition,
vérifications — est consignée dans `PREMIER_DEPLOIEMENT.md`, §9.

**Ne jamais utiliser *Rollback* sur le service MySQL** pour revenir à 9.4 :
Railway le propose encore, mais MySQL ne sait pas redescendre d'une version
sur des données déjà migrées. Le retour en arrière passe par la sauvegarde
(`PREMIER_DEPLOIEMENT.md`, §7.4).

### 3. Poser les variables

Dans les *Variables* du service web :

```
APP_NAME=SCI4K
APP_ENV=production
APP_DEBUG=false              # toute autre valeur : le conteneur refuse de démarrer
APP_KEY=                     # php artisan key:generate --show, puis recopier
APP_URL=https://…            # l'adresse que Railway attribue, puis le domaine

# Le secret des passkeys. Vide : APP_KEY — et changer APP_KEY invalide alors
# toutes les passkeys. Y poser la valeur ACTUELLE de APP_KEY les en détache
# sans rien casser. Détail dans .env.production.example.
PASSKEYS_USER_HANDLE_SECRET=

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_STACK=stderr             # Railway collecte la sortie d'erreur
LOG_LEVEL=warning            # « debug » écrirait chaque requête SQL, valeurs comprises

# Railway termine le TLS devant l'application. Sans cette ligne, isSecure()
# répond faux : les liens repartent en http, le cookie de session n'est jamais
# marqué Secure, et la limitation de débit compte toutes les visites sur une
# seule adresse — les quatre formulaires publics fermeraient au cinquième envoi.
TRUSTED_PROXIES=*

# Le courrier. MAIL_MAILER=log n'envoie RIEN — il écrit les courriels dans le
# journal : ce n'est pas une configuration de production. Les identifiants
# viennent du fournisseur SMTP de l'agence ; ils se posent ici, jamais dans
# un fichier du dépôt. MAIL_FROM_ADDRESS vide : hello@example.com.
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=           # une adresse du domaine de l'agence
MAIL_FROM_NAME=SCI4K

# Le rapport d'erreurs. Sans DSN, rien ne part, et personne n'est prévenu
# d'une erreur ni d'un entretien de nuit manqué. SENTRY_ENVIRONMENT sépare
# l'essai du site définitif (sans lui : APP_ENV, donc « production » pour
# l'essai aussi). La version se renseigne seule : « railway up » ne transmet
# pas de commit, Sentry reçoit l'identifiant du déploiement. Ne pas poser
# SENTRY_TRACES_SAMPLE_RATE au-delà de 0 sans regarder le quota.
# SENTRY_LARAVEL_DSN=
# SENTRY_ENVIRONMENT=essai

# Facultatif. La politique de sécurité du contenu est BLOQUANTE par défaut.
# true l'envoie en « Report-Only » : la console du navigateur liste ce qui
# serait bloqué, sans rien bloquer. Utile pour vérifier le chat ou les
# statistiques la première fois qu'on les active. Voir PolitiqueDeContenu.php.
# CSP_OBSERVATION=true
```

La syntaxe `${{MySQL.MYSQLHOST}}` est celle de Railway : elle référence le
service MySQL sans recopier les identifiants.

### 4. Le volume des fichiers téléversés

*Settings* → *Volumes* → monter sur `/app/app-laravel/storage/app/public`.

**Sans volume, les images ajoutées depuis le backoffice disparaissent au
redéploiement suivant.** Le conteneur démarre quand même — c'est prévu, le
temps d'un essai — mais rien ne signalera la perte.

**Le site tourne sous `www-data`, pas sous root.** Railway monte le volume au
nom de root : le script de démarrage le rend à `www-data` (seulement les
fichiers qui ne lui appartiennent pas encore), puis abandonne ses privilèges
avant toute commande du site. Ne pas poser `RAILWAY_RUN_UID` : le conteneur
démarre déjà sous root, et c'est le script qui en sort. Si le volume ne peut
pas être rendu, les journaux de démarrage le disent :
`AVERTISSEMENT : storage/app/public n'est pas accessible en ecriture`.

**Une commande lancée à la main depuis la console Railway tourne, elle, sous
root.** Si elle crée un fichier dans `storage/` — le premier journal du jour,
une entrée de cache —, ce fichier appartient à root et le site ne pourra plus
y écrire jusqu'au prochain redémarrage. La lancer sous le même utilisateur que
le site :

```bash
cd /app/app-laravel && setpriv --reuid=www-data --regid=www-data --init-groups php artisan frequentation:agreger
```

### 5. L'entretien de nuit — rien à créer

Deux tâches tournent chaque nuit : l'agrégation de la fréquentation à 3 h 10,
la purge du journal d'activité à 3 h 40 (heure UTC, celle d'Abidjan). Sans
elles, les deux tables croissent sans borne.

**Elles tournent dans le conteneur du site, sans service à ajouter.** Le
script de démarrage lance le planificateur de Laravel (`schedule:work`) à côté
du serveur, et le relance s'il s'arrête. Chaque passe laisse une ligne dans les
journaux du service :

```
Running ['artisan' journal:purger] ... DONE
```

Et un arrêt du planificateur s'y lit aussi :
`AVERTISSEMENT : le planificateur s'est arrete (code …). Relance dans 10 s.`

**Ne pas créer de service « Cron Job » Railway en plus.** Une ancienne version
de ce document en décrivait un, appelant `schedule:run` à `10 3 * * *` : il ne
lançait jamais la purge de 3 h 40, et pouvait ne rien lancer du tout —
`schedule:run` n'exécute que ce qui est dû à la minute même, et Railway écrit
que ses crons « can vary by a few minutes ». **S'il a été créé, le supprimer
ou le mettre en pause.** Les tâches supportent d'être rejouées, mais deux
déclencheurs n'apportent rien.

**Une seule réplique** pour ce service. Plusieurs ne feraient pas tourner les
tâches en double — elles sont verrouillées par `onOneServer()`, verrou partagé
par la base — mais c'est le jour de passer à un service dédié, en posant
`PLANIFICATEUR_INTEGRE=false` sur le service web.

**Savoir si l'entretien tourne :**

- le tableau de bord de l'administration porte un panneau « Entretien
  automatique » : la dernière passe de chaque tâche, en alerte au-delà de
  26 h sans réussite, ou si la dernière tentative a échoué ;
- avec `SENTRY_LARAVEL_DSN`, chaque passe envoie un signal à Sentry au début
  et à la fin (moniteurs `sci4k-frequentation-agreger` et
  `sci4k-journal-purger`, créés d'eux-mêmes au premier signal). Sentry prévient
  si une passe manque ou échoue. **Sans DSN, cette surveillance est inactive** :
  seul reste le panneau du tableau de bord.

## Les migrations

Elles tournent **au démarrage du conteneur**, faute de pouvoir tourner ailleurs :
la construction de l'image n'atteint pas la base.

C'est acceptable pour un essai, et discutable en production — deux instances
qui démarrent ensemble les lanceraient en même temps. Laravel pose un verrou,
mais le jour où ce site tournera sur plusieurs instances, il faudra les sortir
de là. `MIGRER_AU_DEMARRAGE=false` les désactive.

## Chaque mise en ligne

Depuis un poste, sur `master` à jour, jamais depuis une autre branche :

```bash
git switch master && git pull
./tools/verifier-avant-deploiement.sh && railway up --service sci4k
```

Le script refuse si le dossier n'est pas exactement `master` tel que la CI l'a
validé — branche, fichiers modifiés ou non suivis, écart avec `origin/master`,
checks absents ou en échec. Détail et conduite à tenir :
`docs/BRANCHES_ET_DEPLOIEMENT.md`.

**Avant chaque mise en ligne, sauvegarder** la base et les fichiers
téléversés ; **après**, vérifier. La procédure complète — sauvegardes,
contrôles, retour arrière — est dans `PREMIER_DEPLOIEMENT.md`.

**Aucune poussée sur GitHub ne déploie quoi que ce soit** : le service `sci4k`
n'a pas de source, et le seul service relié au dépôt, `sci4k-website`, a été
supprimé le 1er octobre 2026 (voir §1).

## Vérifier que ça marche

1. `https://…/up` doit répondre 200 — c'est la sonde de Railway.
2. La page d'accueil doit afficher les visuels : sinon `sync-frontoffice.sh`
   n'a pas tourné, ou `public/build` manque.
3. Se connecter au backoffice, **téléverser une image**, puis redéployer :
   elle doit survivre. Si elle disparaît, le volume n'est pas monté.
4. `php artisan frequentation:agreger` depuis la console Railway doit répondre
   sans erreur — lancée sous `www-data`, voir le volume ci-dessus.
5. Les journaux de démarrage doivent annoncer
   `== Demarrage de SCI4K (utilisateur www-data, uid 33) ==`, et aucun
   avertissement d'écriture.

## Construire l'image en local

```bash
docker build -t sci4k .
```

Rien ne dépend de Railway dans cette commande : c'est le meilleur moyen de
vérifier une modification avant de la pousser.
