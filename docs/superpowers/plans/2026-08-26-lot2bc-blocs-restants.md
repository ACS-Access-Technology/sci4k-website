# Lot 2b/2c — blocs restants et portage de l'accueil et de la présentation

Fait suite au cadrage `2026-08-25-lot2-blocs-de-contenu-design.md` et au lot 2a
(services, FAQ, rubriques). Les deux lots sont menés d'un seul tenant à la
demande du client.

## Ce que l'inventaire du site corrige au cadrage

Le cadrage a été relu contre les pages réelles avant d'écrire ce plan. Deux
écarts, tous deux dans le sens de la simplification :

**Les « réglages de section » ne sont pas deux mais vingt-trois.** Le
dictionnaire de `main.js` montre que chaque section du site porte le même
triplet — étiquette, titre, chapô : témoignages, équipe, valeurs, partenaires,
processus, et jusqu'aux bandeaux de page. Le cadrage n'en annonçait que deux,
et aurait donc produit deux cas particuliers là où une table unique indexée par
section couvre les vingt-trois pour le même coût.

**Les étapes du processus vivent sur `/services`**, page déjà portée au lot 2a,
où elles sont écrites en dur dans la vue. Elles n'exigent donc aucun portage,
seulement d'être lues depuis la base.

## Inventaire, relevé sur les pages

| Famille | Entité | Nombre | Emplacement |
|---|---|---:|---|
| Collection ordonnable | témoignages | 3 | accueil |
| | partenaires | 7 | accueil |
| | membres d'équipe | 4 | présentation |
| | encarts | 1 | accueil |
| | images de fond | 20 | `assets/images.css` |
| Petit ensemble figé | valeurs | 4 | présentation |
| | chiffres clés | 3 | accueil, bandeau |
| | étapes du processus | 4 | `/services` |
| Réglage de section | en-têtes de section | 23 | tout le site |

Pages à porter : `index.html`, `presentation.html`. Leur portage referme au
passage le constat **I9** de la relecture du lot 2a — ces deux pages annoncent
encore les six services en dur, si bien qu'un service créé n'y apparaît pas et
qu'un service supprimé y laisse une ancre morte.

## Étapes

### 1. Extraction

`tools/extraction-blocs.py`, sur le modèle des deux scripts existants. Lit les
pages, le dictionnaire de `main.js` et `images.css` ; produit un JSON par
famille dans `database/data/`.

Contraintes héritées, non négociables :

- jamais de `unicode_escape` — le piège avait corrompu les accents des douze
  articles du lot 1 sans qu'aucun test ne le voie ;
- vérifier avant d'écrire, compter après, échouer en nommant le champ manquant
  plutôt qu'en écrivant une chaîne vide ;
- l'anglais vient du dictionnaire, jamais d'une traduction automatique : les
  textes anglais du site sont humains et leur récupération a coûté une
  investigation entière au lot 1.

### 2. Socle

**`reglages_de_section`** — table indexée par un slug de section, colonnes
`etiquette_fr/en`, `titre_fr/en`, `chapo_fr/en`. Un écran de liste, un
formulaire. Ni création ni suppression : les sections sont celles du site.

**`EnsembleFige`** — composant Livewire abstrait pour les petits ensembles
édités d'un bloc. Tous les éléments côte à côte, un seul bouton
d'enregistrement, ni création ni suppression. Chaque ensemble ne déclare que
son modèle et ses champs.

### 3. Collections ordonnables

Cinq entités sur `ListeOrdonnable`, déjà éprouvé par trois collections :
témoignages, partenaires, membres d'équipe, encarts, images de fond.

Chacune : migration, modèle, fabrique, écran de liste, formulaire, tests.

Les images de fond portent un fichier téléversable : elles réutilisent la garde
`cheminEffaçable()` du lot 2a, qui refuse tout segment de remontée — un chemin
forgé pouvait sinon effacer un fichier d'un autre écran.

### 4. Petits ensembles

Valeurs, chiffres clés, étapes du processus, sur `EnsembleFige`.

### 5. Pages publiques

Portage d'`index.html` et de `presentation.html` vers Blade, servies depuis la
base, anciennes adresses redirigées, pages statiques retirées de la
synchronisation — même motif qu'au lot 2a, pour qu'il n'existe jamais deux
adresses rendant deux versions divergentes du même contenu.

Le plan du site rendu au lot 2a suit automatiquement : ses entrées viennent
déjà des routes.

### 6. Vérification

Suite complète sur SQLite **et** sur MySQL, contrôles de
`tools/verifier-site.py`, puis au navigateur : les deux pages portées comparées
à l'actuel, en clair et en sombre, à 375 pixels, dans les deux langues, et le
cycle complet — modifier un bloc dans l'administration, le voir changer sur la
page publique.

## Ce qui n'est pas dans ce lot

Les biens immobiliers, qui forment le lot 3 : `biens.html` ne contient aucun
bien, c'est un sous-système à créer et non un contenu à reprendre.
