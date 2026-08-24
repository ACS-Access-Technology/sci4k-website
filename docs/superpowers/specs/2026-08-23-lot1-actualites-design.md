# Lot 1 — Socle Laravel et actualités

**Date** : 23 août 2026
**État** : validé en cadrage, à relire avant plan d'implémentation
**Périmètre** : premier des quatre lots du rapprochement backoffice / frontoffice

---

## 1. Objectif

Prouver la chaîne complète — formulaire d'administration, base de données, page
publique, deux langues — sur une seule entité, avant de répéter la mécanique sur
les neuf autres.

Le lot est terminé quand :

- un article créé dans l'administration apparaît sur le site public ;
- il apparaît en français et en anglais ;
- les 12 articles existants sont migrés et s'affichent comme aujourd'hui ;
- les anciennes adresses d'articles ne cassent pas ;
- les trois rôles se comportent comme prévu ;
- les tests passent et la CI est verte.

## 2. Contexte

### Ce qui existe

- **Frontoffice** : 12 pages HTML statiques, bilingues, responsives, avec thème
  clair et sombre, données structurées et contrôles de non-régression.
- **Backoffice** : 30 maquettes HTML générées par des scripts Python. Aucune
  donnée, aucun serveur, aucune authentification. Les 28 formulaires n'ont ni
  attribut `action` ni appel réseau : ils n'envoient rien.
- **Contenu des actualités** : 12 articles, éclatés entre `actualites.html`
  (slug, catégorie, date, couverture) et le dictionnaire de `main.js`
  (titre, résumé, quatre paragraphes, en français et en anglais).

### Contraintes

- **Laravel imposé** — seule consigne technique reçue.
- Le site public doit rester bilingue, responsive et conforme à l'existant.
- Le CSS actuel du backoffice provient du gabarit commercial NexLink, dont la
  licence n'a pas été acquise. Il ne sera pas repris.

## 3. Décisions d'architecture

| Sujet | Décision | Motif |
|---|---|---|
| Rendu du site public | Blade, rendu dynamique | voie standard Laravel, la plus simple à maintenir |
| Backoffice | Blade + Tailwind, appuyé sur des briques | fidélité aux maquettes sans réécrire toute la mécanique |
| Bilingue | saisie FR/EN partout | un site bilingue dont une langue est figée se dégrade seul |
| Stockage des langues | deux colonnes par champ (`titre_fr`, `titre_en`) | deux langues fixes ; recherche SQL directe ; aucune dépendance |
| Rôles | Administrateur, Éditeur, Lecteur | repris des maquettes `users-list.html` |
| Entité pilote | actualités | structure représentative, 12 entrées réelles, écrans déjà dessinés |

### Ce qui n'est pas réversible à bon compte

- **La structure bilingue** : passer de deux colonnes à du JSON toucherait toutes
  les tables et tout le code qui les lit.
- **Les slugs** : une fois indexés et partagés, les changer casse les liens.

Le reste — champs supplémentaires, catégories, découpage du contenu — s'ajuste
sans douleur.

## 4. Briques retenues

Toutes sous licence MIT.

- **Starter kit Livewire de Laravel 13** — Livewire 4, Tailwind, composants
  Flux UI, authentification complète (connexion, mot de passe oublié, profil).
- **`spatie/laravel-permission`** — les trois rôles.
- **`spatie/laravel-medialibrary`** — couvertures, conversions, miniatures.

**Point de vigilance** : Flux UI a une édition gratuite et une édition Pro
payante. Vérifier, avant de s'engager, qu'aucun composant nécessaire n'est
réservé au Pro — pour ne pas rejouer le problème du gabarit NexLink.

## 5. Modèle de données

### `articles`

| Colonne | Type | Remarque |
|---|---|---|
| `id` | bigint | |
| `slug` | string, unique | repris de l'existant, ex. `acd-securiser-terrain` |
| `categorie_id` | foreign | vers `categories` |
| `date_publication` | date | |
| `statut` | enum | `brouillon`, `publie` |
| `titre_fr`, `titre_en` | string | |
| `resume_fr`, `resume_en` | text | affiché sur la carte |
| `contenu_fr`, `contenu_en` | longtext | |
| `meta_titre_fr`, `meta_titre_en` | string, nullable | |
| `meta_description_fr`, `meta_description_en` | string, nullable | |
| horodatages | | |

La couverture est rattachée par la médiathèque, hors table.

### `categories`

`id`, `slug`, `nom_fr`, `nom_en`, `ordre`.

Sept valeurs initiales : Foncier, Construction, Gestion / Location, Achat,
Vente, Administration de biens, Marché. Six correspondent exactement aux six
services du site : cette table servira aussi aux services au lot 2, plutôt
que d'être dupliquée.

### Arbitrages explicites

- **Les quatre paragraphes deviennent un champ unique.** Chaque article est
  aujourd'hui découpé en `p1` à `p4`, exactement quatre. Un article de cinq
  paragraphes serait impossible. Un champ de contenu avec éditeur de texte lève
  la contrainte.
- **`auteur` et `étiquettes` sont écartés.** Les maquettes les prévoient, le
  site public ne les affiche pas. Deux champs à remplir pour un résultat
  invisible. Faciles à ajouter si le besoin apparaît.
- **Les champs meta sont conservés.** Absents du site actuel, mais ils portent
  le titre et la description qui sortent dans les résultats de recherche. La
  revue du frontoffice a montré que la description de l'accueil dépasse la
  limite d'affichage : pouvoir les régler par article a une valeur immédiate.

## 6. Migration du contenu

Un *seeder* lit les deux sources actuelles et écrit en base.

- **Il vérifie avant d'écrire.** Chaque article doit avoir ses douze valeurs
  attendues — titre, résumé, contenu, dans les deux langues. Un manque
  interrompt la migration en nommant l'article fautif, plutôt que d'insérer
  une ligne incomplète.
- **Il compte à la fin.** 12 articles en base, 24 titres non vides. Si le
  compte n'y est pas, la migration échoue.
- **Il est rejouable.** Le `slug` sert de clé : relancer ne duplique pas. On
  peut corriger le script et recommencer sans repartir d'une base vide.

Les couvertures existent déjà dans `frontoffice/images/actualites/` et sont
rattachées via la médiathèque, variantes WebP mobiles comprises.

**Le point sensible** : le contenu anglais n'existe que dans le dictionnaire de
`main.js`. Si la migration le rate, il faudra retraduire 12 articles de quatre
paragraphes. Le décompte des textes anglais récupérés est explicite.

## 7. Adressage

- Liste : `/actualites`
- Article : `/actualites/{slug}`

Aujourd'hui les douze articles partagent une seule adresse
(`actualite-detail.html?id=…`), donc une seule fiche pour les moteurs. Le
passage au slug dans le chemin corrige ce point relevé par la revue du
frontoffice.

Les anciennes adresses avec paramètre doivent continuer de fonctionner : un
lien partagé par WhatsApp ne doit pas casser.

## 8. Vérification

### Tests automatiques

- **Migration** : 12 articles en base ; 24 titres non vides ; aucun contenu
  anglais manquant ; rejouer le seeder ne duplique pas.
- **Modèle** : un article sans titre français est refusé ; deux articles ne
  peuvent partager un slug ; un brouillon n'apparaît pas sur le site public ;
  la bascule de langue renvoie le champ attendu.
- **Droits** : un visiteur non connecté est renvoyé vers la connexion ; un
  Lecteur consulte sans enregistrer ; un Éditeur modifie un article sans gérer
  les utilisateurs ; un Administrateur accède à tout.
- **Pages publiques** : `/actualites` répond et liste les articles publiés ;
  `/actualites/acd-securiser-terrain` répond ; une page inexistante renvoie
  un vrai 404.

### Vérifications manuelles

- Rendu des 12 pages portées en Blade, comparé à l'actuel.
- Comportement mobile : cartes, en-tête, grille à deux colonnes.
- Thèmes clair et sombre.
- Cycle complet : créer un article, le publier, le voir en ligne dans les deux
  langues.

### Intégration continue

- Les 7 contrôles existants restent en place.
- Les tests Laravel s'y ajoutent.
- Le contrôle « références mortes » scanne des fichiers HTML : il devra scanner
  des vues Blade.

## 9. Devenir de l'existant

- **`backoffice/` et son générateur Python** — conservés comme référence
  fonctionnelle, plus exécutés. Les 30 maquettes disent quels champs et quelles
  colonnes sont attendus ; c'est leur usage le plus utile.
- **`backoffice/assets/css/styles.css`** — retiré du dépôt. 463 Ko issus du
  gabarit NexLink, dont la licence n'a pas été acquise.
- **`frontoffice/`** — ses fichiers deviennent des vues Blade. Le CSS et le
  JavaScript sont conservés sans réécriture, y compris les corrections mobile,
  la gestion des thèmes et les traductions.

## 10. Hors périmètre de ce lot

- Les neuf autres entités — lots 2 à 4.
- Les boîtes de réception (messages, visites, newsletter). Le formulaire de
  contact achemine aujourd'hui vers WhatsApp ; le rebrancher relève du lot 4.
- Le choix de l'hébergement de production. Il n'est pas requis pour développer,
  mais devra être tranché avant mise en ligne.
- Les points bloquants relevés par la revue du frontoffice : mentions légales
  incomplètes, photographies provisoires, domaine déclaré avec `www`.
