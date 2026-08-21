# Écarts frontoffice → backoffice

> Analyse des 10 pages de `frontoffice/` confrontées au périmètre couvert par `backoffice/` et `backoffice2/` (périmètre identique).
> Date : 21 août 2026

---

## ✅ Statut — traité le 21 août 2026

Les 12 modules manquants ont été créés dans `backoffice/` et les 3 écarts de champs corrigés.
Le backoffice compte désormais **26 écrans** répartis en 5 groupes de navigation
(Pilotage, Contenu, Blocs du site, Demandes, Réglages).

| Point | Écran livré | Statut |
|---|---|---|
| 2.1 Témoignages | `testimonials-list.html` | ✅ |
| 2.2 Partenaires | `partners-list.html` | ✅ |
| 2.3 Chiffres clés | `stats-list.html` | ✅ |
| 2.4 Messages de contact | `messages-list.html` | ✅ |
| 2.5 Demandes de visite | `visits-list.html` | ✅ |
| 2.6 Abonnés newsletter | `newsletter-list.html` | ✅ |
| 2.7 Équipe | `team-list.html` | ✅ |
| 2.8 Valeurs | `values-list.html` | ✅ |
| 2.9 Processus | `process-list.html` | ✅ |
| 2.10 Menus | `menus.html` | ✅ |
| 2.11 Référentiels | `referentials.html` | ✅ |
| 2.12 Bandeau CTA | bloc éditable dans `pages-edit.html` | ✅ |
| 3.1 Statut juridique | champ ajouté à `bien-edit.html` | ✅ |
| 3.2 Six services | `service-list.html` réaligné | ✅ |
| 3.3 Vocabulaire des filtres | `bien-list.html`, `bien-edit.html`, `referentials.html` | ✅ |

Le tableau de bord renvoie désormais vers la boîte de réception, ce qui referme
l'incohérence signalée au point 2.4.

---

## 1. Synthèse

Le backoffice couvre les **entités classiques d'un CMS** (biens, articles, FAQ, services, pages, médias, utilisateurs, réglages). Il ne couvre pas trois familles de contenu pourtant bien présentes sur le site public :

| Famille | Constat |
|---|---|
| **Blocs éditoriaux de la page d'accueil et de la présentation** | Témoignages, partenaires, chiffres clés, équipe, valeurs, processus : aucun écran de gestion. Ces contenus sont aujourd'hui figés dans le HTML. |
| **Flux entrants** | Messages du formulaire de contact, demandes de visite, abonnés newsletter : aucune boîte de réception, alors que le tableau de bord affiche déjà des compteurs de messages. |
| **Structure du site** | Menu d'en-tête (7 entrées), menu de pied de page (3 colonnes), référentiels des filtres de recherche : non pilotables. |

**12 modules manquants** et **3 écarts de champs** sont détaillés ci-dessous.

---

## 2. Modules manquants

### Priorité haute — contenus visibles sur l'accueil

**2.1 Témoignages clients**
`index.html` → section `testimonials-section`, 3 témoignages affichés.
Écran attendu : liste + formulaire (auteur, fonction ou quartier, texte, note, photo, ordre, visible oui/non).

**2.2 Partenaires**
`index.html` → section `partners-section`, 10 logos affichés (le dossier `images/partners/` en contient 7 : BMS-CI, CIE, CNPS, Credit Access, FPPN, NSIA Banque, Ordre des Architectes).
Écran attendu : liste de logos (image, nom, lien, ordre, visible).

**2.3 Chiffres clés**
`index.html` → 3 compteurs animés : « Biens commercialisés », « Années d'expérience », « % clients satisfaits ».
Écran attendu : bloc de 3 à 4 valeurs éditables (libellé, valeur, suffixe, ordre).

### Priorité haute — flux entrants

**2.4 Messages de contact**
`contact.html` → formulaire à 4 champs (nom, téléphone, email, message).
Aucun écran de réception. Le tableau de bord affiche pourtant « 18 messages non lus » et une liste d'expéditeurs — ces données n'ont aucune page de destination.
Écran attendu : boîte de réception (liste, lecture, statut nouveau/traité/archivé, réponse, export).

**2.5 Demandes de visite**
`biens.html` → chaque bien mène à une demande de visite (le tableau de bord affiche « Demande de visite — Villa Cocody »).
Écran attendu : liste des demandes rattachées à un bien (demandeur, bien concerné, date souhaitée, statut).

**2.6 Abonnés newsletter**
Bloc d'inscription présent dans le pied de page de toutes les pages.
Écran attendu : liste des abonnés (email, date, source, statut), export CSV, désinscription.

### Priorité moyenne — page Présentation

**2.7 Équipe**
`presentation.html` → section `team-section`, 4 membres : M. Jean-Philippe Yao, Mme Sarah Koné, M. Marc Kouassi, Mme Aminata Diop.
Écran attendu : liste (photo, nom, fonction, biographie, réseaux, ordre).

**2.8 Valeurs et engagements**
`presentation.html` → section `values-section`, 4 valeurs : Rigueur & Sécurité, Transparence Totale, Ancrage Abidjanais, Service Client VIP.
Écran attendu : liste (icône, titre, description, ordre).

### Priorité moyenne — page Services

**2.9 Étapes du processus**
`services.html` → section `process-section`, 4 étapes : Écoute & Analyse, Sélection & Audit, Négociation & Acte, Suivi Continu.
Écran attendu : liste ordonnée (numéro, titre, description).

### Priorité moyenne — structure

**2.10 Menus de navigation**
En-tête : Accueil, Présentation, Biens Immobiliers, Nos Services, Blog, FAQ, Contact.
Pied de page : trois colonnes — Navigation, Nos Services, Nous contacter — plus les liens légaux.
Écran attendu : gestionnaire de menus (libellé, cible, ordre, position, visible).

**2.11 Référentiels des filtres de biens**
`biens.html` → 4 listes déroulantes dont les valeurs sont figées dans le HTML :

| Filtre | Valeurs actuelles |
|---|---|
| Type | Villa & Duplex, Appartement & Studio, Immeuble de rapport, Terrain viabilisé |
| Zone | Cocody & Riviera, Bingerville, Marcory, Le Plateau, Abatta |
| Pièces | 1 à 2, 3 à 4, 5+ |
| Surface | < 100 m², 100–250 m², 250–500 m², > 500 m² |

Écran attendu : gestion des référentiels, pour que l'ajout d'une zone ou d'un type ne demande pas de toucher au code.

**2.12 Bandeau d'appel à l'action**
`index.html` → section `city-cta-section` (« Prêt à concrétiser votre projet immobilier ? »).
Listé comme section dans `pages-edit.html`, mais sans écran d'édition propre. À confirmer selon la granularité retenue.

---

## 3. Écarts de champs sur les écrans existants

**3.1 Fiche bien — statut juridique manquant**
Les cartes de `biens.html` affichent quatre caractéristiques : type de bien, surface totale, pièces/chambres et **statut juridique** (exemple affiché : « ACD Disponible »).
`bien-edit.html` ne propose aucun champ pour ce statut, alors qu'il s'agit d'une information structurante sur le marché ivoirien (ACD, titre foncier, lettre d'attribution).
→ Ajouter un champ « Statut juridique » et, si utile, « Numéro de titre ».

**3.2 Services — liste désalignée**
Le site expose 6 services : Foncier, Construction, Gestion / Location, Achat, Vente, Administration de biens.
`service-list.html` en propose 4, aux libellés différents (Vente immobilière, Location et gestion locative, Construction et aménagement, Conseil et investissement).
→ Aligner sur les 6 services réellement publiés.

**3.3 Filtres de recherche — vocabulaire divergent**
Le backoffice filtre par type / offre / commune ; le site filtre par type / zone / pièces / surface, avec des libellés différents (« Villa & Duplex » côté site, « Maison » côté admin).
→ Harmoniser le vocabulaire, idéalement via les référentiels du point 2.11.

---

## 4. Ce qui est déjà bien couvert

| Page publique | Écran d'administration |
|---|---|
| `index.html` (bannière, CTA) | `pages-edit.html` |
| `biens.html` | `bien-list.html` + `bien-edit.html` |
| `blog.html` (3 articles) | `article-list.html` + `article-edit.html` |
| `faq.html` (12 questions) | `faq-list.html` |
| `services.html` (fiches services) | `service-list.html` *(à aligner, cf. 3.2)* |
| `contact.html` (siège, téléphone, email, horaires, carte) | `settings.html` → onglet Contact |
| `mentions-legales.html`, `politique-confidentialite.html` | `pages-list.html` + `pages-edit.html` |
| `404.html` | `pages-list.html` (entrée « Système ») |
| Métadonnées, robots.txt, Analytics | `settings.html` → onglet Référencement |
| Images du site | `media-gallery.html` |

---

## 5. Ordre de traitement suggéré

1. **Messages de contact** et **demandes de visite** — le tableau de bord promet déjà ces écrans, leur absence est la plus visible à l'usage.
2. **Témoignages**, **partenaires**, **chiffres clés** — trois blocs de la page d'accueil, aujourd'hui modifiables uniquement dans le code.
3. **Équipe**, **valeurs**, **processus** — même logique pour Présentation et Services.
4. **Statut juridique** sur la fiche bien et **alignement des 6 services** — corrections rapides sur des écrans existants.
5. **Menus** et **référentiels de filtres** — structurants, mais moins urgents tant que le site reste à sept pages.
6. **Newsletter** — dépend de l'outil d'emailing retenu.
