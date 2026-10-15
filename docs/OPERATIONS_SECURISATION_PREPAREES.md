# Opérations de sécurisation préparées — A, B, D, et méthode de lecture des variables (13 octobre 2026)

**Rien de ce document n'est exécuté sur vos données.** Aucun fichier de
`~/sauvegardes-sci4k`, de `%TEMP%` ou du scratchpad n'a été chiffré, déplacé ni
supprimé ; aucun conteneur, volume ni mot de passe n'a été modifié ; aucune
variable de production n'a été affichée. Les essais ci-dessous ont tourné sur des
**données fictives** et, pour un seul, sur l'export clair du 12 octobre **en
lecture seule** dans un conteneur sans réseau et sans disque (§1.6).

Ordre retenu par vos décisions : **A** chiffrement (à exécuter après votre contrôle
de BitLocker et votre confirmation), puis **B** quarantaine (après A vérifié), puis
**D** mot de passe ; **C** suppression : **pas maintenant**.

## 0. Correction d'un défaut de `SECURISATION_COPIES_V2.md`

L'étape A5 de ce document (fusionné dans `dev`) **restaurait l'export dans une base
neuve** par `mysql <base> < export`. **C'est faux pour les exports de
`~/sauvegardes-sci4k`** : l'export du 12 octobre contient `CREATE DATABASE` et
`USE` (constaté : 1 et 1). La restauration aurait donc écrit dans la base
`railway` **du conteneur persistant, en écrasant la copie qui s'y trouve**, et la
base « neuve » serait restée vide.

Trouvé avant toute exécution, rien n'a été perdu. **Remplacé par**
`tools/securiser-copie-recuperation.sh restaurer` : un conteneur **jetable, sans
réseau, sans port, sans volume** (base en mémoire), où la base `railway` créée par
l'export n'écrase rien. **Les §4 (commandes A) et la comparaison avec `railway` de
`SECURISATION_COPIES_V2.md` ne doivent plus être suivis** ; ce document les
remplace.

## 1. Contrôles préalables demandés (constatés)

| Exigence | Constat |
|---|---|
| **La copie complète du 12 octobre est identifiable sans ambiguïté** | `base-20261012-1554-point-de-retour-avant-corrections.sql.gz` (179 107 o, 12/10 15:54), empreinte **conforme** à son `.sha256`, `gzip -t` intègre, **48 tables**, 35 `INSERT`, `CREATE DATABASE` et `USE` présents ; dossier `fichiers-20261012-1554-point-de-retour` (23 fichiers, 4 506 107 o). Aucun autre élément de cette date. Aucune note de bascule ne s'y réfère |
| **Capacité de destination suffisante** | **17,6 Go** libres sur `C:` (475,7 Go) pour ≈ **5,1 Mo** à chiffrer ; le script exige 3 × la taille et refuse sinon |
| **Permissions restrictives** | le script règle le coffre par `icacls /inheritance:r` et **échoue** si le coffre n'a pas exactement **une** entrée de droits. Essayé sur un dossier fictif : une seule identité (`AUGUSTIN-PERSEP\LENOVO`, contrôle total) |
| **La source ne peut pas être écrasée** | la source n'est que **lue** (`sha256sum`, `tar -cf -`, `gpg` en lecture) ; le coffre ne peut pas être dans la source ni la contenir (refus) ; un fichier de sortie existant **bloque** le script (essai : « existe déjà : il ne sera pas écrasé ») ; **empreintes de la source identiques avant et après** l'essai complet |
| **La phrase secrète est saisie de façon interactive, jamais enregistrée** | le script **n'a aucun chemin d'entrée pour la phrase** : ni argument, ni variable, ni fichier ; `gpg` la demande dans une fenêtre `pinentry` (présent : `pinentry-w32`) et **ne la garde pas** (`--no-symkey-cache`). Les commandes tapées ne la contiennent pas, donc ni l'historique du terminal ni un journal ne la reçoivent. **Non essayé en interactif par moi** (je ne peux pas saisir dans une fenêtre) : voir la répétition à blanc, §2 |
| **Empreintes source = contenu déchiffré** | `verifier` compare l'empreinte du **flux déchiffré** à celle de l'original, pour la base, l'export supplémentaire et l'archive des fichiers, puis **extrait** les fichiers et compare chacun. Essayé sur données fictives : identiques. **Mauvaise phrase : refus constaté** (`Bad session key`) ; **fichier altéré : refus constaté** (essai gpg du 13/10) |
| **Restauration dans un environnement isolé, sans connexion à la production** | voir §1.6 |

### 1.6 Restauration isolée : essais réels

1. **Données fictives**, de bout en bout (chiffrer → vérifier → restaurer) : 2 tables
   restaurées sur 2 attendues, 3 lignes.
2. **L'export clair réel du 12 octobre**, lu en lecture seule, restauré dans un
   conteneur `mysql:9.7.2` créé **avec `--network none`**, **sans port publié**,
   **sans volume** (`--tmpfs`) : réseaux constatés `none`, ports `[]`. Résultat :
   **48 tables restaurées, 4 560 lignes**, base `railway` créée par l'export. Le
   conteneur a été **arrêté et a disparu** (`--rm`) ; la base en mémoire avec lui.
   Aucune copie supplémentaire n'a été écrite sur disque.

La comparaison ligne à ligne avec la copie `railway` du conteneur persistant **n'a
pas été faite** (elle exigeait le mot de passe racine). Elle n'est pas nécessaire
à la validation du chiffrement : le critère est « les mêmes 48 tables et les mêmes
lignes que l'export en clair », constaté en 2.

## 2. Opération A — chiffrement (à exécuter par vous, après BitLocker)

**Préalable.** BitLocker : `manage-bde -status C:` dans un terminal administrateur
(`Conversion Status`, `Percentage Encrypted`, `Protection Status`). **S'il est
inactif, ne rien supprimer avant d'avoir traité le chiffrement du disque et la
sauvegarde de sa clé de récupération** ; cette clé ne se partage jamais ici.

**Phrase secrète** : choisie par vous, enregistrée **avant** le premier chiffrement
dans le gestionnaire de mots de passe de l'organisation, deux personnes d'accès.
Perdue, la copie est irrécupérable.

**Répétition à blanc (recommandée, ne touche à aucune donnée réelle)** : elle
vérifie que la fenêtre de saisie fonctionne chez vous, avec le vrai `gpg` et une
phrase d'essai que vous inventez :

```bash
# Fixture fictive : mêmes noms que les vrais éléments, contenu sans valeur
R=$(mktemp -d); B=base-20261012-1554-point-de-retour-avant-corrections.sql.gz; F=fichiers-20261012-1554-point-de-retour
mkdir -p "$R/src/$F"
printf '%s\n' 'CREATE DATABASE `railway`;' 'USE `railway`;' 'CREATE TABLE `t` (id int);' 'INSERT INTO `t` VALUES (1);' | gzip > "$R/src/$B"
sha256sum "$R/src/$B" | cut -c1-64 > "$R/src/$B.sha256"; echo x > "$R/src/$F/a.txt"
export SAUVEGARDES_DIR="$R/src" COFFRE_DIR="$R/coffre" EXPORT_SUPPLEMENTAIRE=
tools/securiser-copie-recuperation.sh controles
tools/securiser-copie-recuperation.sh chiffrer     # une fenêtre demande la phrase d'ESSAI, deux fois
tools/securiser-copie-recuperation.sh verifier     # la fenêtre la redemande
tools/securiser-copie-recuperation.sh restaurer    # idem
rm -rf "$R"; unset SAUVEGARDES_DIR COFFRE_DIR EXPORT_SUPPLEMENTAIRE
```

**Exécution réelle**, dans l'ordre, une commande à la fois :

```bash
cd "C:/Users/LENOVO/Desktop/4KID - Copie"      # dépôt, branche dev à jour
tools/securiser-copie-recuperation.sh controles   # lecture seule : RIEN n'est écrit
tools/securiser-copie-recuperation.sh chiffrer    # crée ~/coffre-sci4k, empreintes, 3 fichiers .gpg
tools/securiser-copie-recuperation.sh verifier    # algorithme, empreintes en flux, extraction de contrôle
tools/securiser-copie-recuperation.sh restaurer   # restauration isolée sans réseau, depuis la copie chiffrée
```

`chiffrer` inclut aussi l'export du 6 octobre (`%TEMP%\full.sql`). Pour l'exclure :
`EXPORT_SUPPLEMENTAIRE= tools/securiser-copie-recuperation.sh chiffrer`.

**Vérifications attendues** (le script s'arrête à la première qui manque) :

| Étape | Attendu |
|---|---|
| `controles` | empreinte conforme au `.sha256`, 48 tables, capacité suffisante, coffre distinct de la source, aucune cible existante, « RIEN n'a été écrit » |
| `chiffrer` | une seule identité dans les droits du coffre ; 3 fichiers `.gpg` ; fichiers d'empreintes d'origine |
| `verifier` | chaque `.gpg` : `cipher 9` et `mdc_method 2` ; empreintes en flux **identiques** ; N fichiers extraits identiques |
| mauvaise phrase | **à faire une fois à la main** : saisir une phrase fausse pour `verifier` ; refus attendu |
| `restaurer` | réseaux `none`, ports vides ; **48 tables sur 48**, lignes comptées ; conteneur disparu |

**Copies en clair après A** : **toutes**, inchangées (`~/sauvegardes-sci4k`,
`full.sql`, `avant.sql`, les trois `tmp.*`, le volume `3e0c…`). **S'y ajoutent** : le
coffre chiffré (3 fichiers + 3 fichiers d'empreintes, sans contenu SQL).
**Rien n'est supprimé par A.**

**Retour arrière** : supprimer `~/coffre-sci4k` (aucune source touchée).

**Risques** : phrase perdue (copie perdue) ; chiffrer à l'aide d'une fenêtre dont
on ignore l'origine (n'accepter que la fenêtre ouverte par la commande lancée) ;
l'utilisateur oublie `unset` d'une variable d'essai (`SAUVEGARDES_DIR`…) puis
lance la commande réelle : **la sortie de `controles` affiche les chemins
utilisés — les relire** avant `chiffrer`.

## 3. Opération B — quarantaine (après A réussi, séparément)

**But** : sortir les copies en clair de `%TEMP%` (dont les droits héritent à
`CodexSandboxUsers` en modification) vers un dossier réservé à votre compte, **sans
rien détruire**.

**Dépendances** : recensées par recherche dans les scripts : **seul
`restauration_e2e.sh` lit `cle-essai.txt`** ; **rien ne lit** `full.sql`, `avant.sql`,
les `tmp.*`, `jar*.txt`, `q.b64`, `apres.sql` ni `railway-up.log`. **`cle-essai.txt`
n'est donc pas déplacée** : elle reste jusqu'à la fin des essais avec le volume
`sci4k-s3`.

**Commandes** (PowerShell ; le manifeste rend le retour arrière exact) :

```powershell
$q  = "$env:USERPROFILE\coffre-sci4k\quarantaine"
New-Item -ItemType Directory -Force $q | Out-Null
icacls $q /inheritance:r /grant:r "${env:USERNAME}:(OI)(CI)F" | Out-Null
(Get-Acl $q).Access.Count        # attendu : 1
$sp = "$env:TEMP\claude\C--Users-LENOVO-Desktop-4KID---Copie\79b26cd2-d995-417a-9be3-efb5b81b9b40\scratchpad"
$el = @("$env:TEMP\full.sql","$sp\avant.sql","$env:TEMP\apres.sql","$sp\apres.sql","$sp\jar.txt","$sp\jar2.txt","$sp\q.b64","$sp\railway-up.log")
$manifeste = "$q\MANIFESTE.txt"
foreach ($f in $el) {
  $h1 = (Get-FileHash $f -Algorithm SHA256).Hash
  $dest = Join-Path $q ((Split-Path (Split-Path $f -Parent) -Leaf) + "__" + (Split-Path $f -Leaf))
  if (Test-Path $dest) { throw "destination existante : $dest" }
  Move-Item -LiteralPath $f -Destination $dest
  $h2 = (Get-FileHash $dest -Algorithm SHA256).Hash
  "{0}`t{1}`t{2}`t{3}" -f $h1, $f, $dest, $(if ($h1 -eq $h2) {'IDENTIQUE'} else {'DIFFERENT'}) | Tee-Object -Append $manifeste
}
foreach ($d in "tmp.bKOIO5ZXv8","tmp.u9MuKmeISR","tmp.CNz3BmSaf8") {
  if (Test-Path "$q\$d") { throw "destination existante : $q\$d" }
  Move-Item -LiteralPath "$env:TEMP\$d" -Destination "$q\$d"
  "DOSSIER`t$env:TEMP\$d`t$q\$d" | Tee-Object -Append $manifeste
}
```

**Vérifications** : chaque ligne du manifeste porte `IDENTIQUE` ; `%TEMP%` ne
contient plus ces éléments ; `icacls` de la quarantaine : une seule identité.

**Copies en clair après B** : les mêmes, **dans `coffre-sci4k\quarantaine`**, droits
restreints à vous ; `~/sauvegardes-sci4k`, le volume `3e0c…`, le volume `cde5…` et
`cle-essai.txt` inchangés.

**Retour arrière** : relire `MANIFESTE.txt` et redéplacer chaque élément vers son
chemin d'origine (colonne 2), puis contrôler l'empreinte (colonne 1).

**Risques** : un outil qui attendrait l'un de ces fichiers dans `%TEMP%` (aucun
connu) ; un déplacement interrompu (le manifeste dit où on en est) ; l'ACL de
l'espace de travail de Claude peut refuser l'accès au scratchpad (alors les éléments
du scratchpad restent en place, et la liste du manifeste le montre).

## 4. Opération D — mot de passe du conteneur jetable (après inventaire)

**Où la valeur actuelle subsiste (noms seulement)** :

| Emplacement | Nature |
|---|---|
| `scratchpad\restauration_e2e.sh`, `scratchpad\suite-procedure.md`, `%TEMP%\serve.sh` | scripts et notes **hors dépôt** qui l'écrivent en clair |
| Configuration des conteneurs `sci4k-restauration`, `sci4k-dev-smoke`, `-smoke2`, `-smoke3` (variables d'environnement) | **lisible par `docker inspect`**, même après changement du mot de passe |
| Journal de cette session de travail (`~/.claude/projects/…/*.jsonl`) | contient les commandes que j'ai lancées |
| Dépôt Git (`dev`, `master`, `preprod`) | **0 occurrence**, vérifié |

**Dépendances de l'outillage** : `tools/essai-b2-isole.sh` lit
`RESTAURATION_MOT_DE_PASSE` et `DB_PASSWORD` **dans l'environnement** : il accepte un
nouveau mot de passe sans réintroduire de secret dans le dépôt. Les scripts du
scratchpad devront être mis à jour ou abandonnés (ils sont hors dépôt).

**Limite à connaître** : changer le mot de passe **ne purge pas** l'ancien de la
configuration des quatre conteneurs ; seule la **suppression** de ces conteneurs
(famille C, séparée) le retire. D ferme l'accès réseau local à la base, pas la
lisibilité de l'ancienne valeur dans `docker inspect`.

**Commandes** (par vous, saisies masquées, rien dans un fichier) :

```bash
docker start sci4k-restauration && sleep 20
read -rs -p "Ancien mot de passe : " ANCIEN; echo
read -rs -p "Nouveau mot de passe (aleatoire, longueur >= 20) : " NOUVEAU; echo
export MYSQL_PWD="$ANCIEN"       # le client lit la variable ; docker exec -e MYSQL_PWD (sans valeur) la transmet
printf "ALTER USER 'root'@'%%' IDENTIFIED BY '%s'; ALTER USER 'root'@'localhost' IDENTIFIED BY '%s'; FLUSH PRIVILEGES;\n" "$NOUVEAU" "$NOUVEAU" \
  | docker exec -i -e MYSQL_PWD sci4k-restauration mysql -uroot
export MYSQL_PWD="$NOUVEAU"
docker exec -e MYSQL_PWD sci4k-restauration mysql -uroot -N -e "select 'nouveau mot de passe accepte'"
export MYSQL_PWD="$ANCIEN"
docker exec -e MYSQL_PWD sci4k-restauration mysql -uroot -N -e "select 1" 2>&1 | head -1   # attendu : Access denied
docker stop sci4k-restauration; unset ANCIEN NOUVEAU MYSQL_PWD
```

Le nouveau mot de passe est généré par vous (gestionnaire de mots de passe) ; il
n'apparaît ni dans une commande ni dans l'historique (`read -s`). **Il est transmis
au client par l'entrée standard et par l'environnement, jamais par un argument** : un
argument de `docker exec` se lit dans la liste des processus de la machine. (`printf`
est interne au shell : son texte n'est pas un processus.) **Non vérifié** : si MySQL
journalise ce texte (journal général ou binaire) ; à constater avant avec
`SHOW VARIABLES LIKE 'general_log'`.

**Vérifications** : le nouveau mot de passe est accepté, l'ancien refusé ; la
restauration isolée d'A fonctionne toujours (elle n'en dépend pas : conteneur sans
mot de passe, sans réseau).

**Retour arrière** : refaire `ALTER USER` avec l'ancien mot de passe si vous le
conservez ; **sinon**, recréer le conteneur depuis le volume ou reconstruire la
copie à partir de `~/sauvegardes-sci4k` (la copie est **reproductible** : l'export du
12/10 se restaure en conteneur isolé, §1.6). **Risque** : perdre le nouveau mot de
passe ⇒ copie à reconstruire.

## 5. Variables de production : une méthode qui ne fait pas sortir de valeur

**Fait.** L'aide de la CLI Railway dit que `--kv` et `--json` « impriment les valeurs
brutes » ; la recherche dans le schéma de l'API ne montre **aucune requête qui
renvoie seulement des noms**. **Toute lecture de variables fait donc transiter les
valeurs** par la mémoire d'un processus de votre session, même si rien n'est
affiché.

**Divulgation.** Pendant cette séance, **avant votre consigne d'attendre votre
autorisation**, j'ai lancé `railway variables --service sci4k --kv | cut -d= -f1`
(et pour `MySQL-1Luf`) pour recenser les **noms**. Les valeurs sont passées dans un
tube vers `cut`, **sans affichage, sans écriture sur disque, sans journal** ; seuls
les noms sont sortis. C'était une lecture des variables de production que vous
n'aviez pas autorisée expressément ; **je n'en fais plus aucune** avant votre
accord.

**Méthode proposée, de la moins à la plus intrusive** :

1. **Aucune lecture par moi (recommandée)** : vous ouvrez, dans le tableau de bord
   Railway, l'onglet *Variables* du service, valeurs masquées par l'interface, et
   vous comparez **les noms** à la liste blanche de `SECURISATION_COPIES_V2.md`, §8.5.
2. **Noms seulement, par tube** (si vous l'autorisez) :
   ```bash
   railway variable list --kv -s sci4k -e production | cut -d= -f1 | sort > /dev/stdout
   ```
   Les valeurs ne sont ni affichées ni écrites ; elles transitent par `cut`.
3. **Comparaison de valeurs sans les voir** (si vous l'autorisez, et seulement
   pour détecter une **copie** accidentelle entre production et préproduction) :
   ```bash
   h() { railway variable list --kv -s "$1" -e "$2" | while IFS== read -r k v; do printf '%s %s\n' "$k" "$(printf '%s' "$v" | sha256sum | cut -c1-64)"; done | sort; }
   join <(h sci4k production) <(h sci4k preprod) | awk '$2 == $3 {print $1}'   # n'imprime QUE les noms dont la valeur est identique
   ```
   Les empreintes ne passent que par des tubes (aucun fichier). **Limites** : elle lit
   quand même les valeurs de production ; une valeur à faible entropie (par exemple
   `APP_NAME`) est devinable par son empreinte, ce qui est sans enjeu pour des valeurs
   non secrètes ; les variables **scellées** ne sont pas listées par la CLI : **la clé
   Resend, absente de la liste des noms, est probablement scellée (hypothèse non
   vérifiée)** et ne pourrait donc pas être comparée.
   **Attendu** : aucun nom parmi `APP_KEY`, `PASSKEYS_USER_HANDLE_SECRET`,
   `DB_PASSWORD`, `MYSQL*`.

**Aucune de ces lectures n'est faite sans votre autorisation écrite.**

## 6. Préproduction Railway et B2 : inchangé, rien n'est créé

Plan, coûts basse / centrale / haute, limites et éléments de facture à confirmer :
`SECURISATION_COPIES_V2.md`, §8. Critères d'acceptation B2 : §9. Les lignes « 3 jours »
restent un calcul proportionnel **non confirmé** (pas de coût fixe connu : à
vérifier sur la facture). Aucun service, compte, bucket, clé ni secret n'est créé.

## 7. Récapitulatif : ce qui est fait et ce qui ne l'est pas

| Élément | État |
|---|---|
| Copie du 12/10 identifiée, empreinte et intégrité contrôlées | **fait** (lecture seule) |
| Script de chiffrement, vérification et restauration isolée | **écrit et essayé sur données fictives** ; **non essayé avec la fenêtre de saisie réelle** |
| Restauration isolée de l'export clair du 12/10 (sans réseau, en mémoire) | **faite** : 48 tables, 4 560 lignes |
| BitLocker | **non vérifié** (à faire par vous) |
| **A** chiffrement | **non exécuté** |
| **B** quarantaine | **non exécutée** |
| **D** mot de passe | **non exécuté** |
| **C** suppression | **non exécutée, non demandée** |
| Lecture des variables de production (méthodes 2 et 3) | **non faite** ; seule une lecture des noms a eu lieu (§5) |
| Préproduction, B2 | **rien créé** |

## 8. Mise à jour du 15 octobre 2026

**Ce qui a changé depuis la rédaction ci-dessus, constaté ce jour :**

1. **Les copies en clair de `%TEMP%` n'existent plus.** Absents au relevé du 15/10 :
   `full.sql`, `avant.sql`, `apres.sql` (×2), `cle-essai.txt`, `jar.txt`, `jar2.txt`,
   `q.b64`, `railway-up.log`, les trois dossiers `tmp.*`, `serve.sh`, et les anciens
   scripts du scratchpad. Ni dans la corbeille (0 élément), ni ailleurs dans le profil.
   **Cause non établie.** Indice : **Storage Sense** est activé avec le nettoyage des
   fichiers temporaires (`HKCU\…\StorageSense`, clé `04` = 1), et la coupure
   correspond à environ 48 heures (les fichiers du 13/10 10:48 existent, ceux du 12/10
   20:53 et antérieurs ont disparu). **Ce n'est pas une preuve.**
   Conséquences :
   - **`cle-essai.txt` perdue** : les objets d'essai chiffrés du volume `sci4k-s3`
     (6,4 Mo, copie de la base jetable) sont **définitivement illisibles** ; ils n'ont
     aucune valeur de récupération ;
   - **l'export du 6/10 (`full.sql`) n'existe plus.** Son équivalence avec un export de
     `~/sauvegardes-sci4k` n'ayant jamais été prouvée, **je ne peux pas dire qu'il est
     couvert** ; les exports du 6/10 15:39 et 16:43 l'encadrent ;
   - **la quarantaine (B) est sans objet pour ces fichiers** : il n'y a plus rien à
     déplacer. Une suppression système n'est pas un effacement sûr (SSD) : voir BitLocker.
2. **Ce qui existe toujours** (contrôlé le 15/10) : `~/sauvegardes-sci4k` **intact**
   (34 éléments, 12 exports, 5 copies de fichiers, rien de plus récent que le 12/10,
   11 empreintes `.sha256` toutes conformes, copie du 12/10 conforme) ; volumes Docker
   `3e0c2154…` (copie `railway` du 12/10 et dérivées) et `cde5c74d…` ; conteneurs
   `sci4k-restauration`, `sci4k-s3`, `sci4k-dev-smoke`, `-smoke2`, `-smoke3`
   (arrêtés), dont la configuration porte encore l'ancien mot de passe jetable.
3. **BitLocker, vérification partielle.** `manage-bde -status C:` exige un terminal
   administrateur (« accès refusé » dans ma session). Lecture sans droits : la propriété
   d'Explorateur `System.Volume.BitLockerProtection` vaut **1** (protection activée).
   **Non vérifiés par moi** : le pourcentage chiffré (100 % selon vous) et les
   protecteurs de clé. Pour les constater, dans un terminal administrateur :
   `manage-bde -status C:` puis `manage-bde -protectors -get C:` **sans copier la clé
   de récupération** ; relever seulement « Conversion Status », « Percentage
   Encrypted », « Protection Status » et les **types** de protecteurs (Mot de passe
   numérique, TPM…).
4. **Espace libre sur `C:`** : 50,5 Go (17,6 Go le 13/10).
5. **Dépôt** : `dev` = `4cc155b` après la fusion de la PR #20.

**Corrections de cette date :** l'export supplémentaire est désormais **facultatif**
(vide par défaut) dans `tools/securiser-copie-recuperation.sh` : le script pointait vers
`full.sql`, qui n'existe plus, et `controles` s'arrêtait. Le script de changement de
mot de passe est ajouté : `tools/changer-mot-de-passe-conteneur-jetable.sh` (§9).

## 9. Opération D prête : `tools/changer-mot-de-passe-conteneur-jetable.sh`

Essayé sur un **conteneur fictif** (mots de passe factices, réseau coupé) ; **non
lancé sur `sci4k-restauration`**. Il lit l'ancien et le nouveau mot de passe en saisie
masquée (ou sur l'entrée standard), exige 20 caractères au moins, refuse un nouveau
mot de passe identique à l'ancien ou contenant des caractères qui casseraient le SQL,
transmet le tout par l'entrée standard et par `MYSQL_PWD` (**jamais par un argument**),
vérifie que le nouveau est accepté **et** l'ancien refusé, puis liste (sans afficher
la valeur) les conteneurs `sci4k-*` dont la configuration contient encore l'ancien.

Constaté sur le conteneur fictif : 5 refus attendus (nom hors `sci4k-*`, trop court,
identique, caractère interdit, ancien faux ⇒ rien modifié) ; changement réussi ; nouveau
accepté, ancien refusé ; le conteneur factice est détecté comme portant l'ancienne
valeur. **Échantillonnage de la liste des processus pendant l'exécution** (toutes les
~0,5 s) : aucune ligne de commande ne contenait un mot de passe, **avec un contrôle
positif réussi** ; l'échantillonnage peut manquer un processus très bref, la garantie
repose donc surtout sur la lecture du script (aucun argument ne porte de valeur).

Dépendances de `sci4k-restauration` : plus aucun script hors dépôt ne contient le
mot de passe (les trois fichiers du §4 ont disparu) ; `tools/essai-b2-isole.sh` le lit
dans l'environnement ; les quatre configurations de conteneurs gardent l'ancienne
valeur **tant qu'elles ne sont pas supprimées** (opération C, séparée).

## 10. Opération A : la commande exacte

À lancer **par vous**, dans Git Bash, depuis le dépôt à jour (`git checkout dev && git pull`) :

```bash
cd "/c/Users/LENOVO/Desktop/4KID - Copie"
tools/securiser-copie-recuperation.sh controles   # lecture seule
tools/securiser-copie-recuperation.sh chiffrer    # une fenêtre vous demande la phrase, deux fois
tools/securiser-copie-recuperation.sh verifier    # la fenêtre la redemande, plusieurs fois
tools/securiser-copie-recuperation.sh restaurer   # la fenêtre la redemande une fois
```

**Saisie de la phrase.** `gpg` ouvre une fenêtre **« pinentry »** (de `gpg`, au premier
plan) : tapez la phrase **dans cette fenêtre seulement**, puis une seconde fois pour
confirmer (`chiffrer`). Elle n'est ni dans la commande, ni dans une variable, ni dans
un fichier, ni dans le terminal. **Avant**, enregistrez-la dans le gestionnaire de mots
de passe de l'organisation (deux personnes d'accès) : **perdue, la copie est
irrécupérable**. Si aucune fenêtre ne s'ouvre, arrêtez-vous et faites la répétition à
blanc du §2.

**Résultat attendu** : `~/coffre-sci4k` contient 3 fichiers (`base-….sql.gz.gpg`,
`fichiers-….tar.gpg`, plus les fichiers d'empreintes), la source est **inchangée**,
et `restaurer` affiche « 48 tables sur 48 attendues » dans un conteneur sans réseau.
**Rien n'est supprimé.**
