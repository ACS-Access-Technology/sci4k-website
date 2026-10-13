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
