@extends('public.layout')

@section('titre', $banniere?->texteBilingue('meta_titre', $langue) ?: __('Présentation'))
@section('description', $banniere?->texteBilingue('meta_description', $langue) ?: __("Société Civile Immobilière basée à Abidjan : découvrez la vision, les engagements et l'équipe de SCI4K."))
@section('classe-page', 'page-presentation')

@section('contenu')

{{--
  La signature sous le mot du directeur etait ecrite en dur : une agence qui
  change de directeur general devait rouvrir le code.

  « Fermer » vient de l'habillage du site, sur l'ecran « Menus » : il ferme
  aussi la fiche d'un bien et la fenetre d'un service.
--}}
@php($tDirecteur = fn (string $nom, string $defaut) => $motDuDirecteur?->texteBilingue($nom, $langue) ?: $defaut)
@php($tSite = fn (string $nom, string $defaut) => $chrome?->texteBilingue($nom, $langue) ?: $defaut)

{{--
  Page de presentation, portee depuis frontoffice/presentation.html.

  Le balisage est repris tel quel — memes classes, meme structure — et seuls
  les textes changent de source : ils viennent de la base au lieu du
  dictionnaire de main.js.

  Les en-tetes de section passent par ReglageDeSection ; les valeurs et les
  membres d'equipe par leurs propres tables. Chaque en-tete se replie sur le
  texte d'origine s'il est absent de la base, de sorte que la page reste
  complete meme avant que l'import ne soit rejoue.
--}}

<section class="page-banner pb-presentation">
  <div class="wrap">
    @if ($banniere?->etiquette($langue))
      <div class="tag reveal">{{ $banniere->etiquette($langue) }}</div>
    @endif
    <h1 class="reveal">{{ $banniere?->titre($langue) ?: __('Excellence, Transparence & Vision Durable') }}</h1>
    @if ($banniere?->chapo($langue))
      <p class="reveal">{{ $banniere->chapo($langue) }}</p>
    @endif
  </div>
</section>

{{-- PRÉSENTATION DE L'ENTREPRISE --}}
<section class="company-overview">
  <div class="wrap overview-grid">
    <div class="overview-content reveal">
      @if ($apercu?->etiquette($langue))
        <div class="tag" style="color:var(--accent-etiquette); border-color:var(--gold-500);">{{ $apercu->etiquette($langue) }}</div>
      @endif
      <h2>{{ $apercu?->titre($langue) ?: __('Présentation Générale de SCI4K') }}</h2>

      {{-- Le chapô porte plusieurs paragraphes, separes par une ligne vide :
           meme convention que le contenu d'un article. Les decouper ici plutot
           que d'imposer un champ par paragraphe laisse l'editeur en ajouter ou
           en retirer sans qu'on touche au gabarit. --}}
      @foreach ($apercu?->paragraphes($langue) ?: [] as $paragraphe)
        <p>{{ $paragraphe }}</p>
      @endforeach

      {{-- Les deux cartes viennent des options de la section, editables depuis
           « Pages du site → Présentation ». Elles etaient ecrites en dur : leur
           texte parle de notaires agrees et de quartiers couverts, deux choses
           qui changent sans qu'on touche au code. Le repli reprend le texte
           d'origine, pour qu'une base non renseignee affiche la meme page. --}}
      <div class="overview-highlights">
        <div class="highlight-card">
          <h4>{{ $apercu?->option('atout1_titre_'.$langue) ?: __('Expertise Juridique') }}</h4>
          <p>{{ $apercu?->option('atout1_texte_'.$langue) ?: __('Toutes nos transactions et acquisitions sont scrupuleusement auditées par des notaires agréés.') }}</p>
        </div>
        <div class="highlight-card">
          <h4>{{ $apercu?->option('atout2_titre_'.$langue) ?: __('Ancrage Abidjanais') }}</h4>
          <p>{{ $apercu?->option('atout2_texte_'.$langue) ?: __('Une présence active dans tous les secteurs stratégiques (Cocody, Plateau, Riviera, Marcory, Bingerville).') }}</p>
        </div>
      </div>
    </div>

    {{-- Le repli sur le fichier d'origine n'est pas decoratif : l'entree peut
         etre masquee depuis « Images de fond », et une section sans
         illustration vaut mieux qu'une image cassee. --}}
    <div class="overview-media reveal">
      <img src="{{ asset($visuelApercu?->fichier ?: 'images/presentation/apercu.jpg') }}"
           alt="{{ $visuelApercu?->texteAlternatif($langue) ?: __('Immobilier Abidjan') }}"
           style="border-radius:24px; box-shadow:var(--shadow);" loading="lazy">
    </div>
  </div>
</section>

{{-- MOT DU DIRECTEUR GÉNÉRAL --}}
<section class="presentation">
  <div class="wrap pres-grid">
    <div class="pres-media reveal">
      <div class="frame">
        <img src="{{ asset($visuelDirecteur?->fichier ?: 'images/presentation/silhouette.svg') }}"
             alt="{{ $visuelDirecteur?->texteAlternatif($langue) ?: __('Portrait du Directeur Général') }}" loading="lazy">
      </div>
      {{-- Le compteur vient des options de la section. Sa valeur etait ecrite
           en dur : « 14 quartiers » aurait vieilli en silence. --}}
      <div class="float-card">
        <b class="cnt" data-target="{{ (int) ($motDuDirecteur?->option('compteur_valeur') ?: 14) }}">0</b>
        <span>{{ $motDuDirecteur?->option('compteur_libelle_'.$langue) ?: __("quartiers d'Abidjan couverts par notre réseau d'agents") }}</span>
      </div>
    </div>
    <div class="pres-body">
      @if ($motDuDirecteur?->etiquette($langue))
        <div class="tag reveal" style="color:var(--accent-etiquette); border-color:var(--gold-500);">{{ $motDuDirecteur->etiquette($langue) }}</div>
      @endif
      <h3 class="reveal">{{ $motDuDirecteur?->titre($langue) ?: __('Bâtir des lieux de vie, pas seulement des bâtiments.') }}</h3>

      @foreach ($motDuDirecteur?->paragraphes($langue) ?: [] as $paragraphe)
        <p class="reveal">{{ $paragraphe }}</p>
      @endforeach

      <div class="signature reveal">
        <div>
          <div class="sig-hand">TIEMOKO Regis</div>
          <div class="sig-name">{{ $tDirecteur('signature_nom', __('Le Directeur Général')) }}</div>
          <div class="sig-role">{{ $tDirecteur('signature_role', __('SCI4K — Société Civile Immobilière Abidjan')) }}</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- NOS PILIERS --}}
@if ($valeurs->isNotEmpty())
  <section class="values-section">
    <div class="wrap">
      <div class="section-head reveal" style="max-width:640px;">
        @if ($enteteValeurs?->etiquette($langue))
          <div class="tag" style="color:var(--gold-300); border-color:rgba(211,182,172,0.5);">{{ $enteteValeurs->etiquette($langue) }}</div>
        @endif
        <h2 style="color:#fff;">{{ $enteteValeurs?->titre($langue) ?: __('Les engagements de SCI4K') }}</h2>
        @if ($enteteValeurs?->chapo($langue))
          <p style="color:rgba(255,255,255,0.75);">{{ $enteteValeurs->chapo($langue) }}</p>
        @endif
      </div>

      {{-- La numerotation suit le RANG d'affichage : reordonner les valeurs
           doit renumeroter la grille, sinon la page montrerait « 01, 03, 02 ». --}}
      <div class="values-grid reveal-stagger">
        @foreach ($valeurs as $valeur)
          <div class="value-card reveal" style="--i:{{ $loop->index }}">
            <div class="value-num">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
            <h4>{{ $valeur->titre($langue) }}</h4>
            <p>{{ $valeur->texte($langue) }}</p>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- CAPITAL HUMAIN --}}
@if ($membres->isNotEmpty())
  <section class="team-section">
    <div class="wrap">
      <div class="section-head reveal" style="max-width:640px;">
        @if ($enteteEquipe?->etiquette($langue))
          <div class="tag" style="color:var(--accent-etiquette); border-color:var(--gold-500);">{{ $enteteEquipe->etiquette($langue) }}</div>
        @endif
        <h2>{{ $enteteEquipe?->titre($langue) ?: __("Notre Équipe d'Experts") }}</h2>
        @if ($enteteEquipe?->chapo($langue))
          <p>{{ $enteteEquipe->chapo($langue) }}</p>
        @endif
      </div>

      <div class="team-grid reveal-stagger">
        @foreach ($membres as $membre)
            <button type="button" class="team-card reveal" style="--i:{{ $loop->index }}"
              data-team-name="{{ $membre->nom }}" data-team-role="{{ $membre->fonction($langue) }}"
              data-team-bio="{{ $membre->biographie($langue) }}" data-team-email="{{ $membre->email }}"
              data-team-linkedin="{{ $membre->linkedin }}" data-team-photo="{{ $membre->photo ? asset($membre->photo) : '' }}"
              aria-haspopup="dialog">
            <div>
              <div class="team-avatar">
                {{-- La photo remplace la silhouette quand elle existe. Le
                     tracé d'origine reste le repli : une vignette vide serait
                     plus visible qu'un pictogramme neutre. --}}
                @if ($membre->photo)
                  <img src="{{ asset($membre->photo) }}" alt="{{ $membre->nom }}" loading="lazy">
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
                @endif

                @if ($membre->etiquette($langue))
                  <span class="team-badge">{{ $membre->etiquette($langue) }}</span>
                @endif
              </div>
              <div class="team-body">
                <h4>{{ $membre->nom }}</h4>
                <span class="team-role">{{ $membre->fonction($langue) }}</span>
                @if ($membre->biographie($langue))
                  <p class="team-desc">{{ Str::limit($membre->biographie($langue), 220) }}</p>
                @endif
              </div>
            </div>
          </button>
        @endforeach
      </div>
    </div>
  </section>
@endif

<div class="modal-overlay" id="teamModalOverlay" role="presentation" hidden>
  <div class="modal-container team-modal" role="dialog" aria-modal="true" aria-labelledby="teamModalTitle">
    <button type="button" class="modal-close" id="teamModalClose" aria-label="{{ $tSite('libelle_fermer', __('Fermer')) }}">&times;</button>
    <div class="team-modal-head">
      <div class="team-avatar" id="teamModalAvatar"></div>
      <div><h2 class="modal-title" id="teamModalTitle"></h2><p class="team-role" id="teamModalRole"></p></div>
    </div>
    <p class="team-modal-bio" id="teamModalBio"></p>
    <div class="team-modal-links" id="teamModalLinks"></div>
  </div>
</div>

@endsection
