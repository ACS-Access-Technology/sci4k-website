# Déploiement progressif et revue finale — préparé le 13 octobre 2026

**Rien de ce document n'est exécuté.** Chaque fusion, chaque `railway up` et
chaque variable Railway attend une autorisation écrite. Le circuit
`dev` → `preprod` → `master` est celui de `BRANCHES_ET_DEPLOIEMENT.md`.

## 1. Revue finale des trois demandes de fusion

| PR | Branche | Commits | Fichiers | Mergeable | CI constatée sur le dernier commit |
|---|---|---|---|---|---|
| #14 | `feat/sauvegardes-externes` | `99f23db`, `7c7b35a`, `db19a6c` | 19 | oui, propre | verte (Pint, PHPStan, SQLite, MySQL 9.7.2, Sonar, Semgrep) |
| #15 | `fix/seo-ux` | `d6689bc`, `2495e7e`, `3e76055` (fusion de `fix/adresses-obsoletes`) | 20 | oui, propre | verte (idem) |
| #16 | `docs/etat-post-lancement` | `92c3871`, `8f95021` | 5 | oui, propre | verte sur `8f95021` (contrôlé) |

- **Aucun fichier commun** entre les trois PR ; fusion à blanc de chacune dans
  `dev`, puis des trois ensemble (dans l'ordre #15, #14, #16) : **sans conflit**.
- **Dépendances** : aucune entre elles. #14 ajoute `league/flysystem-aws-s3-v3` ;
  la CI dépend désormais de l'extension `sodium`. #15 contient la migration
  `2026_09_01_190000_remplit_les_pages_legales` (ne remplit que des pages légales
  vides, ne crée ni ne supprime de table). #16 ne touche que des documents.
- **Effets en production après déploiement** : #15 change le comportement
  visible (redirections 301 de `/index.php/...`, actualités vides, canoniques).
  #14 est **inerte** : rien n'est planifié tant que `SAUVEGARDE_ACTIVE` est
  absent. #16 : aucun.
- **Rien dans ces PR** ne modifie les comptes, rôles, authentification,
  témoignages, chiffres, `robots.txt`, noindex, DNS ou messagerie.
- Les mentions légales **ne sont pas publiées** par #15 : la commande
  `mentions-legales:publier` existe mais n'est pas lancée.

**Ordre proposé** : #15, puis #14, puis #16 dans `dev` (seule #15 change le
comportement du site ; si elle pose problème sur `preprod`, les deux autres
restent déployables seules).

## 2. Tests : réussis, et manquants

| Contrôle | État |
|---|---|
| Suite complète locale (SQLite) de `fix/seo-ux` : 1 499 réussis | **fait** |
| Suite complète locale de `feat/sauvegardes-externes` : 1 508 réussis, 12 ignorés | **fait** |
| CI GitHub, SQLite + MySQL 9.7.2, des trois PR | **fait** (constatée) |
| Sauvegarde et restauration de bout en bout, S3 local (SeaweedFS), MySQL 9.7.2 jetable : tables, lignes et fichiers identiques | **fait en local** |
| Extension `sodium` dans l'image de base `dunglas/frankenphp:php8.3` | **vérifiée localement** (image tirée aujourd'hui) |
| Extension `sodium`, mémoire et réseau **dans le conteneur Railway** | **NON** |
| Accès réel à Backblaze B2 : écriture, lecture, refus de suppression | **NON** (aucun compte) |
| Compatibilité du SDK avec les en-têtes de B2 | **NON** |
| Règles de cycle de vie de B2 (14 / 56 / 1 jour) | **NON** |
| Alerte externe : fausse panne puis alerte reçue | **NON** |
| Exécution à 03:20 UTC de `sauvegarde:externe` | **NON** (le mécanisme est prouvé par les tâches de 03:10 et 03:40) |
| Restauration réelle isolée depuis B2 | **NON** |
| Correctifs de #15 vus sur `preprod` puis en ligne | **NON** |

## 3. Procédure Backblaze B2

Détaillée, étape par étape et avec chaque vérification marquée ⚠, dans
`SAUVEGARDES.md` (branche de #14), §4 à §9 : bucket, droits minimaux, règles de
cycle de vie, clé de chiffrement, surveillance, restauration isolée,
variables et ordre d'activation. Points à ne pas perdre :

1. Bucket **d'essai** séparé d'abord, avec clé *listFiles / readFiles / writeFiles*
   sans *deleteFiles* ; `php artisan sauvegarde:verifier-config` doit afficher
   « Suppression : REFUSÉE » ; contrôle croisé dans la console B2.
2. Si B2 ne permet pas une clé sans suppression, **ne pas continuer** : la
   garantie « une compromission de Railway ne détruit pas les sauvegardes »
   tombe, et le choix du fournisseur est à reprendre.
3. Clé de chiffrement `SAUVEGARDE_CLE` : générée une fois, conservée **hors de
   Railway** (gestionnaire de mots de passe de l'organisation, deux personnes,
   copie papier). La perdre rend toutes les sauvegardes illisibles.
4. Activation en dernier : `SAUVEGARDE_ACTIVE=true`, après un essai réel
   restauré depuis le conteneur Railway.

## 4. Déploiement progressif

### Étape 0 — sauvegarde préalable (avant toute fusion vers `master`)

Procédure de `PREMIER_DEPLOIEMENT.md` : export de la base et copie des fichiers
dans `~/sauvegardes-sci4k`, avec empreinte. **Vérifier** : fichier non vide,
empreinte enregistrée, `-- lignes:` présentes en fin de fichier, restauration
à blanc dans un MySQL 9.7.2 jetable (même méthode que le §7 de `SAUVEGARDES.md`).
Noter le déploiement Railway courant (`b3e087cb`) pour le retour arrière.

### Étape 1 — fusions dans `dev`

Fusionner #15, #14, #16 (commits de fusion). Contrôle : CI de `dev` verte.
Retour arrière : `git revert -m 1` du commit de fusion concerné ; aucune
donnée touchée.

### Étape 2 — `dev` → `preprod`

PR, CI verte exigée. Contrôle : `preprod` = `dev`. Aucun déploiement ne part de
cette branche (il n'y a pas d'instance de préproduction) : **les tests de
fumée de #15 se font donc en local, sur une copie, avant l'étape 4**.

### Étape 3 — `preprod` → `master`

PR, CI verte exigée. Contrôle : `./tools/verifier-flux-des-branches.sh`.
Rien n'est encore en ligne.

### Étape 4 — mise en ligne

Sur `master` propre : `./tools/verifier-avant-deploiement.sh`, puis
`railway up --service sci4k`. Les migrations s'exécutent au démarrage.
Attendre le déploiement « SUCCESS » dans Railway.

### Étape 5 — tests de fumée (lecture seule)

| Contrôle | Attendu |
|---|---|
| `GET /` , `/biens`, `/contact`, `/en` | 200 |
| `GET /index.php/biens` | 301 vers `/biens` ; `GET /index.php` : 301 vers `/` |
| `GET /actualites` sans article | 200, message vide, pas de filtre vide |
| `GET /robots.txt` | `Disallow: /` (inchangé) ; `<meta name="robots">` : `noindex, nofollow` |
| `GET /mentions-legales` | page d'attente, `noindex`, absente du plan du site |
| `GET /sitemap.xml` | 200, sans la page d'attente |
| Formulaire de contact avec honeypot rempli | rejeté, aucun courriel |
| `railway logs` | pas d'erreur 5xx ; tâches de 03:10 et 03:40 le lendemain |
| `php artisan sauvegarde:externe` | **non lancée** : `SAUVEGARDE_ACTIVE` absent, rien de planifié |
| Affichage 375 / 768 / 1280 px | chiffres clés lisibles à 768 px, zones tactiles ≥ 44 px |

### Retour arrière

1. Railway → *Rollback* vers `b3e087cb` (possible 72 h).
2. Si la migration de #15 a rempli des pages légales : elle n'a touché que des
   pages vides ; aucune restauration de base n'est nécessaire. Restaurer la
   base uniquement en dernier recours, depuis la sauvegarde de l'étape 0,
   après avoir arrêté les écritures, et avec votre accord.
3. Côté Git : `git revert -m 1` des fusions dans `master` par PR.

## 5. Autorisations nécessaires, une par une

1. Fusion de #15, #14, #16 dans `dev`.
2. PR `dev` → `preprod`, puis `preprod` → `master`.
3. Sauvegarde préalable de la production (lecture seule, mais elle accède aux données).
4. `railway up --service sci4k`.
5. Compte B2 (éventuellement payant), bucket, clés, règles de cycle de vie.
6. Variables Railway de la sauvegarde, puis `SAUVEGARDE_ACTIVE`.
7. `mentions-legales:publier`, après décision sur l'agrément.
8. Toute modification de la fiche `audray@sci4k.com`.

## 6. Résultats des tests de fumée sur `dev` (13 octobre 2026)

**Environnement** : image construite par le `Dockerfile` du dépôt à partir de
`3e157ee` (donc le même chemin de construction que la mise en ligne), exécutée
en local sur une **copie jetable** de la base de production (MySQL 9.7.2,
base `smoke_copie`), sans courrier (`MAIL_MAILER=log`), sans planificateur,
avec une clé d'application factice. Rien n'a été envoyé ni écrit en
production.

| Contrôle | Résultat constaté |
|---|---|
| Démarrage du conteneur, `migrate --force` sur la copie de production | « Nothing to migrate » : **aucune migration à jouer** |
| `base:verifier-version` | MySQL 9.7.2 supporté |
| 20 pages publiques | toutes en 200, `/plan-du-site` en 404 (adresse inexistante, normal) |
| `/index.php`, `/index.php/biens`, `/index.php/en/biens`, `/index.php/biens/villa-f6`, `/index.php/contact?x=1` | **301** vers l'adresse sans le point d'entrée |
| `/index.php/` (barre finale) | **200 : doublon de l'accueil** → corrigé sur `fix/finitions-cibles-et-index-php` (non fusionné) |
| Canoniques | exactement une par page, vers l'adresse propre ; `hreflang` présent |
| `robots.txt` et balise robots | `Disallow: /` et `noindex, nofollow` : **indexation toujours bloquée** |
| `/mentions-legales` (non publiée en base) | page d'attente, `X-Robots-Tag: noindex`, sans canonique ni `hreflang`, **absente du plan du site** |
| `/politique-confidentialite` (publiée) | canonique, `hreflang`, présente dans le plan du site |
| `/actualites` sans article | 200, message « Aucune actualité n'est publiée pour le moment », aucun filtre ni carte |
| Adresses visibles sur le site | `info@acsgroupe.ci` partout ; `nom@exemple.ci` (exemple de formulaire) ; **`audray@sci4k.com` : 2 occurrences**, donnée de la fiche équipe, inchangée |
| Anciennes adresses (`contact@sci4k.com`, `sci4k@sci4k.com`) | aucune |
| Débordement horizontal à 375, 768 et 1280 px | **aucun**, sur 13 pages |
| Zones tactiles < 24 px à 375 et 768 px | accueil, présentation, services, actualités, FAQ, contact, fiche bien : **0** |

**Limites de cet essai** : les images téléversées (`/storage/...`) répondent 404
parce que le volume de fichiers n'existe pas dans le conteneur d'essai ; ces
404 ne sont pas un défaut du code. Le formulaire de contact n'a pas été soumis.
Les affichages ont été mesurés au navigateur (Edge piloté), pas relus à l'œil
sur tous les écrans.

### Trois constats à connaître

1. **Les listes déroulantes de `/biens`** ne mesurent que 17 à 19 px de haut dans
   une carte de 58 px, à 375, 768 et 1280 px : seule cette bande ouvre le menu.
   C'est sous le seuil de 24 px de WCAG 2.2 (critère 2.5.8). Correctif prêt sur
   `fix/finitions-cibles-et-index-php` (zone portée à 44–47 px, mise en page
   décalée de 2 px au plus à 375 px), vérifié en conteneur ; **non fusionné**.
2. **Pages d'attente et 404** : les liens du pied de page et du menu mesurent
   16 à 19 px de haut à 375 px (pages statiques hors Blade). Les pages légales
   publiées en base ne sont pas concernées. Non corrigé.
3. **Sous 44 px** : entre 16 et 30 cibles par page restent sous 44 px (liens
   de pied de page, cases à cocher du catalogue à 1280 px). Aucune n'est sous
   24 px hors les cas ci-dessus. Aucune exigence légale ne l'impose ; à
   arbitrer.

## 7. Migration des pages légales : ce qu'elle peut modifier

Fichier : `2026_09_01_190000_remplit_les_pages_legales.php`. Le seul changement
par rapport à `master` est une **modification de son contenu** : la migration
porte déjà son nom dans la table `migrations` de la production.

- **Sur la production** : Laravel ne rejoue pas une migration déjà enregistrée.
  Constaté sur la copie : `migrate --force` répond « Nothing to migrate ».
  **Aucune donnée n'est modifiée au déploiement.**
- **Sur une base neuve** (CI, poste de développement, reconstruction) : elle crée
  ou met à jour deux lignes de `pages_statiques` (`politique-confidentialite`
  publiée, `mentions-legales` **non publiée**), **uniquement si `contenu_fr` est
  vide**. Un texte déjà saisi n'est jamais écrasé.
- **`down()`** (retour arrière de cette migration, à ne pas lancer en
  production) remet `contenu_fr`/`contenu_en` à vide et `publie` à vrai sur les
  deux pages : il **publierait** une page vide. Voir §9.
- **État de la copie de production** : `mentions-legales` non publiée (3 882
  caractères), `politique-confidentialite` publiée (3 711), aucune ancienne
  adresse dans ces textes.
- `mentions-legales:publier` n'a **pas** été lancée, et ne le sera pas sans
  décision sur l'agrément.

## 8. Essai isolé de la sauvegarde externe (`tools/essai-b2-isole.sh`)

Script préparé pour le jour où un bucket d'essai B2 existera. Il ne lit **aucun
fichier** : tout vient de l'environnement du terminal. Il refuse de démarrer si
le nom du bucket ne contient pas « essai », si la base n'est pas locale ou si
`APP_ENV=production`. Étapes : droits de la clé (suppression refusée), dépôt
réel relu par l'application, relevé **indépendant** des objets par le SDK, empreintes,
déchiffrement par l'outil autonome, restauration dans une base neuve d'un
conteneur jetable avec comparaison table par table.

**Répété le 13 octobre contre le S3 local** (SeaweedFS) et la copie jetable : 48
tables, 4 630 lignes, comptes identiques après restauration ; objets conformes
au manifeste. La répétition a trouvé et fait corriger deux défauts du script
lui-même (lecture de l'entrée standard par `docker exec`, et mélange de
plusieurs essais le même jour). **Le contrôle des droits y est volontairement
sauté** (le S3 local autorise la suppression) : cette répétition ne prouve donc
**rien** sur B2.

Lancement sur un vrai bucket d'essai, variables posées dans le terminal :

```bash
ESSAI_CONFIRME=oui ./tools/essai-b2-isole.sh
```

## 9. Protéger la copie de production locale (`sci4k-restauration`)

**Contenu constaté** (comptes de lignes, rien lu) : base `railway` = copie de
production du 12 octobre ; en données personnelles ou sensibles : `users` (2
comptes, avec empreintes de mots de passe et secrets de double authentification
s'ils existent), `sessions` (187, adresses IP et agents), `password_reset_tokens`
(4), `journal_activites` (254), `visites` (3 440, sans IP), `membres_equipe` (3).
Aucun message de contact ni abonné (supprimés avant la bascule). Les bases
`restauration_essai`, `smoke_copie` et `sci4k_test` en sont dérivées ou jetables.

**État actuel** : port publié sur `127.0.0.1:33907` seulement (inaccessible du
réseau), mot de passe racine jetable et connu (`essai-local-jetable`), volume
Docker non chiffré dans le disque virtuel de Docker Desktop.

| Option | Effet | Coût / limite | Décision |
|---|---|---|---|
| Garder le conteneur **arrêté** hors des essais | plus de service MySQL en écoute | le volume reste lisible par qui a accès à la session Windows | simple, sans risque |
| Changer le mot de passe racine | un autre processus local ne peut plus se connecter | à reporter dans les scripts d'essai | simple |
| **Anonymiser** : vider `users`, `sessions`, `password_reset_tokens`, `journal_activites` dans une copie dérivée, supprimer `railway` | plus aucune donnée personnelle | perd la fidélité du test de restauration sur les comptes | recommandé une fois les essais de restauration faits |
| Chiffrer le disque de la machine (BitLocker) | protège le volume au repos | dépend de l'édition de Windows et du poste de l'utilisateur | hors périmètre de l'assistant |
| Exporter la copie vers un fichier chiffré (clé de l'organisation) puis supprimer la base | une seule copie chiffrée | suppose un gestionnaire de clés | à décider |
| Supprimer volume et conteneur | élimine la copie | irréversible ; les essais devraient repartir d'une nouvelle copie de production | **sur accord explicite uniquement** |

Rien n'a été chiffré, déplacé ni supprimé. Le conteneur est réarrêté après chaque
essai.

## 10. Plan de validation `dev` → `preprod`

`preprod` n'a pas d'instance : c'est une branche, pas un environnement. Valider
`dev` → `preprod`, c'est donc **valider ce qui sera fusionné**, avant d'y toucher.

**Avant d'ouvrir la PR** (tout est déjà fait, sauf mention) :

| Critère de réussite | Preuve attendue | État au 13/10 |
|---|---|---|
| CI de `dev` verte (Pint, PHPStan, SQLite, MySQL 9.7.2) | exécution `3e157ee` en succès | **fait** |
| Image construite par le `Dockerfile` à partir de `dev` | `docker build` sans erreur | **fait** en local |
| Conteneur démarré sur une copie de production : migrations, version MySQL, pages | « Nothing to migrate », 200/301 attendus | **fait** en local |
| Aucune migration en attente en production | `migrate:status` sur la copie | **fait** |
| `master` au même niveau que ce que `preprod` recevra, sans conflit | PR sans conflit | à constater à l'ouverture de la PR |
| Décision sur `fix/finitions-cibles-et-index-php` | fusionnée dans `dev` avant, ou renvoyée après | **à décider** |

**Constaté le 13 octobre (fusions à blanc, rien de poussé)** : `preprod` porte 6
commits de fusion absents de `dev`, et `master` 21 absents de `preprod`
(conséquence normale des commits de fusion à chaque étape). `dev` → `preprod`
puis le résultat → `master` fusionnent **sans conflit**, et la différence
finale `master`/résultat porte sur les **mêmes 45 fichiers (+2 611, −57)** que
`master`/`dev` : ce qui sera en ligne est exactement le contenu de `dev`.

**Risques de cette étape** : la CI de `preprod` tourne sur un commit de fusion
nouveau, que personne n'a essayé en conteneur (son contenu est celui de `dev`,
mais ce commit-là n'a pas été construit) ; une protection de branche
absente ou différente de ce que décrit `BRANCHES_ET_DEPLOIEMENT.md` peut
refuser la PR (à constater à l'ouverture, sans la contourner).

**Retour arrière de cette étape** : sans effet en ligne. Annuler par
`git revert -m 1 <fusion>` sur `preprod`, par une PR ; ne pas réécrire
l'historique.

**Critère de passage vers `master`** : CI de `preprod` verte, `verifier-flux-des-branches.sh`
au vert, sauvegarde préalable vérifiée (§4, étape 0), et votre autorisation écrite.
