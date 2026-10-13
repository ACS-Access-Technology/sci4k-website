# Sécurisation des copies locales et estimation de la préproduction — 13 octobre 2026

**Rien de ce document n'est exécuté.** Aucun fichier, volume ou mot de passe n'a
été modifié, déplacé, chiffré ni supprimé. Les relevés (§1) sont en lecture
seule : chemins, tailles, dates, droits, empreintes. **Aucun contenu SQL, mot de
passe, secret ou clé n'a été lu ni affiché.**

## 1. Inventaire constaté

### 1.1 Les trois fichiers signalés

| Fichier | Taille | Modifié | SHA-256 | Droits (hérités de `%TEMP%`) |
|---|---|---|---|---|
| `C:\Users\LENOVO\AppData\Local\Temp\full.sql` | 442 281 o | 06/10/2026 15:56 | `9b725f9a2a480b9f360b61947443fad388ea78ade30cb80e53c58186b3fa9a31` | LENOVO, Système, **`CodexSandboxUsers` : Modifier**, deux SID de profil |
| `…\Temp\claude\…\scratchpad\avant.sql` | 442 281 o | 06/10/2026 15:56 | `9b725f9a2a480b9f360b61947443fad388ea78ade30cb80e53c58186b3fa9a31` | idem |
| `C:\Users\LENOVO\AppData\Local\Temp\cle-essai.txt` | 45 o | 12/10/2026 20:53 | `33ef3bbe448c0a6aab37efce7671a87cdefab15d7fcf692b8777f80ccb8e281a` | idem |

- **`full.sql` et `avant.sql` sont identiques** (même empreinte) : deux copies d'un
  même export de production du 6 octobre. Les tables présentes (noms seulement) :
  `users`, `sessions`, `password_reset_tokens`, `journal_activites`,
  `membres_equipe`, `abonnes_newsletter`, `demandes_de_visite`, et le reste du site.
  Pas de table `messages` ni `newsletter` sous ces noms.
- **`cle-essai.txt`** est la clé de chiffrement **d'essai** des sauvegardes de
  répétition stockées dans le volume `sci4k-s3`. Avec ce volume, elle les rend
  lisibles : les deux doivent être traités ensemble.
- **Les droits ne sont pas protecteurs** : tout ce qui est dans `%TEMP%` hérite
  d'un groupe `CodexSandboxUsers` qui peut **modifier** les fichiers.

### 1.2 Utilisation par un processus ou une procédure

- Aucun conteneur ne monte ces fichiers (vérifié sur tous les conteneurs).
- Deux `bash.exe` portent leur nom dans leur ligne de commande : ce sont **mes
  propres commandes d'inventaire** de cette séance, pas un service.
- Références dans les scripts : **`cle-essai.txt` est lue par
  `scratchpad\restauration_e2e.sh`** (la répétition de restauration). `full.sql` et
  `avant.sql` ne sont référencés par **aucun script ni document du dépôt**.
- **Aucune des trois n'est requise** pour restaurer la production : la procédure
  (`SAUVEGARDES.md`, §7) part des exports de `~/sauvegardes-sci4k` ou du bucket.

### 1.3 Autres copies et résidus trouvés (hors demande initiale)

Recherche des `*.sql`, `*.sql.gz`, `*.b64`, `*.pem`, `cle-*.txt` dans le Bureau, les
Documents, les Téléchargements, `%TEMP%` et `~/sauvegardes-sci4k` (50 résultats,
dont la plupart sont des bibliothèques sans rapport : `cacert.pem`, tests de
paquets, schémas d'autres projets).

| Chemin | Contenu | Remarque |
|---|---|---|
| `%TEMP%\tmp.bKOIO5ZXv8\` (12/10 16:29, 31 fichiers) | **sauvegarde déchiffrée** de la copie : `base.sql.gz` (179 215 o), `fichiers.tar.gz` | résidu de **mes** répétitions de restauration (script sans nettoyage) |
| `%TEMP%\tmp.u9MuKmeISR\` (12/10 20:54, 31 fichiers) | idem | idem |
| `%TEMP%\tmp.CNz3BmSaf8\` (12/10 16:24, 6 fichiers) | résidu d'une répétition plus ancienne | à examiner avant tout |
| `%TEMP%\apres.sql`, `scratchpad\apres.sql` (3 275 o) | export réduit du 6/10 | faible volume, contenu non vérifié |
| `scratchpad\q.b64` (4 032 o) | non identifié | contenu non lu |
| `scratchpad\jar.txt`, `jar2.txt` (934 o) | cookies de contrôle (6 et 12/10) | jetons de session éventuels, probablement expirés |
| `scratchpad\railway-up.log` (277 484 o) | journal d'un déploiement du 6/10 | contenu non relu |
| Volume Docker `3e0c2154…` (`sci4k-restauration`) | 4 bases dont `railway` (copie du 12/10) | voir §1.4 |
| Volume Docker `cde5c74d…` (`sci4k-s3`) | sauvegardes d'essai chiffrées | lisibles avec `cle-essai.txt` |

Les scripts d'essai écrits aujourd'hui (`tools/essai-b2-isole.sh`) **nettoient** leur
dossier temporaire ; ceux des 12 octobre ne le faisaient pas.

### 1.4 Données sensibles potentiellement contenues

Constat par noms de tables et comptes de lignes de la copie (aucune lecture de
valeurs) :

| Table | Lignes | Nature |
|---|---|---|
| `users` | 2 | adresses de messagerie, **empreintes de mots de passe**, secrets de double authentification s'il y en a, jetons « se souvenir de moi » |
| `sessions` | 187 | adresses IP, agents utilisateurs, contenu de session |
| `password_reset_tokens` | 4 | jetons de réinitialisation |
| `journal_activites` | 254 | actions des comptes, éventuellement adresses |
| `membres_equipe` | 3 | noms, fonctions, adresse affichée |
| `abonnes_newsletter`, `demandes_de_visite` | présentes dans `full.sql` du 6/10 | **absentes ou vides** dans la copie du 12/10 (supprimées avant la bascule) ; leur contenu dans `full.sql` n'est pas vérifié |
| `visites` | 3 440 | fréquentation, sans adresse IP |

### 1.5 `~/sauvegardes-sci4k` — constats, rien n'est modifié

Contenu : **12 exports `base-*.sql.gz`** du 1er au 12 octobre (le plus récent :
`base-20261012-1554-point-de-retour-avant-corrections.sql.gz`, 179 107 o), un
fichier `.b64`, **5 copies des fichiers téléversés** (23 fichiers, 4,5 Mo chacune),
des notes de bascule `bascule-*.md`, un dossier d'essai de téléversement.

- **Il n'est pas chiffré** : aucun attribut EFS (0 fichier chiffré), exports
  simplement compressés. Les exports contiennent les mêmes tables sensibles (§1.4).
- **Droits** : LENOVO, Administrateurs, Système en contrôle total ;
  `CodexSandboxUsers` en **lecture**. Mieux que `%TEMP%`, mais pas un chiffrement.
- **BitLocker : non vérifiable** depuis ma session (`Accès refusé` : exige un
  terminal administrateur). **Je n'en conclus rien.** À contrôler par vous :
  `manage-bde -status C:` dans un terminal administrateur.
- Il contient l'état de production du 12 octobre : **c'est aujourd'hui la seule
  copie de récupération complète, et elle n'est pas chiffrée.**

## 2. Procédure de sécurisation proposée (pour approbation, rien n'est lancé)

**Principes** : une seule copie de récupération **chiffrée**, une clé **hors de ce
poste**, aucune copie en clair dispersée, et **jamais de suppression avant la
vérification** de la copie chiffrée. `~/sauvegardes-sci4k` reste **intact**.

**Outil** : `gpg --symmetric --cipher-algo AES256` (présent dans Git Bash),
avec une phrase secrète saisie **par vous**, de façon interactive, dans votre
terminal. Je ne vois ni ne reçois cette phrase. Alternative : l'outil maison
`tools/dechiffrer-sauvegarde.php` (même format que les sauvegardes B2) avec une clé
de 32 octets, si vous préférez une seule chaîne d'outils.

**Clé** : phrase secrète conservée dans le gestionnaire de mots de passe de
l'organisation, **au moins deux personnes** y ont accès, jamais sur ce poste ni
dans Git, jamais dans une conversation. **Perdue, la copie est irrécupérable.**

| # | Action proposée | Source | Destination | Risques | Vérification |
|---|---|---|---|---|---|
| 1 | Créer un dossier de coffre aux droits restreints | — | `C:\Users\LENOVO\coffre-sci4k\` (droits : LENOVO seul, héritage coupé) | un dossier de plus ; **ne change pas** `~/sauvegardes-sci4k` | `icacls` : aucune autre identité |
| 2 | **Chiffrer une copie de récupération** du dernier état de production | `~/sauvegardes-sci4k\base-20261012-1554-point-de-retour-avant-corrections.sql.gz` (+ son `.sha256`) et `fichiers-20261012-1554-point-de-retour\` (archivé en `.tar` d'abord) | `coffre-sci4k\point-de-retour-20261012.tar.gpg` | phrase perdue = copie perdue ; **la source en clair reste** (par décision) | déchiffrer dans un dossier temporaire, comparer le SHA-256 à celui d'origine, supprimer le temporaire |
| 3 | Chiffrer l'export du 6 octobre **une seule fois** | `%TEMP%\full.sql` | `coffre-sci4k\export-prod-20261006-1556.sql.gpg` | équivalence avec les exports de `~/sauvegardes-sci4k` **non prouvée** (le plus proche : 15:39 et 16:43 le même jour) ; on le garde donc | déchiffrer, SHA-256 = `9b725f9a…9a31` |
| 4 | Supprimer les deux copies en clair de l'export du 6/10 | `%TEMP%\full.sql` et `scratchpad\avant.sql` | — | irréversible ; **uniquement après** la vérification de l'étape 3 ; sur SSD, la suppression n'efface pas les blocs | l'étape 3 est vérifiée ; fichiers absents ; éventuellement `cipher /w:` sur le dossier (lent) |
| 5 | Supprimer les résidus de restauration déchiffrés | `%TEMP%\tmp.bKOIO5ZXv8\`, `tmp.u9MuKmeISR\`, après examen de `tmp.CNz3BmSaf8\` | — | aucun contenu unique : dérivés de la copie jetable | listing avant/après ; rien d'autre ne les référence |
| 6 | Traiter la clé d'essai **avec** le volume S3 | `%TEMP%\cle-essai.txt` et volume `cde5c74d…` | — (suppression des deux ensemble, après les derniers essais de restauration) | supprimer la clé seule rendrait les essais illisibles ; le volume seul laisserait la clé inerte | `docker volume ls` ; fichier absent |
| 7 | Fin de vie de la copie `railway` | volume `3e0c2154…` | — | irréversible ; **la copie est reproductible** depuis `~/sauvegardes-sci4k` | après les essais de restauration, **sur votre confirmation écrite seulement** |
| 8 | Cookies et journaux de contrôle | `scratchpad\jar.txt`, `jar2.txt`, `railway-up.log`, `q.b64`, `apres.sql` | — | contenu non relu : à examiner (en-têtes seulement) avant de statuer | listing avant/après |
| 9 | Contrôler BitLocker | `C:` | — | si désactivé, **tout ce qui précède reste lisible par qui prend le disque** | `manage-bde -status C:` par vous, terminal administrateur |

**Ordre** : 1 → 2 → 3 → (vérifications) → 4 → 5 → 8 ; 6 et 7 seulement après les
derniers essais de restauration (`PROCEDURE_DEPLOIEMENT_PROGRESSIF.md`, §12).

**Si vous préférez ne rien supprimer** : les étapes 1 à 3 donnent déjà une copie de
récupération protégée ; les copies en clair restent un risque documenté.

**Limites assumées** : la suppression sur SSD n'est pas un effacement sûr ;
seul un disque chiffré (BitLocker) protège les blocs résiduels. Cette procédure ne
chiffre pas `~/sauvegardes-sci4k` lui-même : le faire changerait un dossier que
vous m'avez demandé de laisser intact.

## 3. Estimation mensuelle de la préproduction Railway

**Aucun service ni base n'a été créé.** Les chiffres viennent de la mesure de la
production et de la grille tarifaire publiée.

### 3.1 Tarifs vérifiés le 13 octobre 2026

Source : documentation de Railway, page des plans ; abonnement **Hobby** : **5 $/mois**
avec **5 $ d'usage inclus par cycle** (non cumulables) ; au-delà, la différence est
facturée. Usage : mémoire **10 $/Go/mois**, processeur **20 $/vCPU/mois**, volume
**0,15 $/Go/mois**, sortie réseau **0,05 $/Go**. Plafonds par service : volume 5 Go,
mémoire 48 Go, 48 vCPU.

### 3.2 Consommation mesurée de la production (7 derniers jours, lecture seule)

| Service | CPU moyen | Mémoire moyenne (max) | Disque utilisé | Sortie réseau |
|---|---|---|---|---|
| `sci4k` (web) | 0,0037 vCPU | 0,287 Go (0,756) | 0,04 Go | 0,19 Go sur 7 jours |
| `MySQL-1Luf` | 0,0030 vCPU | 0,409 Go (0,484) | 0,21 Go | ≈ 0 |

Volumes : `mysql-volume` **500 Mo** (147 Mo utilisés), `sci4k-volume` (taille non
affichée par la CLI ; 4,4 Mo de fichiers).

### 3.3 Estimation, ligne par ligne (hypothèse : préproduction qui tourne 24 h/24, même empreinte que la production, sans trafic)

| Poste | Calcul | Par mois |
|---|---|---|
| Service web, mémoire | 0,287 Go × 10 $ | ≈ 2,87 $ |
| Service web, processeur | 0,0037 × 20 $ | ≈ 0,07 $ |
| MySQL, mémoire | 0,409 Go × 10 $ | ≈ 4,09 $ |
| MySQL, processeur | 0,0030 × 20 $ | ≈ 0,06 $ |
| Volumes (2 × 500 Mo si facturés à la taille allouée) | 1 Go × 0,15 $ | ≈ 0,15 $ |
| Trafic sortant (quelques tests) | < 1 Go × 0,05 $ | < 0,05 $ |
| **Total, 24 h/24** | | **≈ 7,2 $** |
| **Total, 3 jours par mois seulement** (puis environnement supprimé) | 7,2 $ × 72/730 | **≈ 0,7 $** |

**Impact sur la facture** : la même méthode appliquée à la production donne
≈ 7,3 $ d'usage par mois, soit **déjà un peu au-dessus des 5 $ inclus** (≈ 2,3 $ de
dépassement, **non vérifié sur une facture réelle**). Une préproduction permanente
ajouterait donc **≈ 7 $ par mois** de facture ; une préproduction de campagne
(quelques jours), **≈ 1 $**.

### 3.4 Incertitudes

- **Offre non confirmée par moi** : je pars de Hobby (mémorisé), sans avoir vu votre
  facture ni votre tableau de bord. Si d'autres projets partagent le même crédit de
  5 $, l'effet est plus fort.
- Les mesures sont des **moyennes sur 7 jours**, avec un trafic réel faible ;
  les maximums de mémoire (0,76 Go pour le web) feraient monter le haut de la
  fourchette si la préproduction supportait des tests plus lourds.
- Les volumes sont-ils facturés à la **taille allouée** ou à l'**occupation** ? La
  page lue ne le précise pas : fourchette 0,04 à 0,15 $ ; sans enjeu.
- La page lue **ne dit rien** du nombre d'environnements ou de services autorisés
  sur Hobby : à constater à la création, pas avant.
- Le trafic sortant mesuré est une série d'échantillons horaires non cumulés
  (0,19 Go sur 7 jours) ; l'extrapolation (≈ 0,8 Go/mois) est indicative.
- Une **mémoire MySQL plus basse** (réglage du tampon InnoDB) réduirait le poste le
  plus lourd, mais n'a pas été essayée : **non chiffré**.

### 3.5 Configuration minimale, isolée de la production

| Élément | Choix minimal |
|---|---|
| Environnement | un environnement `preprod` dans le projet `energetic-courtesy`, **créé vide**, services ajoutés à la main |
| Service web | un seul, une réplique, sans domaine personnalisé (adresse provisoire `*.up.railway.app`), déploiement **manuel** |
| Base | **nouveau** MySQL 9.7.2, volume distinct de 500 Mo |
| Volume de fichiers | 500 Mo, vide |
| Variables | `APP_KEY` **différente** ; `APP_DEBUG=false` ; `MAIL_MAILER=log` ; **aucune** clé Resend ni B2 de production ; `SAUVEGARDE_*` absentes |
| Données | structure + démonstration `DEMO-` ; **aucune** donnée de production |
| Cycle de vie | créé pour une campagne de validation, supprimé ensuite |

**Danger à connaître** : la documentation indique qu'on peut **dupliquer** un
environnement (services, variables, configuration). Une duplication de la
production **copierait ses variables**, secrets compris (clé Resend, `APP_KEY`,
identifiants de base). **Je recommande donc de ne pas dupliquer** : créer
l'environnement vide, ou, si l'on duplique, **purger et remplacer chaque variable
avant tout déploiement** (les services dupliqués sont mis en attente et exigent une
approbation, ce qui laisse le temps de les relire).

**Rien n'est créé.** Créer un environnement, un service ou une variable est une
modification de votre compte : elle attend votre autorisation écrite.
