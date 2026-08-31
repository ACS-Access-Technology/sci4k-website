# Photos à replacer — SCI4K

Les visuels d'origine étaient chargés depuis Wikimedia Commons. Ils ont été retirés
du code (plus aucun appel externe) et remplacés provisoirement par des photos déjà
présentes dans ce dossier.

**Le code pointe déjà sur les noms de fichiers ci-dessous.** Il suffit d'écraser
chaque fichier en gardant exactement le même nom : rien d'autre à modifier.

## Les 6 photos d'origine

### 1. Plateau Abidjan de nuit
- Page : https://commons.wikimedia.org/wiki/File:Plateau_Abidjan_de_nuit.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/Plateau%20Abidjan%20de%20nuit.jpg
- Destination : `images/services/foncier.png`

### 2. La commune du Plateau — Abidjan 08
- Page : https://commons.wikimedia.org/wiki/File:La_commune_du_Plateau_-_Abidjan_08.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/La%20commune%20du%20Plateau%20-%20Abidjan%2008.jpg
- Destinations : `images/services/construction.jpg`, `images/blog/article-1.jpg`, `images/presentation/apercu.jpg`

### 3. Mairie de Cocody, Abidjan
- Page : https://commons.wikimedia.org/wiki/File:Mairie_de_Cocody,_Abidjan.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/Mairie%20de%20Cocody,%20Abidjan.jpg
- Destinations : `images/services/gestion-location.jpg`, `images/blog/article-2.jpg`, `images/presentation/equipe.jpg`

### 4. Photo de la lagune à Abidjan
- Page : https://commons.wikimedia.org/wiki/File:Photo_de_la_lagune_à_Abidjan.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/Photo%20de%20la%20lagune%20%C3%A0%20Abidjan.jpg
- Destination : `images/services/achat.jpg`

### 5. Notre Dame de Cocody à Abidjan en 2024 07
- Page : https://commons.wikimedia.org/wiki/File:Notre_Dame_de_Cocody_à_Abidjan_en_2024_07.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/Notre%20Dame%20de%20Cocody%20%C3%A0%20Abidjan%20en%202024%2007.jpg
- Destination : `images/services/vente.jpg`

### 6. Copropriétés Abidjan Riviéra Golf
- Page : https://commons.wikimedia.org/wiki/File:Copropriétés_Abidjan_Riviéra_Golf.jpg
- Fichier : https://commons.wikimedia.org/wiki/Special:FilePath/Copropri%C3%A9t%C3%A9s%20Abidjan%20Rivi%C3%A9ra%20Golf.jpg
- Destination : `images/services/administration.jpg`

## Récapitulatif par emplacement

| Fichier à déposer | Où il apparaît | Photo d'origine |
|---|---|---|
| `services/foncier.png` | Accueil, carte Foncier | Plateau de nuit |
| `services/construction.jpg` | Accueil, carte Construction | Commune du Plateau |
| `services/gestion-location.jpg` | Accueil, carte Gestion / Location | Mairie de Cocody |
| `services/achat.jpg` | Accueil, carte Achat | Lagune |
| `services/vente.jpg` | Accueil, carte Vente | Notre-Dame de Cocody |
| `services/administration.jpg` | Accueil, carte Administration | Copropriétés Riviéra Golf |
| `blog/article-1.jpg` | Accueil + page Blog, article 1 | Commune du Plateau |
| `blog/article-2.jpg` | Accueil + page Blog, article 2 | Mairie de Cocody |
| `blog/article-3.jpg` | Accueil + page Blog, article 3 | Copropriétés Riviéra Golf |
| `presentation/apercu.jpg` | Présentation, bloc Aperçu | Commune du Plateau |
| `presentation/equipe.jpg` | Présentation, cadre Équipe | Mairie de Cocody |

## Deux points de vigilance

**Licences.** Ces fichiers viennent de Wikimedia Commons. Beaucoup sont sous licence
CC BY-SA, qui impose de créditer l'auteur de façon visible, y compris sur un site
commercial. Vérifiez la licence sur chaque page « File: » avant publication, et prévoyez
une mention de crédit si elle est exigée. Une photo libre de droits achetée, ou vos
propres prises de vue, vous éviteraient cette contrainte.

**Extension du fichier Foncier.** `services/foncier.png` porte l'extension `.png` car le
fichier actuel en est un. Si vous y déposez un JPEG, conservez malgré tout le nom
`foncier.png`, ou signalez-le pour que la référence soit corrigée dans le code.


---

# Fonds de section (feuille de style)

Ces visuels-là n'étaient pas dans le HTML mais dans `assets/style.css`, et avaient
échappé au premier passage. Ils sont eux aussi rapatriés en local. Même principe :
écrasez le fichier en gardant son nom.

| Fichier à déposer | Où il apparaît | Photo d'origine |
|---|---|---|
| `hero/biens.jpg` | Bannière de la page Biens | Commune du Plateau |
| `hero/presentation.jpg` | Bannière de la page Présentation | Notre-Dame de Cocody |
| `hero/blog.jpg` | Bannière de la page Blog | Notre-Dame de Cocody |
| `hero/services.jpg` | Bannière de la page Services | Hôtel Ivoire |
| `hero/contact.jpg` | Bannière de la page Contact | Mairie de Cocody |
| `hero/faq.jpg` | Bannière de la page FAQ | Commune du Plateau |
| `sections/lagune.jpg` | Bandeau CTA, témoignages, valeurs, processus | Lagune d'Abidjan |
| `sections/info-box.jpg` | Encadré d'information | Commune du Plateau |
| `sections/footer.jpg` | Fond du pied de page | Mairie de Cocody |

Trois fonds réutilisent des fichiers déjà listés plus haut :
`services/foncier.png` (carte Foncier), `services/gestion-location.jpg`
(carte Gestion) et `services/vente.jpg` (carte Vente).

**Une photo supplémentaire à récupérer**, qui n'apparaissait que dans le CSS :

- Hôtel Ivoire — https://commons.wikimedia.org/wiki/File:Hotelivoire2.jpg
- Destination : `images/hero/services.jpg`

## Page Biens : plus de photo de fond

Le fond de la page Biens était une photo de lagune en `background-attachment: fixed`.
Il est remplacé par une couleur unie de la palette, déclinée selon le thème :
`--paper` (#F4F8FA) en mode clair, `--dark-bg` (#101B26) en mode sombre.
Seule la bannière du haut conserve son image. Aucun fichier à fournir pour ce fond.
