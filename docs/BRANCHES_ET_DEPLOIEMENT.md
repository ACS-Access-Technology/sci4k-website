# Branches, fusions et mise en ligne

Comment un changement voyage de `dev` jusqu'au site en ligne, ce que le dépôt
contrôle de lui-même, et ce qui doit être réglé à la main dans GitHub.

## 1. Les trois branches

| Branche | Rôle |
|---|---|
| `dev` | La branche de travail. On y pousse directement. |
| `preprod` | La préproduction : ce qui a été validé sur `dev` et attend sa mise en ligne. |
| `master` | **La production.** Le site en ligne est — et doit toujours être — le contenu de `master`. |

Il n'y a pas de branche `prod` : `master` tient ce rôle (voir
`MISE_EN_LIGNE.md`, « Les branches »).

## 2. Le flux obligatoire

```
dev  →  preprod  →  master  →  mise en ligne (railway up)
```

Une demande de fusion vers `preprod` vient **de `dev`** ; vers `master`, **de
`preprod`**. Aucune autre combinaison n'est admise — pas même pour un correctif
urgent (§ 9).

## 3. Les demandes de fusion (PR)

- Vers `preprod` et vers `master`, **tout passe par une demande de fusion** :
  une fois les protections posées (§ 13), GitHub refuse les poussées directes.
- La demande n'est fusionnable que si **tous les checks obligatoires sont verts**
  (§ 6). **Aucune approbation** n'est exigée : les checks tiennent ce rôle.
- **Les administrateurs sont soumis aux mêmes règles**, sans contournement
  possible.
- Sur `dev`, pas de demande de fusion : on y pousse, et la CI y tourne à chaque
  poussée, sans rien bloquer.

## 4. La méthode de fusion : des commits de fusion

La fusion garde un **commit de fusion** (« Create a merge commit »). Ni
« squash », ni « rebase », ni historique linéaire ne sont imposés.

Conséquence connue, et sans effet : `master` porte des commits de fusion que
`dev` et `preprod` n'ont pas. Le **contenu** des trois branches reste le même ;
seul l'historique diffère. Rien n'oblige à les réaligner.

Pour la même raison, « Require branches to be up to date before merging » reste
**désactivé** : il obligerait à refusionner `master` dans `preprod`, puis dans
`dev`, à chaque passage. La CI teste déjà le **résultat de la fusion** (elle
tourne sur l'événement `pull_request`).

## 5. Les protections de chaque branche

| Règle | `master` | `preprod` | `dev` |
|---|---|---|---|
| Demande de fusion obligatoire | oui | oui | non |
| Approbations requises | 0 | 0 | — |
| Checks obligatoires | les cinq du § 6 | les cinq du § 6 | aucun |
| Branche à jour avant fusion | non | non | — |
| Historique linéaire | non | non | non |
| Poussée forcée (« force push ») | interdite | interdite | interdite |
| Suppression de la branche | interdite | interdite | interdite |
| Administrateurs soumis aux règles | oui | oui | oui |
| Contournement (« bypass ») | aucun | aucun | aucun |

**État actuel (30 septembre 2026) : aucune de ces protections n'est posée.**
Les trois branches sont sans protection et aucun ruleset n'existe — vérifié par
l'API de GitHub. Tant qu'elles ne le sont pas, une CI en échec n'empêche ni une
poussée, ni une fusion : elle se voit, c'est tout.

## 6. Les checks obligatoires

Pour `preprod` et `master`, exactement ces cinq — le nom est celui qu'affiche
GitHub, c'est-à-dire le `name:` du job :

| Check | Workflow | Ce qu'il vérifie |
|---|---|---|
| `Contrôles de non-régression` | `verification.yml` | Références, données structurées, formulaires, maquettes du backoffice |
| `Tests Laravel` | `verification.yml` | Pint, phpstan, la suite sur SQLite, MySQL 9.7 et 9.4 |
| `Paquets PHP` | `audit-dependances.yml` | Failles connues des paquets PHP livrés (voir `SECURITE_DEPENDANCES.md`) |
| `Paquets npm` | `audit-dependances.yml` | Failles hautes et critiques des paquets npm |
| `Flux des branches` | `flux-des-branches.yml` | L'origine de la demande : `dev` vers `preprod`, `preprod` vers `master` |

**GitHub ne propose un check qu'après l'avoir vu tourner au moins une fois.**
Au 30 septembre 2026, seuls `Contrôles de non-régression` et `Tests Laravel`
ont déjà tourné ; `Paquets PHP`, `Paquets npm` et `Flux des branches`
apparaîtront après le premier push et la première demande de fusion (§ 13).

Le check `Flux des branches` ne tourne **que sur les demandes de fusion vers
`preprod` et `master`**. Vers toute autre branche, il ne tourne pas, et rien
n'est exigé. Il est relancé quand la cible d'une demande change : une demande
ouverte vers `preprod` puis réorientée vers `master` est recontrôlée. Une
branche homonyme venue d'un fork (« dev » d'un autre dépôt) est refusée.

## 7. SonarCloud : visible, pas obligatoire

Le check `SonarCloud Code Analysis` s'affiche sur chaque commit, mais **n'est
pas obligatoire**. Au 30 septembre 2026, sa « Quality Gate » échoue sur `master`
(fiabilité C et sécurité B sur le code nouveau, 46 anomalies antérieures à la
préparation de la mise en ligne). Une bonne part ressemble à des faux positifs
— composants Livewire en un fichier, partiels de gabarit sans `<title>`,
empreinte SHA-1 dans un test standard de Laravel — et une partie mérite
correction (champs de filtre sans libellé). Le rendre obligatoire bloquerait
aujourd'hui toute fusion.

Il le deviendra une fois les anomalies triées dans SonarCloud et corrigées : il
suffira alors de l'ajouter aux checks obligatoires (§ 13).

## 8. La promotion normale

1. Travailler et pousser sur `dev`. La CI tourne à chaque poussée.
2. Ouvrir une demande de fusion **`dev` → `preprod`**. Attendre les cinq checks
   verts, puis fusionner (commit de fusion).
3. Vérifier ce qui doit l'être en préproduction.
4. Ouvrir une demande de fusion **`preprod` → `master`**. Attendre les cinq
   checks verts, puis fusionner.
5. Attendre que la CI ait tourné **sur le commit de fusion de `master`** — la
   poussée de la fusion la déclenche —, puis mettre en ligne (§ 10).

## 9. Le correctif urgent

Même chemin, sans exception : le correctif est poussé sur `dev`, puis suit
`dev` → `preprod` → `master` par deux demandes de fusion, chacune avec ses
checks verts. Le flux est court quand les branches sont à jour : quelques
minutes de CI par étape. Il n'existe ni contournement d'urgence, ni droit
d'administrateur pour s'en passer.

## 10. La mise en ligne

La mise en ligne reste un geste manuel, depuis un poste. **Elle n'est jamais
déclenchée par GitHub** : aucun workflow n'appelle Railway.

```bash
git switch master
git pull
./tools/verifier-avant-deploiement.sh && railway up --service sci4k
```

`railway up` téléverse le **dossier de travail**, pas un commit : sans la
vérification, il enverrait la branche en cours, des fichiers modifiés, ou un
`master` dont la CI a échoué.

## 11. `tools/verifier-avant-deploiement.sh`

Il ne déploie rien : il répond 0 si le dossier peut partir, 1 sinon. Il vérifie,
dans l'ordre :

1. que `git`, `curl` et `node` sont installés ;
2. que le dossier est un dépôt git ;
3. que la branche courante est `master` ;
4. que l'arbre de travail est propre, **fichiers non suivis compris** —
   `railway up` les enverrait ;
5. que `master` est identique à `origin/master`, relu à l'instant ;
6. que le commit est celui attendu — celui d'`origin/master`, ou celui passé
   par `--commit <empreinte>` ;
7. que les checks `Contrôles de non-régression`, `Tests Laravel`, `Paquets PHP`
   et `Paquets npm` sont **terminés et verts pour ce commit** (la dernière
   exécution de chacun fait foi).

`Flux des branches` n'y figure pas : il ne tourne que sur les demandes de
fusion, pas sur le commit de `master`. SonarCloud n'y figure pas : il est
informatif (§ 7).

**Tout ce qui ne peut pas être vérifié est un refus** : un réseau coupé, une
réponse de l'API illisible, un outil absent ou un check encore en cours ne
valent jamais « vert ».

L'état de la CI est lu dans l'API publique de GitHub (le dépôt est public),
sans compte. `GITHUB_TOKEN`, s'il est posé dans l'environnement, est joint à la
requête : il lève la limite de requêtes sans compte (60 par heure) et suffira
le jour où le dépôt deviendrait privé. `SCI4K_DEPOT_GITHUB=proprietaire/depot`
sert si `origin` ne pointe pas sur GitHub.

```bash
./tools/verifier-avant-deploiement.sh --aide
./tools/verifier-avant-deploiement.sh --commit 1a2b3c4
```

**Au 30 septembre 2026, il refuse le `master` en ligne sur GitHub** : `Paquets
PHP` et `Paquets npm` n'y ont jamais tourné. C'est voulu — le premier push fera
tourner l'audit, et le commit suivant de `master` sera vérifiable.

## 12. Si le script refuse

Le message dit pourquoi. **Ne pas lancer `railway up`.**

| Refus | Que faire |
|---|---|
| Branche courante ≠ `master` | `git switch master && git pull` |
| Arbre pas propre | Valider les changements sur `dev` et leur faire suivre le flux, ou les écarter (`git stash -u`) |
| `master` en retard sur `origin/master` | `git pull` |
| `master` en avance | Ces commits n'ont pas suivi le flux : les reprendre sur `dev` ; ils ne se déploient pas |
| Commit différent de `--commit` | Vérifier l'empreinte annoncée, ou relancer sans `--commit` |
| Check absent | La CI n'a pas tourné pour ce commit (ou un job a été renommé) : relancer le workflow dans GitHub, ou pousser à nouveau |
| Check en cours | Attendre la fin, relancer le script |
| Check en échec | Corriger sur `dev` et refaire le flux ; ce commit ne se déploie pas |
| API injoignable ou limite atteinte | Réessayer plus tard, ou poser `GITHUB_TOKEN` (jeton en lecture seule) |
| Outil absent | Installer `git`, `curl` ou `node` |

Le refus ne se contourne pas : le script n'a pas d'option pour ignorer une
vérification.

## 13. Appliquer les protections dans GitHub (à la main)

**Fait le 1er octobre 2026**, et vérifié dans l'API de GitHub : `master` et
`preprod` exigent les cinq checks ci-dessous, sans contournement possible ;
`dev` interdit la suppression et la réécriture forcée. La procédure reste
ici pour un nouveau dépôt, ou si une règle devait être refaite.

Le check `Flux des branches` n'apparaît dans la liste de GitHub qu'après
avoir tourné une fois : ouvrir d'abord une demande de fusion `dev` →
`preprod`.

Rien de ceci n'est fait par le dépôt : il faut un compte **administrateur** du
dépôt `ACS-Access-Technology/sci4k-website`.

**Ordre à suivre**, faute de quoi certains checks ne pourront pas être choisis :

1. **Pousser** les changements sur `dev` (après les avoir relus et commités).
   Vérifier dans *Actions* que « Vérification du site » et « Audit des
   dépendances » tournent et passent.
2. Ouvrir une demande de fusion **`dev` → `preprod`**. Le workflow « Flux des
   branches » tourne pour la première fois ; les cinq checks apparaissent sur
   la demande. Ne pas encore fusionner si l'on veut poser les protections
   d'abord — ou fusionner, puis poser.
3. *Settings* → *Branches* → *Add branch protection rule* (ou *Rules* →
   *Rulesets*, qui offre les mêmes réglages). Pour **`master`** :
   - *Branch name pattern* : `master` ;
   - **Require a pull request before merging** : coché ;
     - *Require approvals* : **décoché** (0 approbation) ;
   - **Require status checks to pass before merging** : coché ;
     - *Require branches to be up to date before merging* : **décoché** ;
     - checks à ajouter, en tapant leur nom : `Contrôles de non-régression`,
       `Tests Laravel`, `Paquets PHP`, `Paquets npm`, `Flux des branches` ;
     - **ne pas** ajouter `SonarCloud Code Analysis` ;
   - *Require linear history* : **décoché** ;
   - **Do not allow bypassing the above settings** : coché (les
     administrateurs sont soumis aux règles) ;
   - *Allow force pushes* : **décoché** ;
   - *Allow deletions* : **décoché** ;
   - enregistrer.
4. Même règle pour **`preprod`** (pattern `preprod`), à l'identique.
5. Pour **`dev`** (pattern `dev`) : *Require a pull request* **décoché**,
   *Require status checks* **décoché**, *Allow force pushes* **décoché**,
   *Allow deletions* **décoché**, *Do not allow bypassing* coché.
6. *Settings* → *General* → *Pull Requests* : garder **Allow merge commits**
   coché (les deux autres méthodes peuvent rester permises, elles ne sont pas
   imposées).
7. **Vérifier, sans rien risquer** :
   - *Code* → *Branches* : `master`, `preprod` et `dev` portent l'étiquette
     « Protected » ;
   - ouvrir une demande `dev` → `master` : le check `Flux des branches` doit
     échouer et le bouton de fusion rester bloqué. La **fermer sans la
     fusionner** ;
   - ne pas « tester » en poussant sur `master` : si une règle était mal posée,
     la poussée passerait.

## 14. Ce qui est automatique, ce qui est réglé à la main

| Élément | Où | État |
|---|---|---|
| CI de non-régression et tests | Dépôt (`verification.yml`) | Tourne à chaque poussée et demande de fusion |
| Audit des dépendances | Dépôt (`audit-dependances.yml`) | Tourne aussi chaque lundi sur `master` |
| Contrôle du flux des branches | Dépôt (`flux-des-branches.yml`) | Tourne sur les demandes vers `preprod` et `master` |
| Vérification avant mise en ligne | Dépôt (`tools/verifier-avant-deploiement.sh`) | À lancer à la main avant `railway up` |
| Rendre les checks **bloquants** | **GitHub** (§ 13) | **À faire** |
| Interdire poussées directes, forcées, suppressions | **GitHub** (§ 13) | **À faire** |
| SonarCloud obligatoire | GitHub, plus tard (§ 7) | Volontairement non |
| La mise en ligne | Poste, `railway up` | Manuelle |

Tant que les protections ne sont pas posées, les workflows **signalent** mais
n'**empêchent** rien.
