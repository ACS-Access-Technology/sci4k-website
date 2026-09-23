# SCI4K — repères pour travailler sur ce dépôt

Site d'une société civile immobilière d'Abidjan. Laravel 13 + Livewire 4,
servi par FrankenPHP, déployé en conteneur.

Ce fichier ne décrit pas l'architecture — le code et ses commentaires s'en
chargent. Il rassemble ce qui **ne se devine pas** et qui a déjà coûté du temps.

## La structure en deux dossiers

`app-laravel/` porte l'application. `maquettes-frontoffice/` porte le CSS, le
script et les images du site public, **et c'est la source de vérité**.
`app-laravel/public/assets/` n'en est qu'une copie, ignorée par git, produite
par `tools/sync-frontoffice.sh` au moment de construire l'image.

Modifier `app-laravel/public/assets/style.css` directement, c'est perdre son
travail au prochain déploiement.

## Le rituel avant de commiter

Formateur, analyse statique, clés de traduction, tests. Le détail et les
pièges de chaque étape sont dans la compétence `verifier` (`/verifier`).

PHPStan tourne au niveau 5 et **bloque la CI** : un échec empêche les tests de
s'exécuter.

## Tout texte public doit être modifiable

Le principe qui structure le backoffice : un visiteur ne doit jamais lire une
chaîne que l'agence ne puisse corriger seule. Les textes vivent dans
`ReglageDeSection`, les listes déroulantes dans `Referentiel`, les blocs dans
leurs modèles.

Écrire un libellé en dur dans une vue, c'est créer une demande d'intervention
pour un mot à changer. Si un nouveau texte doit vraiment rester figé, le passer
par `__()` et l'ajouter aux deux dictionnaires.

## Bilingue

Chaque contenu porte ses colonnes `_fr` et `_en`. La langue vit **dans
l'adresse** (`/en/...`), pas dans la session : c'est ce qui rend les pages
anglaises indexables.

`lang/fr.json` ne contient que les identités nécessaires ; `lang/en.json` porte
les traductions. Un test échoue si une clé manque aux deux.

## Déploiement

**Aucun déploiement automatique depuis GitHub.** La mise en ligne est un
`railway up --service sci4k` lancé à la main. Pousser sur `master` ne change
rien au site en ligne.

Branches : `dev` pour travailler, puis `preprod`, puis `master`.

Trois défauts de portabilité ont été corrigés et ne doivent pas revenir —
`railway up` téléverse l'arbre de travail Windows, pas un clone git :

- `.gitattributes` force les fins de ligne LF sur `*.sh`, `Dockerfile`,
  `Caddyfile`, `*.ini`, `*ignore`. Sans lui, le shebang devient `bash\r`.
- Le Dockerfile refait `chmod +x` sur les scripts : NTFS n'a pas de bit
  d'exécution.
- `.railwayignore` allège le téléversement, qu'une coupure réseau fait
  repartir de zéro.

## Ce qui n'est pas prêt pour le domaine définitif

L'instance en ligne est un **environnement d'essai**, sur adresse provisoire et
volontairement non indexé. Avant le vrai lancement, voir `docs/MISE_EN_LIGNE.md` :

- du contenu de démonstration à retirer — les biens repris de la maquette, et
  les fiches préfixées `DEMO-` ;
- la case « Autoriser l'indexation », à ne cocher qu'une fois le domaine en
  place ;
- des mentions légales incomplètes, faute de RCCM, de directeur de publication
  et d'hébergeur déclaré.

## Une distinction à ne pas confondre

Un bien **publié** n'est pas forcément **au catalogue**. Une « réalisation de
référence » — un immeuble bâti ou administré par l'agence — reste visible sur
le site et garde sa fiche, mais quitte la grille des biens à vendre et ne
propose ni prix ni visite.

D'où deux portées distinctes sur `Bien` : `publies()` et `duCatalogue()`.
Choisir la mauvaise fait soit disparaître une fiche, soit annoncer comme une
offre un immeuble qui n'en est pas une.
