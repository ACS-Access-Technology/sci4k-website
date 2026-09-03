{{--
  Les entrees de la barre laterale, ecrites UNE SEULE FOIS.

  Elles vivaient en double — une liste pour l'ecran large, une pour le menu
  telephone — et il fallait penser aux deux a chaque ecran ajoute. C'est
  exactement le defaut corrige au meme moment sur la navigation publique ; il
  s'etait installe ici pour la meme raison, et se voyait tout aussi peu :
  seule une fenetre reduite montrait l'oubli.

  Les groupes sont ceux de la maquette du backoffice — Pilotage, Contenu,
  Blocs du site, Reglages. « Demandes » viendra avec l'ecran des messages.
--}}
@php($administrateur = auth()->user()?->hasRole('administrateur'))

<p class="px-2 pb-1 pt-2 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Pilotage') }}</p>
<x-admin.lien-lateral route="dashboard" icone="grille" :intitule="__('Tableau de bord')" />
<x-admin.lien-lateral route="admin.journal" icone="journal" :intitule="__('Journal des activités')" />
<x-admin.lien-lateral route="admin.frequentation" icone="graphique" :intitule="__('Fréquentation')" />

{{-- Une entree par page publique. Les groupes « Contenu » et « Blocs du site »,
     qui listaient les ecrans par TYPE, ont disparu avec eux : chaque
     collection s'edite depuis l'ecran de la page qui l'affiche. --}}
<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Pages du site') }}</p>
<x-admin.lien-lateral route="admin.pages.accueil" icone="grille" :intitule="__('Accueil')" />
<x-admin.lien-lateral route="admin.pages.presentation" icone="personne" :intitule="__('Présentation')" />
<x-admin.lien-lateral route="admin.pages.biens" icone="maison" :intitule="__('Biens immobiliers')" />
<x-admin.lien-lateral route="admin.pages.services" icone="service" :intitule="__('Services')" />
<x-admin.lien-lateral route="admin.pages.actualites" icone="article" :intitule="__('Actualités')" />
<x-admin.lien-lateral route="admin.pages.faq" icone="question" :intitule="__('FAQ')" />
<x-admin.lien-lateral route="admin.pages.contact" icone="courrier" :intitule="__('Contact')" />

{{-- La mediatheque n'appartient a aucune page : elle porte les fichiers de
     toutes. Elle reste donc une entree a elle, la ou les quinze ecrans par
     type de contenu ont ete retires. --}}
<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Blocs du site') }}</p>
<x-admin.lien-lateral route="admin.mediatheque" icone="cartable" :intitule="__('Médiathèque')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Demandes') }}</p>
<x-admin.lien-lateral route="admin.messages" icone="courrier" :intitule="__('Messages de contact')" />
<x-admin.lien-lateral route="admin.visites" icone="calendrier" :intitule="__('Demandes de visite')" />
<x-admin.lien-lateral route="admin.newsletter" icone="abonne" :intitule="__('Abonnés newsletter')" />

{{-- Les trois ecrans de reglages sont reserves aux administrateurs. Leur
     route le refuse deja ; ne pas les AFFICHER a un editeur evite de lui
     proposer une porte qui se fermera sur lui. --}}
@if ($administrateur)
    <p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Réglages') }}</p>
    <x-admin.lien-lateral route="admin.configuration" icone="parametres" :intitule="__('Configuration')" />
    <x-admin.lien-lateral route="admin.menus" icone="menu" :intitule="__('Menus du site')" />
    <x-admin.lien-lateral route="admin.utilisateurs" icone="utilisateur" :intitule="__('Utilisateurs')" />
    <x-admin.lien-lateral route="admin.referentiels" icone="base" :intitule="__('Référentiels')" />
    <x-admin.lien-lateral route="admin.pages-statiques" icone="page" :intitule="__('Pages éditables')" />
@endif
