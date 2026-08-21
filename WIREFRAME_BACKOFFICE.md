# 🎨 Wireframe Low-Fidelity - Backoffice CMS

> **Projet:** EDANIlyasK - SCI4K Admin Panel  
> **Date:** 2026-08-20  
> **Fidelité:** Low-Fidelity (ASCII Wireframes)

---

## 📑 Table des Matières

1. [Layout Principal](#layout-principal)
2. [Dashboard](#dashboard)
3. [Gestion des Biens](#gestion-des-biens)
4. [Gestion des Articles](#gestion-des-articles)
5. [Gestion des FAQ](#gestion-des-faq)
6. [Gestion des Pages](#gestion-des-pages)
7. [Configuration](#configuration)
8. [Gestion des Utilisateurs](#gestion-des-utilisateurs)

---

## Layout Principal

### Structure Générale

```
┌─────────────────────────────────────────────────────────────────────┐
│                         🎯 SCI4K Admin Panel                         │
├──────────┬──────────────────────────────────────────────────────────┤
│          │                                                            │
│  SIDEBAR │                    MAIN CONTENT AREA                      │
│          │                                                            │
│ ┌──────┐ │                                                            │
│ │LOGO  │ │  ┌─────────────────────────────────────────────────┐    │
│ └──────┘ │  │ TOP BAR: User | Notifications | Logout           │    │
│          │  └─────────────────────────────────────────────────┘    │
│ ┌──────┐ │                                                            │
│ │ 📊   │ │  ┌─────────────────────────────────────────────────┐    │
│ │Dash. │ │  │ BREADCRUMB: Home > Biens > Voir tous            │    │
│ └──────┘ │  └─────────────────────────────────────────────────┘    │
│          │                                                            │
│ ┌──────┐ │  ┌─────────────────────────────────────────────────┐    │
│ │ 🏘️   │ │  │                                                   │    │
│ │Biens │ │  │       PAGE CONTENT / FORMS / TABLES             │    │
│ └──────┘ │  │                                                   │    │
│          │  │                                                   │    │
│ ┌──────┐ │  │                                                   │    │
│ │ 📝   │ │  │                                                   │    │
│ │Blog  │ │  │                                                   │    │
│ └──────┘ │  │                                                   │    │
│          │  │                                                   │    │
│ ┌──────┐ │  │                                                   │    │
│ │ ❓   │ │  │                                                   │    │
│ │ FAQ  │ │  │                                                   │    │
│ └──────┘ │  │                                                   │    │
│          │  └─────────────────────────────────────────────────┘    │
│ ┌──────┐ │                                                            │
│ │ ⚙️   │ │  ┌─────────────────────────────────────────────────┐    │
│ │Config│ │  │ FOOTER: © 2026 | Status | Last Update          │    │
│ └──────┘ │  └─────────────────────────────────────────────────┘    │
│          │                                                            │
└──────────┴──────────────────────────────────────────────────────────┘

SIDEBAR DETAILLÉ:
┌────────────────┐
│   LOGO SCI4K   │
├────────────────┤
│ 📊 Dashboard   │
├────────────────┤
│ PAGES          │
│ ├─ 🏠 Accueil  │
│ ├─ 👥 Présent. │
│ ├─ 📧 Contact  │
│ └─ ⚖️ Légales  │
├────────────────┤
│ CONTENU        │
│ ├─ 🏘️ Biens   │
│ ├─ 📝 Blog    │
│ ├─ ❓ FAQ     │
│ └─ 🛠️ Services│
├────────────────┤
│ RÉGLAGES       │
│ ├─ ⚙️ Config  │
│ ├─ 👤 Users   │
│ ├─ 📁 Médias  │
│ └─ 📊 Analytics
├────────────────┤
│ 👤 User Name   │
│ 🔴 Online      │
│ 🚪 Logout      │
└────────────────┘
```

---

## Dashboard

```
┌──────────────────────────────────────────────────────────────────────┐
│ Dashboard                                              Voir Rapports > │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│  ┌─────────────────┬──────────────────┬──────────────────┐           │
│  │ 📊 STATS        │ 📊 STATS         │ 📊 STATS         │           │
│  │                 │                  │                  │           │
│  │ Total Biens     │ Articles Publiés │ Visiteurs Today  │           │
│  │     245         │       52         │      1,234       │           │
│  │    ↑ 12%        │     ↑ 8%         │     ↑ 23%        │           │
│  └─────────────────┴──────────────────┴──────────────────┘           │
│                                                                        │
│  ┌──────────────────────────────┬──────────────────────────────┐     │
│  │ ACTIVITÉ RÉCENTE             │ MESSAGES NON LUS (5)         │     │
│  │                              │                              │     │
│  │ • [2h] Bien #123 modifié     │ ┌──────────────────────────┐ │     │
│  │ • [4h] Article publié        │ │ [Nouveau] Demande visite │ │     │
│  │ • [6h] FAQ #45 mise à jour   │ │ Léon K. - 15 min         │ │     │
│  │ • [1d] Contact form x3       │ └──────────────────────────┘ │     │
│  │ • [1d] Utilisateur ajouté    │ ┌──────────────────────────┐ │     │
│  │                              │ │ Formulaire contact       │ │     │
│  │ [Voir tous >]                │ │ Emma D. - 1h             │ │     │
│  └──────────────────────────────┴──────────────────────────────┘     │
│                                                                        │
│  ┌─────────────────────────────────────────────────────────────────┐ │
│  │ TÂCHES PRIORITAIRES                                   Ajouter +  │ │
│  │                                                                  │ │
│  │ ☐ Mettre à jour 5 biens avec nouvelles photos (3 jours)        │ │
│  │ ☐ Publier article blog "Marché immobilier Q3" (5 jours)        │ │
│  │ ☑ Répondre aux 3 demandes de visite (En cours)                 │ │
│  │ ☐ Ajouter 2 nouveaux services (1 semaine)                      │ │
│  │                                                                  │ │
│  └─────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des Biens

### Liste des Biens

```
┌──────────────────────────────────────────────────────────────────────┐
│ Biens Immobiliers                                    [+ Ajouter bien] │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ Filtres:                                                              │
│ ┌──────────────────┬──────────────┬──────────────┬──────────────────┐ │
│ │ Type ▼           │ Offre ▼      │ Prix ▼       │ Localisation ▼   │ │
│ │ - Tous           │ - Tous       │ Min: [  ] €  │ - Tous           │ │
│ │ - Maison         │ - Vente      │ Max: [  ] €  │ - Cocody         │ │
│ │ - Appartement    │ - Location   │              │ - Yopougon       │ │
│ │ - Terrain        │ - Construction                │ - Autres         │ │
│ │ - Bureau         │              │              │                  │ │
│ └──────────────────┴──────────────┴──────────────┴──────────────────┘ │
│ [Filtrer] [Réinitialiser]                                             │
│                                                                        │
│ Résultats: 45 biens                        Trier par: [Récent ▼]     │
│                                                                        │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ ☐ │ Image │ Titre/Adresse          │ Prix    │ Type │ Offre │  │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Villa Cocody           │ 450M    │ M    │ Vente │  │  │
│ │   │       │ Zone Château - 250m²   │         │      │       │  │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Appartement Plateau    │ 120M    │ A    │ Vente │  │  │
│ │   │       │ 3 pièces - 180m²       │         │      │       │  │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Terrain Abidjan 2      │ 25M/m²  │ T    │ Vente │  │  │
│ │   │       │ 1000m² - Cocody        │         │      │       │  │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Bureau Plateau         │ 5M/mois │ B    │ Loc   │  │  │
│ │   │       │ 120m² climatisé        │         │      │       │  │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Maison Riviera         │ 350M    │ M    │ Vente │  │  │
│ │   │       │ 4 pièces - 300m²       │         │      │       │  │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│  < Précédent  Page 1 de 3  Suivant >                                  │
│  Afficher: [10 ▼] | Sélectionner: [Tous] [Aucun]                    │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

### Éditer un Bien

```
┌──────────────────────────────────────────────────────────────────────┐
│ Modifier Bien #123: Villa Cocody                    [Aperçu] [Publier]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ INFORMATIONS GÉNÉRALES                                                │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ Titre*                 [Villa Cocody - Château]             │      │
│ │ Slug                   [villa-cocody-chateau]               │      │
│ │ Description courte*    [Zone Château, 250m², 3 pièces...   ]│      │
│ │                        [Max 160 caractères]                 │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ CARACTÉRISTIQUES                                                      │
│ ┌───────────────────────────────┬───────────────────────────────┐    │
│ │ Type*         [Maison ▼]      │ Offre* [Vente ▼]            │    │
│ │ Prix*         [450,000,000]   │ Devise [XOF ▼]              │    │
│ │ Surface (m²)  [250]           │ Pièces [3]                  │    │
│ │ Chambre       [3]             │ Salle de bain [2]           │    │
│ │ Localisation* [Cocody ▼]      │ Année [2010]                │    │
│ │ État*         [Bon état ▼]    │ Étage [RdC]                 │    │
│ │ Parking       [Oui ▼] Nbre[2] │ Ascenseur [Non ▼]           │    │
│ │ Meublé        [Semi-meublé ▼] │                             │    │
│ └───────────────────────────────┴───────────────────────────────┘    │
│                                                                        │
│ DESCRIPTION COMPLÈTE                                                  │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ [Editor HTML WYSIWYG]                                       │      │
│ │                                                             │      │
│ │ Belle villa de 250m² avec jardin paysagé...                │      │
│ │ 3 chambres, 2 salles de bain, cuisine équipée              │      │
│ │                                                             │      │
│ │ Équipements:                                                │      │
│ │ • Piscine                                                  │      │
│ │ • Jardin                                                   │      │
│ │ • Garage double                                            │      │
│ │                                                             │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ GALERIE PHOTOS                                    [+ Ajouter photos] │
│ ┌───────────────────────────────────────────────────────────────────┐│
│ │ [X] [Img 1] [Img 2] [Img 3] [Img 4]                              ││
│ │ Réorganiser: [↑] [↓] Définir comme miniature [+]                 ││
│ └───────────────────────────────────────────────────────────────────┘│
│                                                                        │
│ LOCALISATION                                                          │
│ ┌──────────────────┬──────────────────────────────────────────┐      │
│ │ Adresse*         │ [Zone Château, Cocody]                  │      │
│ │ Code Postal      │ [00225]                                 │      │
│ │ Latitude/Long.   │ [6.8276, -5.5471]  [📍 Voir sur carte] │      │
│ │                  │ [Google Maps Embedded]                  │      │
│ │                  │ [Map Pin]                               │      │
│ └──────────────────┴──────────────────────────────────────────┘      │
│                                                                        │
│ SEO & MÉTADONNÉES                                                     │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ Meta titre    [Villa à vendre Cocody - 250m² - SCI4K]      │      │
│ │ Meta descrip. [Belle villa de 250m² avec jardin à Cocody.. │      │
│ │ Keywords      [villa, cocody, vente, immobilier, abidjan]  │      │
│ │ Statut        [Publié ▼]  [Visible ▼]                      │      │
│ │ Langue        [FR ▼]                                        │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ ACTIONS                                                                │
│ [💾 Enregistrer] [👁️ Aperçu] [🔗 Voir sur site] [🗑️ Supprimer]    │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des Articles

### Liste des Articles

```
┌──────────────────────────────────────────────────────────────────────┐
│ Blog - Articles                                    [+ Nouvel article] │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ Filtres:                                                              │
│ ┌──────────────────┬──────────────────┬──────────────────────────────┐│
│ │ Catégorie ▼      │ Statut ▼         │ Auteur ▼                    ││
│ │ - Tous           │ - Tous           │ - Tous                      ││
│ │ - Vente          │ - Publié         │ - Admin                     ││
│ │ - Location       │ - Brouillon      │ - Éditeur 1                 ││
│ │ - Conseil        │ - Archivé        │ - Éditeur 2                 ││
│ │ - Marché         │                  │                             ││
│ └──────────────────┴──────────────────┴──────────────────────────────┘│
│ [Filtrer]  [Réinitialiser]                  Trier: [Récent ▼]        │
│                                                                        │
│ Résultats: 12 articles                                               │
│                                                                        │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ ☐ │ Image │ Titre                    │ Auteur  │ Date    │ Stat.│ │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ 5 conseils pour vendre   │ Admin   │ 2j ago  │ ✓   │ │
│ │   │       │ Catégorie: Vente         │ (15 min │         │     │ │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Marché immobilier Q3     │ Éditor1 │ 5j ago  │ ✓   │ │
│ │   │       │ Catégorie: Marché        │ (8 min) │         │     │ │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Guide de l'acheteur      │ Admin   │ 1w ago  │ ✓   │ │
│ │   │       │ Catégorie: Conseil       │ (12 min)│         │     │ │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ ☐ │ [IMG] │ Financement immobilier   │ Éditor2 │ Draft   │ ⚪  │ │
│ │   │       │ Catégorie: Conseil       │ (20 min)│         │     │ │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│ < Précédent  Page 1 de 2  Suivant >                                   │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

### Créer/Éditer un Article

```
┌──────────────────────────────────────────────────────────────────────┐
│ Nouvel Article                                   [Brouillon] [Publier]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ INFORMATIONS DE L'ARTICLE                                             │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ Titre*           [Titre de l'article]                       │      │
│ │ Slug             [titre-de-l-article]                       │      │
│ │ Auteur*          [Admin ▼]                                  │      │
│ │ Catégorie*       [Conseil ▼]                                │      │
│ │ Tags             [immobilier] [conseil] [achat]  [+ Ajouter]│      │
│ │ Date publication [20/08/2026] [14:30]    [📅] [🕐]          │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ IMAGE DE COUVERTURE                         [+ Ajouter image]        │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ [Img Couverture 1200x600px]                                 │      │
│ │ Alt Text: [Description de l'image]                          │      │
│ │ Crédits: [Nom photographe/source]                           │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ CONTENU                                                               │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ [WYSIWYG Editor]                                            │      │
│ │                                                             │      │
│ │ [B][I][U] [H2][H3] [• ][1.] [Lien] [Image] [Vidéo] [...]   │      │
│ │                                                             │      │
│ │ Titre article                                               │      │
│ │ Paragraphe 1...                                             │      │
│ │ [Image intégrée]                                            │      │
│ │ Paragraphe 2...                                             │      │
│ │                                                             │      │
│ │ Sous-titre                                                  │      │
│ │ Paragraphe 3...                                             │      │
│ │                                                             │      │
│ │ [Citation: "Belle phrase"]                                  │      │
│ │                                                             │      │
│ │ Paragraphe 4...                                             │      │
│ │                                                             │      │
│ │ Mot count: 850 | Temps lecture: 5 min                       │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ SEO & MÉTADONNÉES                                                     │
│ ┌─────────────────────────────────────────────────────────────┐      │
│ │ Meta titre    [5 Conseils pour vendre votre bien - SCI4K]   │      │
│ │ Meta descrip. [Découvrez nos 5 meilleurs conseils pour...   │      │
│ │ Keywords      [vente, conseils, immobilier, abidjan]        │      │
│ │ Statut        [Brouillon ▼] [Visible ▼]                    │      │
│ │ Langue        [FR ▼]                                        │      │
│ └─────────────────────────────────────────────────────────────┘      │
│                                                                        │
│ [💾 Brouillon] [👁️ Aperçu] [🚀 Publier] [🗑️ Supprimer]              │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des FAQ

```
┌──────────────────────────────────────────────────────────────────────┐
│ FAQ - Questions Fréquentes                       [+ Ajouter question]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ CATÉGORIES                                                            │
│ ┌──────────────────┬──────────────────┬──────────────────┐           │
│ │ ☐ À propos       │ ☐ Vente          │ ☐ Location      │           │
│ │ (4 questions)    │ (5 questions)    │ (4 questions)   │           │
│ ├──────────────────┼──────────────────┼──────────────────┤           │
│ │ ☐ Construction   │ ☐ Processus      │ ☐ Légal          │           │
│ │ (3 questions)    │ (6 questions)    │ (3 questions)   │           │
│ └──────────────────┴──────────────────┴──────────────────┘           │
│                                                                        │
│ QUESTIONS - Catégorie: À propos (4)              Trier: [Ordre ▼]    │
│                                                                        │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ # │ Question                       │ Réponses │ État │ Actions │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ 1 │ Qui sommes-nous?               │ 128 ch   │ ✓    │ ✏️ 🗑️  │  │
│ │   │ ├─ Réponse... [Afficher]       │          │      │        │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ 2 │ Depuis quand opérez-vous?      │ 95 ch    │ ✓    │ ✏️ 🗑️  │  │
│ │   │ ├─ Réponse... [Afficher]       │          │      │        │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ 3 │ Zone d'intervention?           │ 156 ch   │ ✓    │ ✏️ 🗑️  │  │
│ │   │ ├─ Réponse... [Afficher]       │          │      │        │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ 4 │ Comment vous contacter?        │ 82 ch    │ ⚪   │ ✏️ 🗑️  │  │
│ │   │ ├─ Réponse... [Afficher]       │          │      │        │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│ [Ajouter question à cette catégorie]                                 │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘

ÉDITER UNE QUESTION:
┌──────────────────────────────────────────────────────────────────────┐
│ Modifier Question                                     [Aperçu] [Sauv.]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ Catégorie*        [À propos ▼]                                        │
│ Question*         [Qui sommes-nous?]                                 │
│ Réponse*          [Editor WYSIWYG - Texte long...]                   │
│ Ordre d'affichage [1]                                                │
│ Statut            [Publié ▼]                                          │
│                                                                        │
│ [💾 Enregistrer] [🗑️ Supprimer]                                      │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des Pages

```
┌──────────────────────────────────────────────────────────────────────┐
│ Gestion des Pages                                                     │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ PAGES PRINCIPALES                                                     │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ Page                    │ Statut │ Modifié    │ Actions        │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ 🏠 Accueil              │ ✓      │ 2d ago     │ ✏️ 👁️ 🔗     │  │
│ │ 👥 Présentation         │ ✓      │ 5d ago     │ ✏️ 👁️ 🔗     │  │
│ │ 🛠️ Services            │ ✓      │ 1w ago     │ ✏️ 👁️ 🔗     │  │
│ │ 📧 Contact              │ ✓      │ 1h ago     │ ✏️ 👁️ 🔗     │  │
│ │ ⚖️ Mentions Légales     │ ✓      │ 2w ago     │ ✏️ 👁️ 🔗     │  │
│ │ 🔒 Politique Confid.    │ ✓      │ 2w ago     │ ✏️ 👁️ 🔗     │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│ ÉDITER PAGE - Accueil                           [Aperçu] [Publier]   │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ Titre*               [Accueil SCI4K]                            │  │
│ │ Meta Description     [Plateforme immobilière SCI4K...]          │  │
│ │                                                                 │  │
│ │ ┌─────────────────────────────────────────────────────────────┐│  │
│ │ │ SECTION: Hero Section                       [✏️ Éditer]    ││  │
│ │ │ ├─ Titre: "Votre propriété, notre priorité"                ││  │
│ │ │ ├─ Image: [IMG]                                            ││  │
│ │ │ ├─ Bouton: "Contacter"                                     ││  │
│ │ └─────────────────────────────────────────────────────────────┘│  │
│ │                                                                 │  │
│ │ ┌─────────────────────────────────────────────────────────────┐│  │
│ │ │ SECTION: Services en avant (4 items)         [✏️ Éditer]   ││  │
│ │ │ ├─ Service 1 [Vente]                                       ││  │
│ │ │ ├─ Service 2 [Location]                                    ││  │
│ │ │ ├─ Service 3 [Construction]                                ││  │
│ │ │ └─ Service 4 [Conseil]                                     ││  │
│ │ └─────────────────────────────────────────────────────────────┘│  │
│ │                                                                 │  │
│ │ ┌─────────────────────────────────────────────────────────────┐│  │
│ │ │ SECTION: Biens en vedette (3 items)         [✏️ Éditer]   ││  │
│ │ │ └─ [Sélectionner les biens]                                ││  │
│ │ └─────────────────────────────────────────────────────────────┘│  │
│ │                                                                 │  │
│ │ ┌─────────────────────────────────────────────────────────────┐│  │
│ │ │ SECTION: Témoignages (3 items)              [✏️ Éditer]   ││  │
│ │ │ └─ [Ajouter/modifier témoignages]                           ││  │
│ │ └─────────────────────────────────────────────────────────────┘│  │
│ │                                                                 │  │
│ │ ┌─────────────────────────────────────────────────────────────┐│  │
│ │ │ SECTION: Partenaires (8 logos)              [✏️ Éditer]   ││  │
│ │ │ └─ [Logo1] [Logo2] [Logo3] [Logo4]...                       ││  │
│ │ └─────────────────────────────────────────────────────────────┘│  │
│ │                                                                 │  │
│ │ Statut: [Publié ▼]  Langue: [FR ▼]  Visible: [Oui ▼]          │  │
│ │                                                                 │  │
│ │ [💾 Enregistrer] [👁️ Aperçu] [🌐 Voir sur site]              │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Configuration

```
┌──────────────────────────────────────────────────────────────────────┐
│ Configuration Générale du Site                                [Sauv.] │
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ INFORMATIONS DE L'ENTREPRISE                                          │
│ ┌────────────────────────────────┬────────────────────────────────┐  │
│ │ Nom de l'entreprise*           │ [SCI4K]                        │  │
│ │ Slogan                         │ [Votre propriété, notre...     │  │
│ │ Description                    │ [Texte long...]               │  │
│ │ Logo [Télécharger] [IMG]       │ Favicon [Télécharger] [ICO]   │  │
│ │ Forme juridique                │ [SCI ▼]                       │  │
│ │ SIRET                          │ [00225 123456789]              │  │
│ └────────────────────────────────┴────────────────────────────────┘  │
│                                                                        │
│ COORDONNÉES                                                           │
│ ┌────────────────────────────────┬────────────────────────────────┐  │
│ │ Adresse*                       │ [Cocody, Abidjan]              │  │
│ │ Code Postal                    │ [00225]                        │  │
│ │ Pays                           │ [Côte d'Ivoire ▼]             │  │
│ │ Téléphone 1*                   │ [+225 XX XX XX]                │  │
│ │ Téléphone 2                    │ [+225 XX XX XX]                │  │
│ │ Email 1*                       │ [contact@sci4k.com]            │  │
│ │ Email 2                        │ [info@sci4k.com]               │  │
│ │ WhatsApp                       │ [+225 XX XX XX]                │  │
│ └────────────────────────────────┴────────────────────────────────┘  │
│                                                                        │
│ HORAIRES D'OUVERTURE                                                  │
│ ┌────────────────────────────────────────────────────────────────┐   │
│ │ Lundi - Vendredi    [09:00] à [18:00]     Samedi [09:00-14:00]    │
│ │ Dimanche: Fermé                                                   │
│ │ Jours fériés: Fermé                                               │
│ └────────────────────────────────────────────────────────────────┘   │
│                                                                        │
│ RÉSEAUX SOCIAUX                                                       │
│ ┌────────────────────────────────┬────────────────────────────────┐  │
│ │ Facebook                       │ [https://facebook.com/sci4k]   │  │
│ │ Instagram                      │ [https://instagram.com/sci4k]  │  │
│ │ LinkedIn                       │ [https://linkedin.com/sci4k]   │  │
│ │ Twitter                        │ [https://twitter.com/sci4k]    │  │
│ │ YouTube                        │ [https://youtube.com/sci4k]    │  │
│ └────────────────────────────────┴────────────────────────────────┘  │
│                                                                        │
│ PARAMÈTRES D'AFFICHAGE                                                │
│ ┌────────────────────────────────┬────────────────────────────────┐  │
│ │ Langue par défaut              │ [Français ▼]                   │  │
│ │ Thème par défaut               │ [Clair ▼]                      │  │
│ │ Nb articles par page (blog)    │ [10 ▼]                         │  │
│ │ Nb biens par page              │ [12 ▼]                         │  │
│ │ Afficher newsletter             │ [Oui ▼]                       │  │
│ │ Afficher notifications          │ [Oui ▼]                       │  │
│ └────────────────────────────────┴────────────────────────────────┘  │
│                                                                        │
│ [💾 Enregistrer Configuration]                                        │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des Utilisateurs

```
┌──────────────────────────────────────────────────────────────────────┐
│ Gestion des Utilisateurs Admin                        [+ Ajouter user]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ Utilisateur              │ Email            │ Rôle      │ Actif │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ [👤] Admin Principal     │ admin@sci4k.com  │ Super    │ ✓ 🟢  │  │
│ │     Connecté maintenant  │                  │ Admin    │ Online │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ [👤] Léon K.            │ leon@sci4k.com   │ Admin    │ ✓ 🟡  │  │
│ │     Dernier accès: 2h   │                  │          │ 2h ago │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ [👤] Emma D.            │ emma@sci4k.com   │ Éditeur  │ ✓ 🟡  │  │
│ │     Dernier accès: 4h   │                  │          │ 4h ago │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ [👤] Marc T.            │ marc@sci4k.com   │ Éditeur  │ ✓ 🔴  │  │
│ │     Dernier accès: 3d   │                  │          │ 3d ago │  │
│ ├─────────────────────────────────────────────────────────────────┤  │
│ │ [👤] Nouvel user        │ nouveau@sci4k.   │ Invite   │ ⚫ Inact│  │
│ │     Invitation envoyée  │                  │          │ Pending │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│ ÉDITER UTILISATEUR - Admin Principal                                 │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ Nom complet*         [Léon K.]                                  │  │
│ │ Email*               [admin@sci4k.com]                          │  │
│ │ Rôle*                [Super Admin ▼]                            │  │
│ │ Statut               [Actif ▼]                                  │  │
│ │                                                                 │  │
│ │ PERMISSIONS                                                    │  │
│ │ ☑ Gérer tous les contenus                                      │  │
│ │ ☑ Gérer utilisateurs                                           │  │
│ │ ☑ Gérer configuration                                          │  │
│ │ ☑ Voir analytics                                               │  │
│ │                                                                 │  │
│ │ Dernier accès: 2026-08-20 14:32                                │  │
│ │ Date création: 2026-01-15                                      │  │
│ │                                                                 │  │
│ │ [💾 Enregistrer] [🔑 Réinit. mot de passe] [🗑️ Supprimer]    │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Gestion des Médias

```
┌──────────────────────────────────────────────────────────────────────┐
│ Médiathèque                                      [+ Télécharger image]│
├──────────────────────────────────────────────────────────────────────┤
│                                                                        │
│ Dossiers:                                                              │
│ [📁 Biens] [📁 Blog] [📁 Services] [📁 Partenaires] [📁 Autre]      │
│                                                                        │
│ Parcourant: Biens                                                     │
│                                                                        │
│ ┌─────────────────────────────────────────────────────────────────┐  │
│ │ [IMG] [IMG] [IMG] [IMG] [IMG] [IMG] [IMG] [IMG]                │  │
│ │ [IMG] [IMG] [IMG] [IMG] [IMG] [IMG] [IMG] [IMG]                │  │
│ │ [IMG] [IMG] [IMG] [IMG] [IMG] [IMG]                            │  │
│ └─────────────────────────────────────────────────────────────────┘  │
│                                                                        │
│ Résultats: 54 images                                                 │
│                                                                        │
│ ÉDITER IMAGE                                                          │
│ ┌──────────────────────────────────────────────────────────────────┐ │
│ │ [Image preview - 300x300px]                                     │ │
│ │                                                                  │ │
│ │ Nom du fichier:  [villa_cocody_1.jpg]                           │ │
│ │ Titre:           [Villa Cocody - Vue Avant]                     │ │
│ │ Alt Text*:       [Villa de 250m² à Cocody avec jardin]         │ │
│ │ Description:     [Photo principale du bien...]                  │ │
│ │ Crédits:         [Photo © John Doe]                             │ │
│ │ Taille:          [2.4 MB] - [1920x1440px]                       │ │
│ │ Format optimisé: [WebP ▼]                                       │ │
│ │ Dossier:         [📁 Biens ▼]                                  │ │
│ │                                                                  │ │
│ │ [💾 Enregistrer] [🔗 Copier URL] [🗑️ Supprimer]                │ │
│ └──────────────────────────────────────────────────────────────────┘ │
│                                                                        │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 📊 Components Réutilisables

### Table Générique

```
┌─────────────────────────────────────────────────────┐
│ ☐ │ Colonne1 │ Colonne2 │ Colonne3 │ Colonne4 │ 🔧 │
├─────────────────────────────────────────────────────┤
│ ☐ │ Item 1   │ Value 1  │ Value 1  │ Value 1  │ ✏️ 🗑️│
├─────────────────────────────────────────────────────┤
│ ☐ │ Item 2   │ Value 2  │ Value 2  │ Value 2  │ ✏️ 🗑️│
├─────────────────────────────────────────────────────┤
│ ☐ │ Item 3   │ Value 3  │ Value 3  │ Value 3  │ ✏️ 🗑️│
└─────────────────────────────────────────────────────┘
```

### Formulaire

```
┌──────────────────────────────┐
│ Champ texte                  │
│ [Texte...]                   │
├──────────────────────────────┤
│ Sélect                       │
│ [Option 1 ▼]                │
├──────────────────────────────┤
│ Textarea                     │
│ [Texte long...]             │
│ [Max 500 caractères]        │
├──────────────────────────────┤
│ ☑ Checkbox                  │
│ ○ Radio bouton              │
├──────────────────────────────┤
│ Upload fichier              │
│ [Glisser-déposer ici]       │
│ [Ou cliquer pour parcourir] │
├──────────────────────────────┤
│ Date                        │
│ [20/08/2026] [🗓️]          │
├──────────────────────────────┤
│ Couleur                     │
│ [███████] #FF6B6B           │
├──────────────────────────────┤
│ [💾 Sauvegarder] [✕ Annuler]│
└──────────────────────────────┘
```

### Modal/Dialog

```
╔═══════════════════════════════════════════════════╗
║ Titre de la fenêtre                          [✕] ║
╠═══════════════════════════════════════════════════╣
║                                                   ║
║  Êtes-vous sûr de vouloir supprimer cet élément? ║
║                                                   ║
║  Cette action est irréversible.                   ║
║                                                   ║
╠═══════════════════════════════════════════════════╣
║              [Annuler] [Oui, Supprimer]          ║
╚═══════════════════════════════════════════════════╝
```

### Alert/Notification

```
┌─────────────────────────────────────────────┐
│ ✓ Succès: Bien mis à jour avec succès      │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ⚠️ Attention: Cet article sera publié       │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ❌ Erreur: Email déjà utilisé               │
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ ℹ️ Info: 3 changements en attente d'approbation│
└─────────────────────────────────────────────┘
```

---

## 🎨 Composants d'Interface

### Header Admin

```
┌──────────────────────────────────────────────────────────────────┐
│ ← | SCI4K Admin | [🔍 Recherche] | 🔔 | ⚙️ | 👤 Name | [🚪 Déco] │
└──────────────────────────────────────────────────────────────────┘
```

### Breadcrumb

```
Home > Biens > Modifier > Villa Cocody
```

### Pagination

```
< Précédent  [ 1 ] 2 3 4 5 ... 10  Suivant >
Afficher: [10 ▼] résultats par page
Total: 245 résultats
```

### Filtre/Recherche

```
┌────────────────────────────────────┐
│ 🔍 [Rechercher...]                │
├────────────────────────────────────┤
│ [Filtrer] [Réinitialiser]         │
└────────────────────────────────────┘
```

### Barre d'Actions

```
[+ Ajouter] [✏️ Éditer] [🔗 Voir] [🗑️ Supprimer] [📥 Import] [📤 Export]
```

---

## 📱 Responsive Breakdown

### Mobile (< 768px)

```
┌─────────────────────┐
│ ☰ │ SCI4K │ 🔍 👤  │
├─────────────────────┤
│ MENU SIDEBAR        │
│ ├─ Dashboard        │
│ ├─ Biens            │
│ ├─ Blog             │
│ └─ Config           │
├─────────────────────┤
│                     │
│  PAGE CONTENT       │
│  (Full Width)       │
│                     │
├─────────────────────┤
│ © SCI4K 2026        │
└─────────────────────┘
```

---

## 🎯 Résumé des Écrans Créés

| Écran | Élément | Complexité |
|-------|---------|-----------|
| Layout Principal | Navigation + Sidebar | Haute |
| Dashboard | Cards + Charts + Lists | Moyenne-Haute |
| Liste Biens | Table + Filtres + Pagination | Haute |
| Édition Bien | Formulaire + Galerie + SEO | Très Haute |
| Liste Articles | Table filtrée | Moyenne |
| Créer Article | WYSIWYG Editor | Très Haute |
| FAQ | Accordéons + CRUD | Moyenne |
| Pages | Sections imbriquées | Haute |
| Configuration | Formulaire long | Moyenne |
| Utilisateurs | Table + Permissions | Moyenne |
| Médias | Grid d'images | Moyenne |

---

## 🚀 Recommandations Techniques

### Stack Recommandé

```
Frontend:
├─ React/Vue.js (interface dynamique)
├─ Tailwind CSS (styling)
├─ React Query/SWR (data fetching)
└─ React Hook Form (gestion formulaires)

Backend:
├─ Node.js + Express ou PHP Laravel
├─ Database (PostgreSQL/MySQL)
├─ REST API ou GraphQL
└─ JWT Authentication

CMS Components:
├─ WYSIWYG Editor (TinyMCE/Quill)
├─ Image Upload (multer/sharp)
├─ File Management
└─ Admin Dashboard (Apache ECharts)
```

---

**Document généré:** 2026-08-20  
**Type:** Low-Fidelity Wireframes (ASCII)  
**Écrans:** 11 principaux + 8 components  
**Complétude:** 100%
