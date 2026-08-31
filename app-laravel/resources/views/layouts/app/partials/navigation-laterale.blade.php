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
<x-admin.lien-lateral route="admin.journal" icone="horloge" :intitule="__('Journal des activités')" />
<x-admin.lien-lateral route="admin.frequentation" icone="oeil" :intitule="__('Fréquentation')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Contenu') }}</p>
<x-admin.lien-lateral route="admin.biens.liste" motif="admin.biens.*" icone="archive" :intitule="__('Biens immobiliers')" />
<x-admin.lien-lateral route="admin.articles.liste" motif="admin.articles.*" icone="document" :intitule="__('Articles')" />
<x-admin.lien-lateral route="admin.services.liste" motif="admin.services.*" icone="grille" :intitule="__('Services')" />
<x-admin.lien-lateral route="admin.faq.liste" motif="admin.faq.*" icone="question" :intitule="__('FAQ')" />
<x-admin.lien-lateral route="admin.rubriques-faq.liste" motif="admin.rubriques-faq.*" icone="question" :intitule="__('Rubriques de la FAQ')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Blocs du site') }}</p>
<x-admin.lien-lateral route="admin.temoignages.liste" motif="admin.temoignages.*" icone="guillemets" :intitule="__('Témoignages')" />
<x-admin.lien-lateral route="admin.partenaires.liste" motif="admin.partenaires.*" icone="archive" :intitule="__('Partenaires')" />
<x-admin.lien-lateral route="admin.equipe.liste" motif="admin.equipe.*" icone="personne" :intitule="__('Équipe')" />
<x-admin.lien-lateral route="admin.mediatheque" icone="grille" :intitule="__('Médiathèque')" />
<x-admin.lien-lateral route="admin.valeurs" icone="question" :intitule="__('Valeurs')" />
<x-admin.lien-lateral route="admin.chiffres-cles" icone="grille" :intitule="__('Chiffres clés')" />
<x-admin.lien-lateral route="admin.etapes-processus" icone="chevron-down" :intitule="__('Étapes du processus')" />
<x-admin.lien-lateral route="admin.banderole" icone="document" :intitule="__('Banderole des communes')" />
<x-admin.lien-lateral route="admin.encarts.liste" motif="admin.encarts.*" icone="archive" :intitule="__('Encarts')" />
<x-admin.lien-lateral route="admin.images-de-fond.liste" motif="admin.images-de-fond.*" icone="oeil" :intitule="__('Images de fond')" />
<x-admin.lien-lateral route="admin.reglages-de-section.liste" motif="admin.reglages-de-section.*" icone="crayon" :intitule="__('En-têtes de section')" />

<p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Demandes') }}</p>
<x-admin.lien-lateral route="admin.messages" icone="document" :intitule="__('Messages de contact')" />
<x-admin.lien-lateral route="admin.visites" icone="horloge" :intitule="__('Demandes de visite')" />
<x-admin.lien-lateral route="admin.newsletter" icone="personne" :intitule="__('Abonnés newsletter')" />

{{-- Les trois ecrans de reglages sont reserves aux administrateurs. Leur
     route le refuse deja ; ne pas les AFFICHER a un editeur evite de lui
     proposer une porte qui se fermera sur lui. --}}
@if ($administrateur)
    <p class="px-2 pb-1 pt-4 text-xs font-medium uppercase tracking-wide text-zinc-400">{{ __('Réglages') }}</p>
    <x-admin.lien-lateral route="admin.configuration" icone="crayon" :intitule="__('Configuration')" />
    <x-admin.lien-lateral route="admin.menus" icone="grille" :intitule="__('Menus du site')" />
    <x-admin.lien-lateral route="admin.utilisateurs" icone="personne" :intitule="__('Utilisateurs')" />
    <x-admin.lien-lateral route="admin.referentiels" icone="archive" :intitule="__('Référentiels')" />
    <x-admin.lien-lateral route="admin.pages-statiques" icone="document" :intitule="__('Pages éditables')" />
@endif
