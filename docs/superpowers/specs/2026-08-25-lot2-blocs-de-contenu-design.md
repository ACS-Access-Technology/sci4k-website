# Lot 2 — Blocs de contenu du site

**Date** : 25 août 2026
**État** : validé en cadrage
**Périmètre** : deuxième des quatre lots du rapprochement backoffice / frontoffice
**Base** : `master` après fusion du lot 1 (PR #14, commit `b045061`)

---

## 1. Objectif

Rendre modifiable, depuis le backoffice, tout le contenu éditorial du site à
l'exception des biens immobiliers — et le rendre visible immédiatement sur les
pages publiques.

Le lot est terminé quand :

- les onze blocs de contenu se modifient depuis l'administration ;
- toute modification apparaît sur le site, dans les deux langues ;
- les quatre pages publiques concernées sont servies depuis la base et rendent
  comme aujourd'hui ;
- l'ordre d'affichage se règle par glisser-déposer ;
- les tests passent et la CI est verte.

## 2. Ce que le lot 1 a laissé en place

- Modèle bilingue par colonnes suffixées, avec le trait `TraduitParColonnes`
  et son repli sur le français.
- Coquille d'administration : sept composants Blade partagés — en-tête avec
  fil d'Ariane, carte d'indicateur, pastille de statut, barre et champ de
  filtre, tableau, icônes.
- Bascule FR/EN de l'interface, deux dictionnaires de sens inverse, et trois
  garde-fous automatiques sur les traductions.
- Gabarit public Blade, avec en-tête et pied extraits en partials, et données
  structurées construites en PHP.
- CI exécutant la suite sur SQLite puis sur MySQL.

Le lot 2 hérite de tout cela. Aucune de ces briques n'est réécrite.

## 3. Périmètre

### Les onze blocs

| Bloc | Où il paraît | Volume actuel |
|---|---|---|
| Services | accueil + `services.html` | 6 |
| Étapes du processus | `services.html` | 4, plus l'en-tête de leur section |
| FAQ | `faq.html` | 12 questions, 6 groupes |
| Équipe | `presentation.html` | 4 |
| Valeurs | `presentation.html` | 4 |
| Témoignages | accueil | 3 |
| Partenaires | accueil | 7 |
| Chiffres clés | bandeau de l'accueil | 3 |
| Encart annonce | accueil | 1 |
| Banderole communes | accueil | 1 |
| Images de fond | bandeaux de 5 pages | 18 fichiers référencés |

Le processus compte pour un bloc mais se scinde à l'usage : ses quatre étapes
forment un petit ensemble édité d'un bloc, tandis que le titre, le chapô et la
mise en page de la section relèvent des réglages. Les deux sont sur le même
écran ; la distinction ne concerne que leur stockage.

### Les quatre pages portées en Blade

`index`, `services`, `faq`, `presentation`. Elles gardent leur adresse et leur
rendu actuels.

### Hors périmètre

- Les biens immobiliers — lot 3, entièrement à concevoir : `biens.html` ne
  contient aujourd'hui aucun bien, seulement un bandeau et des filtres.
- Les boîtes de réception : messages, demandes de visite, abonnés — lot 4.
- Les pages `contact`, `mentions-legales`, `politique-confidentialite`,
  `404`, `500`, qui restent statiques.

## 4. Décisions d'architecture

### Trois familles, pas onze cas particuliers

C'est la décision structurante du lot.

**Collections ordonnables** — services, FAQ, témoignages, partenaires, équipe,
encarts, images de fond.

Plusieurs éléments, un ordre, une visibilité. Écran de liste et formulaire,
comme les articles. Ces sept entités partagent le même code : une classe de
base porte l'ordre, la visibilité, la recherche, la pagination et la
suppression ; chaque entité ne déclare que ses champs propres et leurs règles
de validation.

**Petits ensembles édités d'un bloc** — valeurs (4), chiffres clés (3), étapes
du processus (4).

Un seul écran, tous les éléments côte à côte, un bouton d'enregistrement. Ni
création ni suppression : on modifie ce qui existe. La maquette
`values-list.html` décrit exactement cela.

**Réglages de section** — en-tête du processus, banderole communes.

Un titre, un chapô, une mise en page. Un enregistrement unique, jamais de
liste.

### Ce qui motive ce découpage

Un tableau paginé, avec recherche et filtres, pour quatre valeurs, est une
machinerie qui coûte à écrire, à tester et surtout à utiliser : trois clics
pour changer un mot.

À l'inverse, écrire les sept collections une par une reviendrait à répéter
sept fois la même logique d'ordre, de visibilité et de bilingue — donc sept
endroits à corriger au premier défaut. Le lot 1 a déjà tranché ce type de
question en écrivant `TraduitParColonnes` une fois pour dix entités, après
avoir constaté que le motif allait être copié.

### Réordonnancement par glisser-déposer

Les maquettes montrent une colonne « Ordre ». Saisir des rangs à la main
produit des doublons et impose de renuméroter à chaque insertion. Le
glisser-déposer écrit les rangs en une requête, et la colonne reste en base.

### Source unique pour les services

L'accueil et la page services affichent les mêmes six services. Ils lisent la
même table. Sans cela, deux listes à tenir à jour et une divergence garantie.

### Ce qui n'est pas réversible à bon compte

- **La répartition d'une entité entre les trois familles.** Passer une
  collection en ensemble figé, ou l'inverse, change son écran, son modèle et
  ses tests.
- **Les adresses des pages publiques**, déjà indexées.

## 5. Modèle de données

Chaque collection ordonnable porte, en plus de ses champs propres :

| Colonne | Type | Remarque |
|---|---|---|
| `id` | bigint | |
| `ordre` | unsignedInteger | rang d'affichage, écrit par le glisser-déposer |
| `visible` | boolean | retire du site sans effacer |
| horodatages | | |

Les champs éditoriaux suivent la convention du lot 1 : une colonne par langue,
`titre_fr` / `titre_en`.

### Champs propres, relevés sur les maquettes et sur le site

- **Service** : slug, icône, titre, résumé, contenu, étiquettes, visuel
  d'accueil, rattachement à une catégorie existante.
- **Question FAQ** : groupe, question, réponse.
- **Témoignage** : auteur, fonction ou quartier, texte, note sur 5, photo.
- **Membre d'équipe** : nom, fonction, biographie, photo, LinkedIn, e-mail.
- **Partenaire** : nom, logo, site officiel.
- **Encart** : étiquette, titre, texte, libellé et cible du bouton, visuel.
- **Image de fond** : page visée, fichier, texte de remplacement.
- **Valeur**, **chiffre clé**, **étape** : titre et description ; le chiffre
  clé porte en plus sa valeur affichée.
- **Réglages de section** : titre, chapô, mise en page.

Les catégories créées au lot 1 servent aux services : la table avait été
prévue pour cela.

## 6. Migration du contenu

Un script d'extraction, sur le modèle de `tools/extraction-articles.py`, lit
les pages du frontoffice et le dictionnaire de `main.js`, et produit un JSON
par famille. Un seeder l'écrit en base.

Les enseignements du lot 1 s'appliquent sans discussion :

- **Ne jamais décoder par `unicode_escape`.** Le piège avait corrompu tous les
  accents des douze articles sans qu'aucun test ne le voie.
- **Vérifier avant d'écrire**, compter après, et rester rejouable par une clé
  stable.
- **Contrôler la corruption en PHP, pas en SQL** : la collation du projet est
  insensible aux accents, un `LIKE '%Ã%'` signale des faux positifs.

Le contenu anglais n'existe, là encore, que dans le dictionnaire JavaScript.
Sa récupération est le point sensible du lot.

## 7. Vérification

### Tests automatiques

- **Migration** : chaque bloc a le compte attendu, aucun texte anglais
  manquant, seeder rejouable sans doublon, aucun champ corrompu.
- **Collections** : l'ordre se réécrit correctement, un élément invisible ne
  paraît pas sur le site, la recherche porte sur les deux langues.
- **Ensembles et réglages** : l'enregistrement d'un bloc ne touche pas les
  autres.
- **Droits** : un lecteur consulte sans écrire, sur chacun des onze écrans.
- **Pages publiques** : les quatre répondent, affichent le contenu de la base,
  et basculent en anglais.
- **Chaîne complète** : un contenu modifié dans l'administration apparaît sur
  la page publique correspondante, dans les deux langues.

### Garde-fous hérités

Les contrôles de traduction du lot 1 s'appliquent d'office aux nouveaux
écrans : toute clé absente des dictionnaires, tout rendu anglais sous locale
française, toute collision avec les quatre noms réservés du framework font
échouer la suite.

### Vérifications manuelles

- Les quatre pages portées, comparées à l'actuel, en clair et en sombre.
- Comportement à 375 pixels.
- Le glisser-déposer sur un écran de collection.
- Cycle complet : modifier un service, le voir changer sur l'accueil et sur la
  page services, dans les deux langues.

## 8. Risques identifiés

- **Volume.** Onze entités et quatre pages : le lot est plus lourd que le lot
  1. Le code générique est ce qui le rend tenable ; s'il dérive vers onze
  implémentations séparées, le lot double.
- **Récupération de l'anglais.** Même point sensible qu'au lot 1, sur un
  volume plus grand — 228 clés pour les seuls services.
- **Rendu des pages portées.** Quatre pages publiques à reproduire à
  l'identique. Les contrôles de non-régression couvrent les références, les
  données structurées et le thème sombre, pas la mise en page.

## 9. Ce que ce lot ne fait pas

- Les biens immobiliers — lot 3.
- Les boîtes de réception — lot 4.
- Les pages légales et de contact, qui restent statiques.
- Les points laissés ouverts par le lot 1 : politique de confidentialité à
  reprendre, indexation de la version anglaise, hébergement de production.
