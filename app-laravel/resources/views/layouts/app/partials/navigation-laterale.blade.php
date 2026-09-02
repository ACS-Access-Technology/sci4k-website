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

{{-- Refonte en cours : une entree par page publique. Le groupe grossira au fil
     des pages traitees, et les groupes par type de contenu ci-dessous
     disparaitront quand toutes seront couvertes. --}}
<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Pages du site') }}</p>
<x-admin.lien-lateral route="admin.pages.accueil" icone="grille" :intitule="__('Accueil')" />
<x-admin.lien-lateral route="admin.pages.presentation" icone="personne" :intitule="__('Présentation')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Contenu') }}</p>
<x-admin.lien-lateral route="admin.biens.liste" motif="admin.biens.*" icone="maison" :intitule="__('Biens immobiliers')" />
<x-admin.lien-lateral route="admin.articles.liste" motif="admin.articles.*" icone="article" :intitule="__('Articles')" />
<x-admin.lien-lateral route="admin.services.liste" motif="admin.services.*" icone="service" :intitule="__('Services')" />
<x-admin.lien-lateral route="admin.faq.liste" motif="admin.faq.*" icone="bulle" :intitule="__('FAQ')" />
<x-admin.lien-lateral route="admin.rubriques-faq.liste" motif="admin.rubriques-faq.*" icone="question" :intitule="__('Rubriques de la FAQ')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Blocs du site') }}</p>
<x-admin.lien-lateral route="admin.temoignages.liste" motif="admin.temoignages.*" icone="etoile" :intitule="__('Témoignages')" />
<x-admin.lien-lateral route="admin.partenaires.liste" motif="admin.partenaires.*" icone="coeur" :intitule="__('Partenaires')" />
<x-admin.lien-lateral route="admin.equipe.liste" motif="admin.equipe.*" icone="personne" :intitule="__('Équipe')" />
<x-admin.lien-lateral route="admin.mediatheque" icone="cartable" :intitule="__('Médiathèque')" />
<x-admin.lien-lateral route="admin.valeurs" icone="coeur" :intitule="__('Valeurs')" />
<x-admin.lien-lateral route="admin.chiffres-cles" icone="graphique" :intitule="__('Chiffres clés')" />
<x-admin.lien-lateral route="admin.etapes-processus" icone="escalier" :intitule="__('Étapes du processus')" />
<x-admin.lien-lateral route="admin.banderole" icone="bandeau" :intitule="__('Banderole des communes')" />
<x-admin.lien-lateral route="admin.encarts.liste" motif="admin.encarts.*" icone="encart" :intitule="__('Annonces & Actions')" />
<x-admin.lien-lateral route="admin.images-de-fond.liste" motif="admin.images-de-fond.*" icone="image" :intitule="__('Images de fond')" />
<x-admin.lien-lateral route="admin.reglages-de-section.liste" motif="admin.reglages-de-section.*" icone="crayon" :intitule="__('En-têtes de section')" />

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
