# Sécurisation des copies de production — inventaire complet et plan par opérations (v2, 13 octobre 2026)

**Remplace** les §2 et §3 de `PLAN_SECURISATION_COPIES_ET_PREPRODUCTION.md`.
**Rien n'est exécuté** : aucun fichier, volume, conteneur ni mot de passe n'a été
modifié, déplacé, chiffré ni supprimé. `~/sauvegardes-sci4k` est intact.
Aucun mot de passe, secret, contenu SQL, jeton ni clé n'est reproduit ici ; les
valeurs de variables Railway n'ont jamais été affichées (seulement leurs noms).

Trois familles d'opérations, **approuvées séparément** : **A** chiffrement
(n'altère aucun original), **B** déplacement (met en quarantaine, ne détruit rien),
**C** suppression (irréversible). **D** : changement de mot de passe du conteneur.

## 1. Inventaire complet

Colonnes : chemin absolu, taille, date de modification, SHA-256 (complet pour les
trois fichiers signalés, tronqué à 16 caractères ailleurs), nature, données
potentiellement sensibles, rôle. Relevé du 13 octobre 2026.

### 1.1 Fichiers

| Chemin | Taille | Date | SHA-256 | Nature | Données sensibles | Rôle |
|---|---|---|---|---|---|---|
| `C:\Users\LENOVO\AppData\Local\Temp\full.sql` | 442 281 o | 06/10 15:56 | `9b725f9a2a480b9f360b61947443fad388ea78ade30cb80e53c58186b3fa9a31` | export SQL de production (`mysqldump`, base `railway`) | tables `users`, `sessions`, `password_reset_tokens`, `journal_activites`, `membres_equipe`, `abonnes_newsletter`, `demandes_de_visite` | aucun rôle actuel : aucun script ni document ne le lit ; ancien point de contrôle |
| `…\Temp\claude\…\scratchpad\avant.sql` | 442 281 o | 06/10 15:56 | idem `9b725f9a…9a31` | **copie identique** de `full.sql` | idem | aucun |
| `…\Temp\cle-essai.txt` | 45 o | 12/10 20:53 | `33ef3bbe448c0a6aab37efce7671a87cdefab15d7fcf692b8777f80ccb8e281a` | clé de chiffrement **d'essai** (32 octets en base64) | la clé elle-même | lue par `restauration_e2e.sh` ; déchiffre les objets du volume `sci4k-s3` |
| `…\scratchpad\railway-up.log` | 277 484 o | 06/10 11:54 | `d30d526a0e2a42d7…` | journal de construction d'un déploiement (2 937 lignes) | aucun secret repéré : 2 lignes citent « key/token/secret » (un fichier JS compilé et le paquet `laravel/passkeys`) ; 3 hôtes cités (`deb.debian.org`, `railway.com`, `getcomposer.org`) | trace d'un déploiement du 6/10 ; aucun rôle |
| `…\scratchpad\jar.txt` | 934 o | 06/10 20:22 | `8f288f91a627ae0f…` | jarre de cookies Netscape, 2 entrées | un jeton CSRF `XSRF-TOKEN` de `www.sci4k.com`, **expiré le 06/10 22:22** | contrôle ponctuel ; aucun rôle |
| `…\scratchpad\jar2.txt` | 934 o | 12/10 15:16 | `2d9d5118d10d3448…` | idem | `XSRF-TOKEN`, **expiré le 12/10 17:16** | idem |
| `…\scratchpad\q.b64` | 4 032 o | 06/10 17:30 | `bc1960de4d6680a0…` | texte ASCII en base64, décodé en mémoire : **3 022 octets, une requête SQL `SELECT`** (0 `INSERT`/`UPDATE`/`DELETE`) | une requête, pas de données ; ce qu'elle nomme n'a pas été lu | requête passée à distance le 6/10 ; aucun rôle |
| `…\scratchpad\apres.sql` et `…\Temp\apres.sql` | 3 275 o chacun | 06/10 15:56 | `cc6dfad6b85ddfe9…` (identiques) | 12 `INSERT` dans `biens`, `bien_equipement`, `bien_service`, `photos_de_bien` | données de **bien immobilier** (fiche Villa F6), pas de données personnelles | ré-insertion du bien après nettoyage du 6/10 |
| `…\Temp\tmp.bKOIO5ZXv8\` | 31 fichiers, ≈ 13,6 Mo | 12/10 16:29 | `base.sql.gz` `a7a021c4f1becff…` ; `fichiers.tar.gz` `ce792e2b12685…` | **sauvegarde déchiffrée** de la copie jetable + `base.enc`, `fichiers.enc` (chiffrés), `x.gz` vide, `attendu.txt`/`restaure.txt` (comptes de lignes) ; dossier `f/` : `biens` 5, `comptes` 1, `equipe` 3 fichiers | tout le contenu de la base de la copie (§1.3) et **3 photos d'équipe + 1 photo de compte** | résidu de **mes** répétitions du 12/10 ; aucun rôle |
| `…\Temp\tmp.u9MuKmeISR\` | 31 fichiers, ≈ 13,6 Mo | 12/10 20:54 | `base.sql.gz` `2472eecc3fde636…` ; `fichiers.tar.gz` `4dd827e788510…` | idem + `manifeste.json` (728 o) | idem | idem |
| `…\Temp\tmp.CNz3BmSaf8\` | 6 fichiers, 15 Ko | 12/10 16:24 | `f.tar.gz` `c85f8045644fc80…` ; `t.php` `8ea48009d3bd39b…` | essai d'archive de photos de biens + script de test de 295 o | images de biens | essai d'archivage ; aucun rôle |

Droits : tout ce qui est sous `%TEMP%` **hérite** d'un groupe `CodexSandboxUsers` qui
peut **modifier**, de LENOVO, du Système et de deux SID de profil.

### 1.2 Conteneurs et volumes Docker

| Élément | Taille | Créé | Nature | Données sensibles | Rôle |
|---|---|---|---|---|---|
| Conteneur `sci4k-restauration` (`mysql:9.7.2`, arrêté) | — | 12/10 20:45 | MySQL jetable, port `127.0.0.1:33907` | voir volume | essais de restauration |
| Volume `3e0c2154e15b29adc6318c2db35ebaf9ae41c11f55b8d04d57b22f446e7d6c5c` | 259 Mo | 12/10 20:45 | données MySQL : bases `railway` (**copie de production du 12/10**), `restauration_essai`, `smoke_copie`, `sci4k_test` | §1.3 | contient la copie ; `smoke_copie` est lue par les conteneurs d'essai |
| Conteneur `sci4k-s3` (`chrislusf/seaweedfs`, arrêté) | — | 12/10 20:52 | stockage S3 local sans authentification | voir volume | répétition du dépôt B2 |
| Volume `cde5c74dd98acc4203f5cd7eff453258c044b4d160240cdefc02f3443d2fbc13` | 6,4 Mo | 12/10 20:52 | sauvegardes d'essai **chiffrées** de la copie | les mêmes données, chiffrées ; lisibles avec `cle-essai.txt` | répétition |
| Conteneurs `sci4k-dev-smoke`, `-smoke2`, `-smoke3` (arrêtés) | — | 13/10 | application construite depuis `dev`, branchée sur `smoke_copie` | accès à `smoke_copie` tant qu'ils sont démarrés | tests de fumée ; sans volume |
| Image `sci4k-dev-essai` | 1,11 Go | 13/10 | application seule | aucune donnée | tests de fumée |

### 1.3 Données sensibles de la copie (comptes de lignes, rien lu)

`users` 2 (adresses, empreintes de mots de passe, secrets 2FA éventuels, jetons
« se souvenir »), `sessions` 187 (adresses IP, agents, contenu), `password_reset_tokens`
4, `journal_activites` 254, `membres_equipe` 3 (dont l'adresse `audray@sci4k.com`),
`visites` 3 440 (sans IP). Pas de messages ni d'abonnés au 12/10 ; dans `full.sql`
du 6/10, les tables `abonnes_newsletter` et `demandes_de_visite` existent, **leur
contenu n'a pas été vérifié**.

### 1.4 `~/sauvegardes-sci4k` (`C:\Users\LENOVO\sauvegardes-sci4k`), à conserver intact

12 exports `base-*.sql.gz`, un export `base-20261001-1039.b64` (113 012 o), 5 copies de
fichiers (23 fichiers, 4,5 Mo chacune : 01/10, 06/10 ×3, 12/10), un dossier
d'essai de téléversement, 4 notes `bascule-*.md`.

| Export | Taille | Date | SHA-256 (16 car.) | `.sha256` |
|---|---|---|---|---|
| `base-20261001-1039.sql.gz` | 84 758 | 01/10 10:39 | `a620fbae9915e18f` | **absent** |
| `base-20261001-1715-avant-mysql-9.7.sql.gz` | 85 999 | 01/10 17:15 | `6298888699126ea5` | conforme |
| `base-20261006-1142-avant-deploiement-d7dfa1c.sql.gz` | 115 004 | 06/10 11:42 | `210657b746afe382` | conforme |
| `base-20261006-1306-avant-nettoyage-contenu.sql.gz` | 190 368 | 06/10 13:06 | `a62630b21977aa05` | conforme |
| `base-20261006-1508-avant-suppression-contenu-fictif.sql.gz` | 106 053 | 06/10 15:08 | `1472b4a675767426` | conforme |
| `base-20261006-1539-avant-rotation-app-key.sql.gz` | 119 366 | 06/10 15:39 | `033c4020219f2801` | conforme |
| `base-20261006-1643-avant-adresse-info-acsgroupe.sql.gz` | 136 376 | 06/10 16:43 | `5453919860b9a94c` | conforme |
| `base-20261006-1729-avant-suppression-messages-essai.sql.gz` | 142 758 | 06/10 17:29 | `aaa5d3ad31912867` | conforme |
| `base-20261006-1846-avant-deploiement-e645b3c.sql.gz` | 137 177 | 06/10 18:46 | `5d5dedcd548b0488` | conforme |
| `base-20261006-1911-avant-suppression-abonnes-essai.sql.gz` | 147 699 | 06/10 19:11 | `ad56958b0dc664a4` | conforme |
| `base-20261006-1947-avant-deploiement-b1a8c20.sql.gz` | 147 944 | 06/10 19:47 | `af0ab26aeb2e43f7` | conforme |
| `base-20261012-1554-point-de-retour-avant-corrections.sql.gz` | 179 107 | 12/10 15:54 | `4744a54a24ae6dd0` | conforme |
| `base-20261001-1039.b64` | 113 012 | 01/10 10:39 | `d2f51dd4336469a8` | **absent** |

- **Non chiffré** : 0 fichier EFS ; exports simplement compressés. Droits : LENOVO,
  Administrateurs, Système en contrôle total ; `CodexSandboxUsers` en lecture.
- **Contenu** : les mêmes tables sensibles que §1.3, à des dates différentes ; les
  exports antérieurs au 6/10 19:11 contiennent aussi les abonnés et messages d'essai
  supprimés ensuite.
- **C'est aujourd'hui la seule copie de récupération complète** de la production, et
  elle est **en clair**.

## 2. Ce qui est possible, constaté et non supposé

| Moyen | Constat | Conséquence |
|---|---|---|
| **GnuPG 2.4.9** (Git Bash) | **Essayé sur un fichier fictif** de 200 Ko : chiffrement symétrique AES-256 (`cipher 9`), dérivation SHA-512 à 65 011 712 itérations, contrôle d'intégrité (`mdc_method 2`) ; aller-retour d'empreinte identique ; **mauvaise phrase refusée** ; **fichier altéré refusé** | utilisable. **Effet de bord : l'essai a créé le dossier `C:\Users\LENOVO\.gnupg`** (vide de clés, sans danger) |
| Saisie de la phrase | `pinentry-w32` est présent : une fenêtre demande la phrase **à vous**. Non essayé en interactif (je ne le peux pas) | la phrase ne transite jamais par moi |
| Outil maison (libsodium, `tools/dechiffrer-sauvegarde.php`) | existe, déjà couvert par les tests ; format authentifié XChaCha20-Poly1305 | alternative si vous préférez une clé de 32 octets à une phrase |
| `7z`, `age` | absents | — |
| **BitLocker** | **non vérifiable** : `Accès refusé` sans terminal administrateur | **je n'en conclus rien** ; à contrôler par vous, §7 |
| EFS (chiffrement NTFS) | non activé sur le dossier de sauvegardes | non retenu : la clé dépend du profil Windows, perte = perte des données |
| Restauration isolée | conteneur `mysql:9.7.2` jetable déjà prêt, **déjà éprouvé** le 12–13/10 sur des objets chiffrés | utilisable |

## 3. Ordre d'intervention proposé

Principe : **la seule copie de récupération n'est jamais touchée avant qu'une copie
chiffrée n'ait été restaurée avec succès.**

| Étape | Famille | Effet | Copies en clair **après** l'étape |
|---|---|---|---|
| 0 | — | contrôle de BitLocker par vous (§7) | toutes (inchangé) |
| 1 | **A** | créer `coffre-sci4k`, droits restreints | toutes |
| 2 | **A** | chiffrer : dernier export + fichiers du 12/10, et `full.sql` | toutes ; **s'y ajoutent 3 fichiers chiffrés** |
| 3 | **A** | vérifier : en-têtes, empreintes en flux, mauvaise phrase refusée | toutes |
| 4 | **A** | **test de restauration isolée** depuis les copies chiffrées | toutes ; **+ une base temporaire** dans le conteneur jetable |
| — | | **point de décision** : la copie chiffrée est restaurée et conforme | |
| 5 | **B** | quarantaine : déplacer hors de `%TEMP%` les clairs superflus | **les mêmes, mais hors de `%TEMP%`**, droits restreints à votre compte |
| 6 | **D** | changer le mot de passe du conteneur jetable | idem |
| 7 | **C** | supprimer les clairs superflus de la quarantaine (`full.sql`, `avant.sql`, `tmp.*`, jarres, journal) | **restent** : `~/sauvegardes-sci4k` (intact, en clair), volume `3e0c…` (copie), volume `cde5…` + `cle-essai.txt` |
| 8 | **C** | après les derniers essais B2 / préproduction : supprimer volumes, conteneurs, `cle-essai.txt` | **reste** : `~/sauvegardes-sci4k` et le coffre chiffré |

`~/sauvegardes-sci4k` reste en clair à toutes les étapes, **par votre décision** :
la protection réelle repose alors sur BitLocker (étape 0) et sur le coffre chiffré.

## 4. Commandes exactes — **A : chiffrement** (non exécutées)

> **⚠ REMPLACÉ le 13 octobre 2026.** Les commandes ci-dessous, en particulier A5
> (la restauration), **ne doivent pas être suivies** : l'export du 12/10 contient
> `CREATE DATABASE` et `USE`, de sorte que la restauration écrirait dans la base
> `railway` du conteneur persistant en écrasant la copie. Utiliser
> `tools/securiser-copie-recuperation.sh` et `docs/OPERATIONS_SECURISATION_PREPAREES.md`.

À lancer par **vous**, dans **Git Bash**, `pinentry` demandera la phrase dans une
fenêtre. La phrase est **mise dans le gestionnaire de mots de passe de
l'organisation avant** le premier chiffrement, avec **deux personnes** d'accès.

```bash
# A1 — coffre aux droits restreints (PowerShell pour icacls)
mkdir -p ~/coffre-sci4k
powershell.exe -NoProfile -Command "icacls \"$env:USERPROFILE\coffre-sci4k\" /inheritance:r /grant:r \"${env:USERNAME}:(OI)(CI)F\"; icacls \"$env:USERPROFILE\coffre-sci4k\""
# Attendu : une seule identité (LENOVO). Rien d'autre.
```

```bash
# A2 — empreintes des ORIGINAUX, avant tout chiffrement (fichier d'empreintes, pas de contenu)
cd ~/coffre-sci4k
SRC=~/sauvegardes-sci4k
sha256sum "$SRC/base-20261012-1554-point-de-retour-avant-corrections.sql.gz" "$TEMP/full.sql" > EMPREINTES-origine.txt
( cd "$SRC" && find fichiers-20261012-1554-point-de-retour -type f -print0 | sort -z | xargs -0 sha256sum ) > EMPREINTES-fichiers-origine.txt
wc -l EMPREINTES-*.txt
```

```bash
# A3 — chiffrement AES-256 (la phrase est demandée par une fenêtre ; NE PAS la passer en argument)
cd ~/coffre-sci4k
G="gpg --symmetric --cipher-algo AES256 --s2k-digest-algo SHA512 --s2k-count 65011712"
$G -o base-20261012-1554.sql.gz.gpg "$SRC/base-20261012-1554-point-de-retour-avant-corrections.sql.gz"
tar -cf - -C "$SRC" fichiers-20261012-1554-point-de-retour | $G -o fichiers-20261012-1554.tar.gpg
$G -o export-20261006-1556.sql.gpg "$TEMP/full.sql"
ls -l *.gpg
```

```bash
# A4 — verifications cryptographiques (sans ecrire de clair sur le disque)
cd ~/coffre-sci4k
for f in *.gpg; do printf '%s : ' "$f"; gpg --list-packets "$f" 2>&1 | grep -E "cipher 9|mdc_method 2" | tr '\n' ' '; echo; done
# Attendu pour chaque fichier : "cipher 9" ET "mdc_method 2".
# Empreintes en flux, comparees aux originaux (la phrase est redemandee) :
gpg -d base-20261012-1554.sql.gz.gpg   | sha256sum | cut -c1-64
gpg -d export-20261006-1556.sql.gpg    | sha256sum | cut -c1-64
grep -E "base-20261012-1554|full.sql" EMPREINTES-origine.txt | cut -c1-64
# Attendu : chaque empreinte relue = l'empreinte d'origine, caractere pour caractere.
# Archive des fichiers : relire la liste et les empreintes (extraction dans un dossier de test, supprime ensuite)
V=$(mktemp -d); gpg -d fichiers-20261012-1554.tar.gpg | tar -xf - -C "$V"
( cd "$V" && find fichiers-20261012-1554-point-de-retour -type f -print0 | sort -z | xargs -0 sha256sum ) | diff - EMPREINTES-fichiers-origine.txt && echo "fichiers : identiques"
rm -rf "$V"
# Mauvaise phrase : doit etre REFUSEE (saisir volontairement une phrase fausse)
gpg -d base-20261012-1554.sql.gz.gpg > /dev/null && echo "ALERTE" || echo "refus constate"
```

```bash
# A5 — TEST DE RESTAURATION ISOLEE depuis la copie chiffree
# (conteneur jetable ; la base temporaire a un NOM NEUF ; jamais "railway")
docker start sci4k-restauration && sleep 20
read -rs -p "Mot de passe racine du conteneur jetable : " MYSQL_PWD; echo; export MYSQL_PWD   # saisi par vous
N=restauration_coffre_$(date +%Y%m%d_%H%M%S)
docker exec -i -e MYSQL_PWD="$MYSQL_PWD" sci4k-restauration mysql -uroot -e "create database $N character set utf8mb4"
gpg -d ~/coffre-sci4k/base-20261012-1554.sql.gz.gpg | zcat | docker exec -i -e MYSQL_PWD="$MYSQL_PWD" sci4k-restauration mysql -uroot "$N"
# Comparaison avec la copie "railway" du conteneur (chargee le 12/10 depuis la meme production), ligne a ligne :
for db in railway "$N"; do docker exec -e MYSQL_PWD="$MYSQL_PWD" sci4k-restauration sh -c "mysqldump -uroot --skip-comments --skip-extended-insert --no-create-info --order-by-primary $db 2>/dev/null | md5sum"; done
# Attendu : deux sommes identiques. Si elles different, ce n'est pas forcement un echec
# (la copie "railway" peut venir d'un autre export) : comparer alors les comptes de lignes table par table.
docker exec -e MYSQL_PWD="$MYSQL_PWD" sci4k-restauration mysql -uroot -e "drop database $N"   # supprime SEULEMENT la base de test creee ci-dessus
docker stop sci4k-restauration
unset MYSQL_PWD
```

**Critères de réussite de A** : les trois fichiers affichent `cipher 9` et
`mdc_method 2` ; les empreintes relues égalent celles d'origine ; la mauvaise phrase
est refusée ; la restauration crée les 48 tables ; la comparaison avec `railway` est
identique (ou expliquée) ; la phrase est dans le gestionnaire de mots de passe,
deux accès confirmés **par vous**.

## 5. Commandes exactes — **B : déplacement en quarantaine** (non exécutées)

Seulement après le **point de décision** (A réussi). Sur le même disque : un
déplacement conserve les empreintes ; il retire seulement les fichiers de `%TEMP%`
(hérité par `CodexSandboxUsers`).

```powershell
# B1 — dossier de quarantaine aux droits restreints
$q = "$env:USERPROFILE\coffre-sci4k\quarantaine"
New-Item -ItemType Directory -Force $q | Out-Null
icacls $q /inheritance:r /grant:r "${env:USERNAME}:(OI)(CI)F" | Out-Null
icacls $q
```

```powershell
# B2 — deplacer, en verifiant l'empreinte avant et apres
$sp = "$env:TEMP\claude\C--Users-LENOVO-Desktop-4KID---Copie\79b26cd2-d995-417a-9be3-efb5b81b9b40\scratchpad"
$elements = @("$env:TEMP\full.sql","$sp\avant.sql","$env:TEMP\apres.sql","$sp\apres.sql","$sp\jar.txt","$sp\jar2.txt","$sp\q.b64","$sp\railway-up.log")
foreach ($f in $elements) {
  $h1 = (Get-FileHash $f -Algorithm SHA256).Hash
  $dest = Join-Path $q ((Split-Path $f -Parent | Split-Path -Leaf) + "__" + (Split-Path $f -Leaf))
  Move-Item $f $dest
  $h2 = (Get-FileHash $dest -Algorithm SHA256).Hash
  "{0}  {1}" -f ($(if ($h1 -eq $h2) {'IDENTIQUE'} else {'DIFFERENT'})), (Split-Path $f -Leaf)
}
foreach ($d in "tmp.bKOIO5ZXv8","tmp.u9MuKmeISR","tmp.CNz3BmSaf8") { Move-Item "$env:TEMP\$d" "$q\$d" }
Get-ChildItem $q -Recurse -File | Measure-Object | ForEach-Object { "fichiers en quarantaine : $($_.Count)" }
```

**Critères de B** : chaque ligne `IDENTIQUE` ; `%TEMP%` ne contient plus aucun de ces
éléments ; `icacls` de la quarantaine ne montre que votre compte. **Copies en clair
après B** : les mêmes, **dans `coffre-sci4k\quarantaine`**.
Les deux conteneurs `sci4k-s3` et `sci4k-restauration` et `cle-essai.txt` **ne sont
pas déplacés** (le script de répétition la lit dans `%TEMP%`).

## 6. Commandes exactes — **D : mot de passe** puis **C : suppression** (non exécutées)

```bash
# D — changer le mot de passe racine du conteneur jetable (saisi par vous, jamais affiche)
docker start sci4k-restauration && sleep 20
read -rs -p "Ancien mot de passe : " ANCIEN; echo
read -rs -p "Nouveau mot de passe : " NOUVEAU; echo
docker exec -i -e MYSQL_PWD="$ANCIEN" sci4k-restauration mysql -uroot -e "ALTER USER 'root'@'%' IDENTIFIED BY '$NOUVEAU'; ALTER USER 'root'@'localhost' IDENTIFIED BY '$NOUVEAU'; FLUSH PRIVILEGES;"
docker exec -e MYSQL_PWD="$NOUVEAU" sci4k-restauration mysql -uroot -N -e "select 'nouveau mot de passe accepte'"
docker stop sci4k-restauration; unset ANCIEN NOUVEAU
```

```powershell
# C1 — suppression des clairs superflus (apres B ; le coffre chiffre est restaure et verifie)
$q = "$env:USERPROFILE\coffre-sci4k\quarantaine"
Get-ChildItem $q -Recurse -File | Select-Object FullName,Length          # revoir la liste AVANT
Remove-Item $q -Recurse -Force -Confirm
cipher /w:"$env:USERPROFILE\coffre-sci4k"        # facultatif, lent : ecrase l'espace libre du dossier (SSD : effet limite)
```

```bash
# C2 — fin de vie des conteneurs, volumes et de la cle d'essai (SEULEMENT apres les derniers essais, sur confirmation ecrite)
docker rm sci4k-dev-smoke sci4k-dev-smoke2 sci4k-dev-smoke3 sci4k-s3 sci4k-restauration
docker volume rm 3e0c2154e15b29adc6318c2db35ebaf9ae41c11f55b8d04d57b22f446e7d6c5c cde5c74dd98acc4203f5cd7eff453258c044b4d160240cdefc02f3443d2fbc13
rm -f "$TEMP/cle-essai.txt"
docker image rm sci4k-dev-essai          # facultatif : l'image ne contient aucune donnee
docker ps -a --format '{{.Names}}' | grep sci4k; docker volume ls | grep -E '3e0c2154|cde5c74d'   # attendu : rien
```

**Ce que C ne touche jamais** : `~/sauvegardes-sci4k`, le coffre chiffré.
Sur SSD, la suppression ne garantit pas l'effacement des blocs : seul BitLocker
(étape 0) protège ce résidu.

## 7. Étape 0 — BitLocker, à faire par vous

Dans un **terminal administrateur** : `manage-bde -status C:` (ou
Paramètres → Confidentialité et sécurité → Chiffrement de l'appareil). Si
« Chiffrement » n'est pas actif sur `C:`, **toute copie en clair de ce poste reste
lisible par quiconque prend le disque** : c'est alors le premier risque à traiter,
avant tout le reste.

## 8. Préproduction Railway : plan d'exécution révisé

**Aucun service, aucune base, aucune variable n'est créé.**

### 8.1 Tarifs vérifiés le 13 octobre 2026

Page des plans de Railway : Hobby **5 $/mois** (payé même sans usage) avec **5 $
d'usage inclus** par cycle, non reportables ; au-delà, la différence est facturée ;
mémoire **10 $/Go/mois** (0,000231 $/Go/min), processeur **20 $/vCPU/mois**
(0,000463 $/vCPU/min), volume **0,15 $/Go/mois**, sortie réseau **0,05 $/Go** ;
plafonds par service : volume 5 Go, mémoire 48 Go, 48 vCPU, 6 réplicas.

### 8.2 Consommation mesurée de la production (7 jours, lecture seule)

Web : CPU 0,0037 vCPU moyen (max 0,010), mémoire 0,287 Go (max 0,756), disque 0,04 Go,
sortie 0,19 Go. MySQL : CPU 0,0030 vCPU (max 0,0059), mémoire 0,409 Go (max 0,484),
disque 0,21 Go. Volumes : `mysql-volume` 500 Mo (147 Mo utilisés).

### 8.3 Estimation : basse, centrale, haute (préproduction qui tourne 24 h/24)

| Poste | **Basse** (inactive, MySQL allégé) | **Centrale** (moyennes mesurées) | **Haute** (pics de campagne, volumes au plafond) |
|---|---|---|---|
| Web, mémoire | 0,25 Go → 2,50 $ | 0,287 Go → 2,87 $ | 0,756 Go → 7,56 $ |
| MySQL, mémoire | 0,35 Go → 3,50 $ | 0,409 Go → 4,09 $ | 0,484 Go → 4,84 $ |
| Processeur (2 services) | 0,01 vCPU → 0,20 $ | 0,0067 vCPU → 0,13 $ | 0,10 vCPU → 2,00 $ |
| Volumes | 0,5 Go → 0,08 $ | 1 Go alloué → 0,15 $ | 10 Go (2 × 5 Go) → 1,50 $ |
| Trafic sortant | ≈ 0 | 1 Go → 0,05 $ | 5 Go → 0,25 $ |
| **Total mensuel, 24 h/24** | **≈ 6,3 $** | **≈ 7,3 $** | **≈ 16,2 $** |
| Même environnement **3 jours seulement** (×72/730) | ≈ 0,6 $ | ≈ 0,7 $ | ≈ 1,6 $ |

**Les lignes « 3 jours » sont un produit proportionnel, non une prévision de
facture** : elles supposent une facturation à la minute, uniquement pendant que les
services tournent, **sans coût fixe par environnement ni minimum**. Rien dans la
page lue ne le confirme ni ne l'infirme. **Ne pas retenir 0,70 $ comme un
engagement.**

### 8.4 Ce que la facture réelle doit confirmer (je ne l'ai pas vue)

1. **L'offre** : Hobby est mémorisé, pas constaté ; un plan Pro changerait tout
   (abonnement par siège, plafonds différents).
2. **L'usage déjà facturé** : la même méthode appliquée à la production donne
   ≈ 7,3 $/mois, soit **au-dessus du crédit inclus de 5 $**. Si c'est exact,
   **chaque dollar de préproduction se paie intégralement** ; si la production
   reste sous 5 $, une partie serait absorbée par le crédit.
3. **La base de facturation des volumes** : taille allouée ou occupation.
4. **Les coûts fixes éventuels** : par environnement, par service, par volume.
5. **Le nombre d'environnements et de services permis** sur l'offre (non précisé
   par la page lue).
6. **La ventilation par service** du cycle en cours (tableau de bord → Usage).

### 8.5 Procédure garantissant qu'aucun secret de production n'est copié

Variables de production **par noms** (valeurs jamais lues) : `APP_DEBUG`, `APP_ENV`,
`APP_FALLBACK_LOCALE`, `APP_KEY`, `APP_LOCALE`, `APP_NAME`, `APP_URL`, `CACHE_STORE`,
`DB_CONNECTION`, `DB_DATABASE`, `DB_HOST`, `DB_PASSWORD`, `DB_PORT`, `DB_USERNAME`,
`LOG_CHANNEL`, `LOG_LEVEL`, `LOG_STACK`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`,
`MAIL_MAILER`, `PASSKEYS_USER_HANDLE_SECRET`, `QUEUE_CONNECTION`, `SESSION_DRIVER`,
`SESSION_SECURE_COOKIE`, `TRUSTED_PROXIES` (+ 14 `RAILWAY_*` automatiques). MySQL :
`MYSQLDATABASE`, `MYSQLHOST`, `MYSQLPASSWORD`, `MYSQLPORT`, `MYSQLUSER`,
`MYSQL_DATABASE`, `MYSQL_ROOT_PASSWORD`, `MYSQL_URL`.

**Constat à ne pas ignorer** : aucune variable de clé Resend n'apparaît dans cette
liste. L'envoi de courrier fonctionne pourtant. **Hypothèse, non vérifiée** : la clé
est une variable **scellée** (non listée par la CLI) ou stockée autrement. Elle
**existe donc probablement** dans l'environnement de production **sans que je puisse
la voir ni la comparer** ; c'est la raison de la règle « ne rien dupliquer ».

Procédure :

1. **Ne pas dupliquer** l'environnement de production. Créer un environnement
   **vide** (⚠ intitulé exact de l'écran non vérifié).
2. Ajouter **un nouveau MySQL 9.7.2** et **un nouveau service web**, sans lien avec
   les services de production.
3. Poser **uniquement** cette liste blanche, avec **des valeurs neuves** :

   | Variable | Valeur en préproduction |
   |---|---|
   | `APP_KEY` | nouvelle clé (`php artisan key:generate --show`, jamais celle de production) |
   | `PASSKEYS_USER_HANDLE_SECRET` | nouvelle valeur aléatoire |
   | `APP_ENV` | `production` (même comportement) |
   | `APP_DEBUG` | `false` (le conteneur refuse sinon) |
   | `APP_URL` | l'adresse provisoire `*.up.railway.app` de **cet** environnement |
   | `DB_*` | pointent vers le **MySQL de préproduction** (références du service, ⚠ syntaxe à vérifier) |
   | `MAIL_MAILER` | `log` |
   | `LOG_CHANNEL` | `stderr` |
   | `SAUVEGARDE_*`, clés Resend, B2, Sentry | **absentes** |
   | `APP_NAME`, `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, `SESSION_SECURE_COOKIE`, `TRUSTED_PROXIES`, `LOG_LEVEL`, `LOG_STACK`, `MAIL_FROM_*` | valeurs non secrètes, recopiées à la main |

4. **Contrôle croisé sans afficher de valeur** : comparer les **empreintes** (et non
   les valeurs) des variables des deux environnements ; le script n'imprime que les
   **noms** dont l'empreinte est identique. Attendu : aucun nom parmi `APP_KEY`,
   `PASSKEYS_USER_HANDLE_SECRET`, `DB_PASSWORD`, `MYSQL*`. Ce contrôle **lit** les
   variables de production dans la mémoire du processus : il demande votre accord.
5. **Aucun mail réel** : `MAIL_MAILER=log` ; contrôle : soumettre un formulaire de
   contact et lire le journal, **sans** passer par la boîte de réception.
6. **Données de test seulement** : `db:seed` (structure) puis `DemonstrationSeeder`
   (fiches `DEMO-`) ; **aucune** copie de la base de production, aucun compte réel :
   créer un compte de test avec `php artisan compte:creer-administrateur` et un
   mot de passe jetable de préproduction.
7. **Indexation** : case décochée (défaut d'une base neuve) ; domaine provisoire non
   diffusé.
8. **Déploiement manuel** : `railway up --service sci4k --environment <nom>` ;
   jamais de déploiement automatique depuis GitHub.
9. **Fin de campagne** : supprimer l'environnement entier (services et volumes)
   pour arrêter toute facturation ; vérifier dans la facture qu'aucune ligne ne
   subsiste.

**Configuration minimale** : 1 environnement, 1 service web (1 réplique), 1 MySQL
9.7.2, 2 volumes de 500 Mo, pas de domaine personnalisé, pas de sauvegarde.

## 9. Critères d'acceptation du futur test Backblaze B2

Aucun compte, bucket, clé ni secret n'est créé. Le test n'est « réussi » que si
**chaque** critère est constaté et consigné (date, sortie, capture), et **échoue**
dès qu'un seul manque.

| # | Critère | Constat exigé |
|---|---|---|
| 1 | **Compte et bucket** | compte au nom de l'organisation ; bucket **d'essai** privé dont le nom contient « essai » ; liste des buckets relevée |
| 2 | **Permissions minimales** | clé limitée à ce bucket ; capacités relevées dans la console : *listFiles*, *readFiles*, *writeFiles* seulement ; **absence de *deleteFiles*** notée |
| 3 | **Refus de suppression constaté** | `sauvegarde:verifier-config` affiche « Suppression : REFUSÉE par le fournisseur » **et** l'objet de contrôle subsiste sous `_essais/` (vérifié en console) |
| 4 | **Clé de chiffrement séparée** | clé d'essai générée **hors** du dépôt, de Railway et du dossier de travail ; lieu de conservation désigné ; au moins deux personnes y accèdent ; **jamais** la clé de production |
| 5 | **Transfert** | `tools/essai-b2-isole.sh` : « Sauvegarde réussie et vérifiée » avec les tables et lignes attendues |
| 6 | **Intégrité** | empreintes du chiffré = manifeste ; empreintes du clair = export ; octet pour octet |
| 7 | **Alerte en cas d'échec** | contrôle de surveillance créé ; **fausse panne provoquée** (aucun signal) ; courriel d'alerte **reçu** par l'adresse prévue, heure relevée |
| 8 | **Alerte de non-exécution** | absence de signal à l'heure attendue détectée (période de grâce relevée) |
| 9 | **Aucun faux succès** | un dépôt volontairement interrompu ou une clé fausse **n'envoie pas** le signal de succès |
| 10 | **Restauration réussie, isolée** | restauration dans une base **neuve** d'un conteneur jetable : mêmes 48 tables, mêmes comptes de lignes, fichiers identiques ; aucune écriture en production |
| 11 | **Rétention** | règles 14 / 56 / 1 jour créées et visibles ; aucune suppression prématurée constatée |
| 12 | **Dans le conteneur Railway** | `sauvegarde:externe` réussi depuis la préproduction (§8), pas depuis la production |
| 13 | **Premier passage planifié** | objets présents le lendemain à 03:20 UTC et signal de succès reçu |
| 14 | **Aucun secret fuité** | sortie, journaux et dépôt relus : aucune clé, aucun secret |

**Aucun de ces critères n'est rempli à ce jour** : les critères 5, 6 et 10 n'ont été
répétés qu'en local, contre un stockage sans authentification, ce qui ne vaut pas
validation.
