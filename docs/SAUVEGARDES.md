# Sauvegardes externes

État au 12 octobre 2026. **Le code est préparé et éprouvé en local sur une copie
de la production. Il n'est ni déployé ni activé, aucun compte de stockage n'existe,
et la clé n'a donc été éprouvée contre aucun vrai fournisseur.** La production
n'est sauvegardée que par des copies manuelles (`~/sauvegardes-sci4k`).

## 0. Ce qui a été testé, et ce qui ne l'a pas été

| | Fait | Où |
|---|---|---|
| Chiffrement : aller-retour, mauvaise clé, octet modifié, fichier tronqué (même à la frontière d'un bloc), fichier étranger | oui, tests automatisés | SQLite et MySQL |
| Export SQL, instantané cohérent, comptes par table | oui | MySQL 9.7.2 (conteneur isolé) |
| Dépôt, relecture, déchiffrement, **empreinte du clair identique à l'export** | oui, tests automatisés | stockage simulé |
| Reprise sur panne passagère (1 échec puis succès) ; échec après 3 essais ; objet altéré après dépôt : **le succès n'est jamais annoncé** | oui, tests automatisés | MySQL |
| Sauvegarde exigeant la surveillance externe ; contrôle de configuration (`sauvegarde:verifier-config`) refusant une clé capable de supprimer | oui | automatisé + S3 local qui autorise la suppression : refus constaté |
| Planification : tâche ajoutée à 03 h 20 UTC **seulement** si `SAUVEGARDE_ACTIVE` est vrai | oui | `schedule:list` local, avec et sans |
| Le planificateur existant tourne en production | **oui, démontré** | le journal Railway montre `frequentation:agreger` (03 h 10) et `journal:purger` (03 h 40) terminés « DONE » les 10, 11 et 12 octobre |
| Sauvegarde complète d'une copie de la production, restaurée dans une base neuve | oui | 48 tables, comptes identiques ; **base : 6f9a7bf2… identique ligne par ligne ; structure identique** ; 24 fichiers sur 24 identiques |
| Restauration avec l'outil autonome (PHP seul) | oui | |
| **Vrai bucket Backblaze B2** : écriture, lecture, **suppression refusée**, règles de cycle de vie | **NON** | impossible sans compte ; voir §5 pour l'essai isolé à faire |
| Exécution **dans le conteneur Railway** (extension sodium, connexion MySQL réelle, mémoire) | **NON** | exige le déploiement |
| Alerte réelle (courriel du service de surveillance) | **NON** | exige un compte sur ce service |
| **Planification de 03 h 20 en production** | **NON** | exige le déploiement et l'activation ; seule la planification des deux tâches existantes est démontrée |

Tant que les lignes **NON** ne sont pas faites, **ne rien présenter comme validé**.

## 1. Ce qu'il faut sauvegarder, et où c'est

| Donnée | Où elle vit | Volume (12/10/2026) | Qui la change |
|---|---|---|---|
| Base MySQL 9.7.2 (48 tables) | service `MySQL-1Luf` de Railway | 2,9 Mo ; export compressé ≈ 0,18 Mo | formulaires publics, statistiques de fréquentation, backoffice |
| Fichiers téléversés (photos de biens, équipe, fonds, services) | volume `sci4k-volume`, monté sur `storage/app/public` du service `sci4k` | 4,4 Mo, 23 fichiers | le backoffice, rarement |

**La base ne contient pas les fichiers** : seulement leur chemin. Restaurer la
base seule rend un site sans photos. Les deux se sauvegardent ensemble.

Railway **ne sauvegarde ni l'un ni l'autre** sur le plan Hobby.

## 2. Solutions comparées

Tarifs lus sur les pages des fournisseurs le 12/10/2026 ; non engageants, à
revérifier à l'inscription.

| | Backblaze B2 (**retenu, sous réserve du §5**) | Cloudflare R2 | Option Railway (plan supérieur) |
|---|---|---|---|
| Coût pour ce volume (≈ 0,1 Go au total) | 0 € : 10 Go gratuits | 0 € : 10 Go gratuits | plan Pro, plusieurs fois le coût actuel (non chiffré ici) |
| Au-delà | ≈ 6,95 $/To/mois ; sortie gratuite jusqu'à 3× le stockage | 0,015 $/Go/mois ; sortie gratuite | — |
| API compatible S3 | oui | oui | non |
| Jeton sans droit de suppression | **oui, d'après la documentation du fournisseur** (capacités à la carte) ; **non vérifié** | non : « lecture et écriture » inclut la suppression | — |
| Verrouillage d'objet | Object Lock, à confirmer à la création du bucket | verrouillage de bucket, à confirmer | — |
| Hors du fournisseur de l'application | oui | oui | non |

Coût réel attendu : **0 €**. Il n'est pas confirmé : un compte B2 peut exiger une
carte bancaire à l'ouverture, et un dépassement du gratuit est facturé. Rien n'a
été souscrit.

## 3. Architecture

```
03 h 20 UTC, planificateur du conteneur (schedule:work, déjà en service)
   └─ php artisan sauvegarde:externe
        0. signal « start » au service de surveillance
        1. export SQL (PDO, instantané cohérent, sans mysqldump)   → .sql.gz
        2. archive des fichiers téléversés                          → .tar.gz
        3. chiffrement libsodium (XChaCha20-Poly1305, par blocs)    → .enc
        4. dépôt sur le bucket (S3, TLS) — 3 essais, attente croissante
        5. RELECTURE : retéléchargement, déchiffrement, et l'empreinte du
           contenu relu doit être IDENTIQUE à celle de l'export ; l'export
           doit finir par sa ligne de fin
        6. manifeste (tailles, empreintes) puis signal de BONNE SANTÉ
```

**Règle d'or : le signal de bonne santé n'est envoyé qu'après l'étape 5.** Un
échec, à n'importe quelle étape, envoie le signal d'échec (message sans secret)
et fait échouer la commande. Test automatisé : sur dépôt refusé trois fois, et
sur objet altéré après dépôt, aucun succès n'est signalé.

### Que se passe-t-il si… ?

| Panne | Effet |
|---|---|
| Réseau coupé pendant la nuit | reprises (3 essais) ; sinon échec + signal d'échec + entrée au journal |
| Clé ou variable manquante alors que `SAUVEGARDE_ACTIVE=true` | la commande échoue avant de commencer ; **aucun signal** : le service de surveillance alerte à l'absence de signal |
| Conteneur arrêté à 03 h 20 | pas de signal : alerte à l'absence |
| Le service de surveillance lui-même est injoignable | la sauvegarde se fait quand même ; l'alerte ne part pas : à vérifier dans le service |
| Plus aucune nouvelle sauvegarde | **les règles de cycle de vie continuent de supprimer** : sans copie neuve, tout disparaît en ≈ 15 jours (quotidien) et ≈ 57 jours (hebdomadaire). L'alerte doit être traitée dans ce délai |
| Clé de chiffrement perdue | toutes les sauvegardes sont illisibles. Voir §6 |
| Serveur compromis | la clé d'application ne peut pas supprimer (**à vérifier au §5**) mais elle lit : elle peut relire les sauvegardes chiffrées, que seule la clé de chiffrement ouvre |

**Pas de `mysqldump`** : le conteneur n'en a pas, et le client Debian (MariaDB)
face à un MySQL 9.7 serait un pari. L'export PHP passe par la connexion de
l'application ; sa fidélité est démontrée par restauration (§0). La base n'a ni
déclencheur, ni procédure, ni vue (vérifié le 12/10/2026) ; le jour où elle en
aura, `DumpSql` devra les ajouter.

## 4. Rétention

Réglée **chez le fournisseur**, pas par l'application, qui ne supprime rien :

| Préfixe | Contenu | Conservation visée |
|---|---|---|
| `sci4k/quotidien/AAAA-MM-JJ/` | une sauvegarde par jour | 14 jours |
| `sci4k/hebdomadaire/AAAA-Www/` | la copie du dimanche, **indépendante** de la précédente | 56 jours (8 semaines) |
| `sci4k/_essais/` | objets du contrôle de configuration | 1 jour |

La copie du dimanche est déposée **deux fois** (quotidien/ et hebdomadaire/) :
l'expiration des quotidiens ne peut donc pas emporter les hebdomadaires. À ce
volume : ≈ 22 jeux × 4,7 Mo ≈ 0,1 Go.

**Règles de cycle de vie B2 à créer** (écran « Lifecycle Settings » du bucket,
règles personnalisées ; **intitulés non vérifiés**, aucun bucket n'existant) :

| Préfixe | Jours avant masquage | Jours entre masquage et suppression |
|---|---|---|
| `sci4k/quotidien/` | 14 | 1 |
| `sci4k/hebdomadaire/` | 56 | 1 |
| `sci4k/_essais/` | 1 | 1 |

Risque de suppression prématurée : B2 ne compte que depuis l'envoi. Une
sauvegarde du jour J disparaît donc vers J + 15 (quotidien) ; le « 14 » visé est
un minimum. Le risque réel n'est pas la règle, c'est l'arrêt des sauvegardes
(tableau ci-dessus).

## 5. Mise en place et validation avec B2 (à faire par le propriétaire)

**Rien de ceci n'est fait. Chaque étape marquée ⚠ est une vérification que
personne n'a pu faire sans compte.**

1. Compte Backblaze B2 au nom de l'organisation (pas un compte personnel).
2. **Un bucket d'ESSAI, séparé**, privé, région Europe, et **une clé d'essai** limitée à ce bucket avec les capacités *listFiles, readFiles, writeFiles* — **sans** *deleteFiles*. ⚠ Relever les capacités effectivement proposées.
3. Depuis un poste, avec ces variables **dans le terminal, jamais dans un fichier** :
   ```bash
   export SAUVEGARDE_CLE="$(php -r 'echo base64_encode(random_bytes(32));')"   # clé d'essai, jetable
   export SAUVEGARDE_S3_ENDPOINT=…  SAUVEGARDE_S3_REGION=…  SAUVEGARDE_S3_BUCKET=…
   export SAUVEGARDE_S3_KEY=…       SAUVEGARDE_S3_SECRET=…   SAUVEGARDE_PING_URL=…
   php artisan sauvegarde:verifier-config
   ```
   Attendu : écriture et lecture « ok », **« Suppression : REFUSÉE par le fournisseur »**. Si la ligne dit « LA CLE PEUT SUPPRIMER », la clé est trop puissante : **ne pas continuer**. ⚠ Confirmer aussi dans la console du fournisseur que la suppression est bien refusée, et noter que l'objet de contrôle reste sous `_essais/`.
4. Un dépôt réel dans le bucket d'essai, sur une **copie** de la base (jamais la production) : `php artisan sauvegarde:externe`, puis la restauration du §7 avec ces objets.
5. ⚠ Vérifier la compatibilité du SDK avec B2 (options de sommes de contrôle déjà prévues dans `config/filesystems.php`) : si l'étape 4 échoue sur un refus d'en-tête, le noter avant de continuer.
6. Créer les règles du §4 sur le bucket de production et vérifier qu'elles figurent à l'écran. ⚠ Facultatif : Object Lock sur `hebdomadaire/`, **à décider à la création du bucket**.
7. Un contrôle sur healthchecks.io (ou équivalent) : période 1 jour, tolérance de quelques heures, alerte par courriel vers `info@acsgroupe.ci`. ⚠ Provoquer une fausse panne (pas de signal) et constater l'alerte. Le service ne reçoit rien d'autre que des signaux : jamais de donnée.

## 6. La clé de chiffrement

* Générée une seule fois : `php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`.
* Conservée **hors de Railway** : dans le gestionnaire de mots de passe de l'organisation, avec **au moins deux personnes** qui y ont accès, et une copie papier dans un lieu sûr. **La perdre rend toutes les sauvegardes illisibles, sans recours.**
* Posée sur Railway comme variable `SAUVEGARDE_CLE`, seule copie présente sur la plateforme.
* Jamais dans Git, ni dans un journal, ni dans un rapport ; le contrôle de configuration et les journaux ne la nomment que par son nom de variable (test automatisé).
* Rotation annuelle : générer une nouvelle clé, la déposer **avant** de retirer l'ancienne ; les anciennes sauvegardes restent lisibles avec l'ancienne clé, qu'il faut donc **conserver** tant qu'elles existent.

## 7. Restaurer (sans jamais toucher la production)

À faire sur un poste ou un conteneur isolé, **jamais** sur la base de production.

1. Récupérer les objets du jour voulu dans le bucket (`quotidien/AAAA-MM-JJ/` ou `hebdomadaire/`).
2. Déchiffrer (PHP avec l'extension `sodium` suffit, sans l'application) :
   ```bash
   SAUVEGARDE_CLE='…' php tools/dechiffrer-sauvegarde.php base-….sql.gz.enc base.sql.gz
   SAUVEGARDE_CLE='…' php tools/dechiffrer-sauvegarde.php fichiers-….tar.gz.enc fichiers.tar.gz
   ```
3. Contrôler les empreintes : `sha256sum base.sql.gz` doit égaler `sha256_clair` du `manifeste.json`.
4. Base : dans un MySQL 9.7.x vide et isolé,
   `docker run -d --name restauration -e MYSQL_ROOT_PASSWORD=… mysql:9.7.2`, puis
   `mysql -uroot -p… -e "create database sci4k character set utf8mb4"` et
   `zcat base.sql.gz | mysql -uroot -p… sci4k`.
5. Comptes : la fin du fichier porte une ligne `-- lignes: table=n` par table ; comparer au `select count(*)` de chaque table restaurée.
6. Fichiers : `tar -xzf fichiers.tar.gz -C /chemin/de/storage/app/public`.
7. **Remettre la production en état** est une décision et une opération à part : arrêter les écritures, restaurer, vérifier, rouvrir.

À faire **chaque trimestre**, et après toute migration, sur les objets les plus récents.

## 8. Variables Railway (service `sci4k`) — rien n'est posé

| Variable | Rôle |
|---|---|
| `SAUVEGARDE_ACTIVE` | `true` pour planifier. **À poser en dernier.** Absente ou fausse : rien n'est planifié |
| `SAUVEGARDE_CLE` | clé de chiffrement (§6) |
| `SAUVEGARDE_S3_ENDPOINT`, `_REGION`, `_BUCKET`, `_KEY`, `_SECRET` | le stockage |
| `SAUVEGARDE_PING_URL` | surveillance externe — **obligatoire** : sans elle, `sauvegarde:externe` refuse de s'exécuter |
| `SAUVEGARDE_PREFIXE` | facultatif, `sci4k` par défaut |
| `SAUVEGARDE_SANS_SURVEILLANCE` | essais locaux uniquement ; **ne jamais le poser en production** |

Désactivation : retirer `SAUVEGARDE_ACTIVE` (ou la passer à `false`). L'application
ne supprimant rien, rien n'est à défaire côté stockage.

## 9. Activation — ordre à suivre

1. §5 en entier, sur le bucket d'essai ; chaque ⚠ résolu.
2. Bucket, clé et règles de production ; clé de chiffrement générée et conservée (§6) ; contrôle de surveillance créé.
3. Déploiement du code par le circuit habituel (`dev` → `preprod` → `master`) — **inerte** tant que `SAUVEGARDE_ACTIVE` n'est pas posé.
4. Variables posées, **sauf `SAUVEGARDE_ACTIVE`**.
5. `railway ssh --service sci4k -- php artisan sauvegarde:verifier-config` : tout « ok ».
6. Premier essai réel : `railway ssh --service sci4k -- php artisan sauvegarde:externe`, puis restauration complète du §7 avec **ces** objets. C'est ici que sont vérifiés pour la première fois l'extension sodium, la mémoire et la connexion dans le conteneur.
7. Seulement alors : `SAUVEGARDE_ACTIVE=true`, et **le lendemain**, contrôler que les objets de 03 h 20 sont présents et que le service de surveillance a reçu le signal.

**Retour arrière** : retirer `SAUVEGARDE_ACTIVE`. Les copies manuelles de
`~/sauvegardes-sci4k` restent le repli jusqu'à l'étape 7.

## 10. Fichiers du dépôt

| | |
|---|---|
| `app/Support/Sauvegarde/FluxChiffre.php` | chiffrement/déchiffrement, sans dépendance |
| `app/Support/Sauvegarde/DumpSql.php` | export SQL |
| `app/Support/Sauvegarde/ArchiveFichiers.php` | archive des téléversements |
| `app/Support/Sauvegarde/SauvegardeExterne.php` | l'enchaînement, les reprises, la relecture, l'alerte |
| `app/Support/Sauvegarde/VerificationConfiguration.php` | le contrôle avant activation, droits de la clé compris |
| `app/Console/Commands/Sauvegarde*.php` | `sauvegarde:externe`, `sauvegarde:verifier-config`, `sauvegarde:dechiffrer` |
| `tools/dechiffrer-sauvegarde.php` | déchiffrer avec PHP seul |
| `config/sauvegarde.php`, disque `sauvegardes` | configuration |
| `tests/Feature/Sauvegarde/` | tests (la partie MySQL tourne dans la passe MySQL de la CI) |
