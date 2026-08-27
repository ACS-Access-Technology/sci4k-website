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
<x-admin.lien-lateral route="dashboard" :intitule="__('Tableau de bord')" />
<x-admin.lien-lateral route="admin.journal" :intitule="__('Journal des activités')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Contenu') }}</p>
<x-admin.lien-lateral route="admin.articles.liste" motif="admin.articles.*" :intitule="__('Articles')" />
<x-admin.lien-lateral route="admin.services.liste" motif="admin.services.*" :intitule="__('Services')" />
<x-admin.lien-lateral route="admin.faq.liste" motif="admin.faq.*" :intitule="__('FAQ')" />
<x-admin.lien-lateral route="admin.rubriques-faq.liste" motif="admin.rubriques-faq.*" :intitule="__('Rubriques de la FAQ')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Blocs du site') }}</p>
<x-admin.lien-lateral route="admin.temoignages.liste" motif="admin.temoignages.*" :intitule="__('Témoignages')" />
<x-admin.lien-lateral route="admin.partenaires.liste" motif="admin.partenaires.*" :intitule="__('Partenaires')" />
<x-admin.lien-lateral route="admin.equipe.liste" motif="admin.equipe.*" :intitule="__('Équipe')" />
<x-admin.lien-lateral route="admin.valeurs" :intitule="__('Valeurs')" />
<x-admin.lien-lateral route="admin.chiffres-cles" :intitule="__('Chiffres clés')" />
<x-admin.lien-lateral route="admin.etapes-processus" :intitule="__('Étapes du processus')" />
<x-admin.lien-lateral route="admin.banderole" :intitule="__('Banderole des communes')" />
<x-admin.lien-lateral route="admin.encarts.liste" motif="admin.encarts.*" :intitule="__('Encarts')" />
<x-admin.lien-lateral route="admin.images-de-fond.liste" motif="admin.images-de-fond.*" :intitule="__('Images de fond')" />
<x-admin.lien-lateral route="admin.reglages-de-section.liste" motif="admin.reglages-de-section.*" :intitule="__('En-têtes de section')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Demandes') }}</p>
<x-admin.lien-lateral route="admin.messages" :intitule="__('Messages de contact')" />
<x-admin.lien-lateral route="admin.newsletter" :intitule="__('Abonnés newsletter')" />

{{-- Les trois ecrans de reglages sont reserves aux administrateurs. Leur
     route le refuse deja ; ne pas les AFFICHER a un editeur evite de lui
     proposer une porte qui se fermera sur lui. --}}
@if ($administrateur)
    <p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Réglages') }}</p>
    <x-admin.lien-lateral route="admin.configuration" :intitule="__('Configuration')" />
    <x-admin.lien-lateral route="admin.menus" :intitule="__('Menus du site')" />
    <x-admin.lien-lateral route="admin.utilisateurs" :intitule="__('Utilisateurs')" />
    <x-admin.lien-lateral route="admin.referentiels" :intitule="__('Référentiels')" />
@endif
