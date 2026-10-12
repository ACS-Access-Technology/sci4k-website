# Sauvegardes externes

État au 12 octobre 2026. **Préparé et testé en local ; pas encore activé en
production** (voir §6 : il manque un compte de stockage, des secrets et un
déploiement, tous soumis à accord).

## 1. Ce qu'il faut sauvegarder, et où c'est

| Donnée | Où elle vit | Volume (12/10/2026) | Qui la change |
|---|---|---|---|
| Base MySQL 9.7.2 (48 tables) | service `MySQL-1Luf` de Railway | 2,9 Mo ; export compressé ≈ 0,18 Mo | formulaires publics (messages, visites, newsletter, statistiques de fréquentation), backoffice |
| Fichiers téléversés (photos de biens, équipe, fonds, services) | volume `sci4k-volume`, monté sur `storage/app/public` du service `sci4k` | 4,4 Mo, 23 fichiers | le backoffice, rarement |

**La base ne contient pas les fichiers** : seulement leur chemin. Restaurer la
base seule rend un site sans photos. Les deux se sauvegardent ensemble.

Railway **ne sauvegarde ni l'un ni l'autre** sur le plan Hobby ; les copies
manuelles de `~/sauvegardes-sci4k` n'existent que sur un poste.

## 2. Solutions comparées

Coûts relevés sur les pages des fournisseurs le 12/10/2026 (non engageants).

| | Backblaze B2 (**recommandé**) | Cloudflare R2 | Option Railway (plan supérieur) |
|---|---|---|---|
| Coût pour ce volume (≈ 0,1 Go au total) | **0 €** : 10 Go gratuits | **0 €** : 10 Go gratuits | plan Pro, plusieurs fois le coût actuel (tarif exact à vérifier avant toute décision) |
| Au-delà du gratuit | ≈ 6,95 $/To/mois ; sortie gratuite jusqu'à 3× le stockage | 0,015 $/Go/mois ; sortie gratuite | — |
| API compatible S3 | oui | oui | non (propriétaire) |
| **Jeton sans droit de suppression** | **oui** (capacités à la carte : écrire, lire, lister, sans « supprimer ») | non : « Lecture et écriture » inclut la suppression | — |
| Verrouillage d'objet (anti-effacement) | Object Lock | verrouillage de bucket (à confirmer dans la console) | — |
| Expiration automatique | règles de cycle de vie | règles de cycle de vie | — |
| Hors du fournisseur de l'application | oui | oui | **non** : même fournisseur que la base |
| Dépendance | faible (S3 standard : migrer = changer 4 variables) | faible | forte |

**Pourquoi B2** : à volume égal et coût nul, c'est le seul des deux dont le
jeton peut être privé du droit de supprimer. Une erreur de code, ou un secret
volé sur Railway, ne peut alors pas effacer l'historique. R2 reste une
alternative valable : même code, quatre variables différentes.

**Pourquoi pas l'option Railway** : elle laisserait les sauvegardes chez le
fournisseur dont une panne ou une suspension emporterait aussi la base.

## 3. Architecture

```
03 h 20 UTC, planificateur du conteneur (schedule:work, déjà en service)
   └─ php artisan sauvegarde:externe
        1. export SQL (PDO, instantané cohérent, sans mysqldump)   → .sql.gz
        2. archive des fichiers téléversés                          → .tar.gz
        3. chiffrement libsodium (XChaCha20-Poly1305, par blocs)    → .enc
        4. dépôt sur le bucket (S3, TLS)
        5. RELECTURE : retéléchargement, déchiffrement, lecture complète,
           et l'export doit finir par sa ligne de fin
        6. manifeste + signal de bonne santé
```

* **Chiffrement** : avant de quitter le serveur ; le fournisseur ne voit que
  des octets. La clé (`SAUVEGARDE_CLE`, 32 octets en base64) n'est jamais écrite
  dans un journal. Un octet altéré, un bloc retiré ou un fichier tronqué font
  échouer le déchiffrement (test).
* **Pas de `mysqldump`** : le conteneur n'en a pas, et le client Debian
  (MariaDB) face à un MySQL 9.7 serait un pari. L'export PHP passe par la
  connexion que l'application utilise ; sa fidélité est vérifiée par
  restauration (§5). La base n'a ni déclencheur, ni procédure, ni vue
  (vérifié le 12/10/2026) ; le jour où elle en aura, `DumpSql` devra les
  ajouter.
* **Rétention** (réglée **chez le fournisseur**, pas par l'application, qui ne
  supprime rien) : `quotidien/` 14 jours ; `hebdomadaire/` (copie du dimanche)
  56 jours. À ce volume : ≈ 22 jeux × 4,7 Mo ≈ 0,1 Go.
* **Alerte** : `SAUVEGARDE_PING_URL`, un service « dead man's switch »
  (healthchecks.io ou équivalent) appelé au début, puis à la réussite ou à
  l'échec. Il prévient par courriel quand le signal n'arrive plus — y compris
  si le conteneur entier est arrêté, cas que rien d'interne ne verrait.
* **Planification inerte par défaut** : `SAUVEGARDE_ACTIVE` doit valoir `true`.
  Déployer le code sans l'activer ne change rien.

## 4. Fichiers du dépôt

| | |
|---|---|
| `app/Support/Sauvegarde/FluxChiffre.php` | chiffrement/déchiffrement, sans dépendance |
| `app/Support/Sauvegarde/DumpSql.php` | export SQL |
| `app/Support/Sauvegarde/ArchiveFichiers.php` | archive des téléversements |
| `app/Support/Sauvegarde/SauvegardeExterne.php` | l'enchaînement, la relecture, l'alerte |
| `app/Console/Commands/Sauvegarde*.php` | `sauvegarde:externe`, `sauvegarde:dechiffrer` |
| `tools/dechiffrer-sauvegarde.php` | déchiffrer avec PHP seul, sans l'application |
| `config/sauvegarde.php`, disque `sauvegardes` | configuration |
| `tests/Feature/Sauvegarde/` | tests (la partie MySQL tourne dans la passe MySQL de la CI) |

## 5. Restaurer (sans jamais toucher la production)

À faire sur un poste ou un conteneur isolé, **jamais** sur la base de production.

1. Récupérer les deux objets du jour voulu dans le bucket (`quotidien/AAAA-MM-JJ/`).
2. Déchiffrer (PHP avec l'extension `sodium` suffit) :
   ```bash
   SAUVEGARDE_CLE='…' php tools/dechiffrer-sauvegarde.php base-….sql.gz.enc base.sql.gz
   SAUVEGARDE_CLE='…' php tools/dechiffrer-sauvegarde.php fichiers-….tar.gz.enc fichiers.tar.gz
   ```
3. Base : dans un MySQL 9.7.x vide et isolé,
   `docker run -d --name restauration -e MYSQL_ROOT_PASSWORD=… mysql:9.7.2`, puis
   `mysql -uroot -p… -e "create database sci4k"` et
   `zcat base.sql.gz | mysql -uroot -p… sci4k`.
4. Contrôle : la fin du fichier porte une ligne `-- lignes: table=n` par table.
   Comparer au `select count(*)` de chaque table restaurée.
5. Fichiers : `tar -xzf fichiers.tar.gz -C /chemin/de/storage/app/public`.
6. **Pour remettre la production en état**, c'est une décision et une
   opération à part : arrêter les écritures, restaurer, vérifier, rouvrir.

**Essais déjà faits (12/10/2026, conteneurs isolés, aucune écriture en production)**

| Essai | Résultat |
|---|---|
| Export manuel de la production (`mysqldump`) restauré dans MySQL 9.7.2 | 48 tables, lignes identiques |
| `sauvegarde:externe` sur une copie de la production, S3 local (SeaweedFS) | 48 tables, 4 560 lignes, 4,7 Mo chiffrés, relecture réussie |
| Déchiffrement avec l'outil autonome ; avec une mauvaise clé | réussi ; refusé |
| Restauration dans une base neuve | 48 tables, lignes identiques à l'export ; empreinte des lignes de `biens`, `temoignages`, `parametres`, `users` identique à la source |
| Fichiers | 24 sur 24 identiques |

**Pas encore essayé** : un dépôt sur un vrai B2 (compatibilité du SDK avec ce
fournisseur — options de sommes de contrôle prévues dans `config/filesystems.php`),
et l'exécution dans le conteneur Railway. Ce sont les deux premiers contrôles
de l'activation.

## 6. Activation (rien de ceci n'est fait)

À la charge du propriétaire, ou avec son accord explicite :

1. Compte Backblaze B2 (au nom de l'organisation, pas un compte personnel) ;
   bucket **privé**, région Europe.
2. Clé d'application **limitée à ce bucket**, capacités *listFiles, readFiles,
   writeFiles* — **sans** *deleteFiles*.
3. Règles de cycle de vie : `sci4k/quotidien/` 14 jours, `sci4k/hebdomadaire/`
   56 jours. Option : Object Lock en mode conformité sur `hebdomadaire/`.
4. Un contrôle sur healthchecks.io (ou équivalent) : période 1 jour, tolérance
   quelques heures, notification vers `info@acsgroupe.ci`.
5. Générer la clé : `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`.
   **La conserver dans un gestionnaire de mots de passe, hors Railway** : la
   perdre rend toutes les sauvegardes illisibles.
6. Variables du service `sci4k` sur Railway : `SAUVEGARDE_CLE`,
   `SAUVEGARDE_S3_ENDPOINT`, `SAUVEGARDE_S3_BUCKET`, `SAUVEGARDE_S3_KEY`,
   `SAUVEGARDE_S3_SECRET`, `SAUVEGARDE_S3_REGION`, `SAUVEGARDE_PING_URL`.
7. Déployer le code (circuit habituel `dev` → `preprod` → `master`).
8. Un premier essai manuel :
   `railway ssh --service sci4k -- php artisan sauvegarde:externe`, puis
   vérifier les objets dans le bucket et refaire l'essai de restauration (§5)
   **avec ces objets-là**.
9. Seulement alors : `SAUVEGARDE_ACTIVE=true`.

**Retour arrière** : `SAUVEGARDE_ACTIVE=false` (ou retirer la variable) ;
l'application ne supprimant rien, rien n'est à défaire. Les copies manuelles de
`~/sauvegardes-sci4k` restent la solution de repli jusqu'à l'étape 9.

## 7. À surveiller une fois actif

* Un essai de restauration complet **chaque trimestre**, et après toute
  migration.
* Le signal de surveillance, pas le journal : c'est lui qui prévient.
* La clé de chiffrement et les jetons : une rotation annuelle ; après une
  rotation de clé, les anciennes sauvegardes se lisent encore avec l'ancienne.
* Les coûts, si le volume dépasse 10 Go (très loin).
