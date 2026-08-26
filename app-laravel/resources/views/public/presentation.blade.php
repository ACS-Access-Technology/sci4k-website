@extends('public.layout')

@section('titre', __('Présentation'))
@section('description', __("Société Civile Immobilière basée à Abidjan : découvrez la vision, les engagements et l'équipe de SCI4K."))
@section('classe-page', 'page-presentation')

@section('contenu')

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
      @foreach (preg_split('/\R{2,}/u', trim((string) $apercu?->chapo($langue))) ?: [] as $paragraphe)
        @continue(blank($paragraphe))
        <p>{{ $paragraphe }}</p>
      @endforeach

      <div class="overview-highlights">
        <div class="highlight-card">
          <h4>{{ __('Expertise Juridique') }}</h4>
          <p>{{ __('Toutes nos transactions et acquisitions sont scrupuleusement auditées par des notaires agréés.') }}</p>
        </div>
        <div class="highlight-card">
          <h4>{{ __('Ancrage Abidjanais') }}</h4>
          <p>{{ __('Une présence active dans tous les secteurs stratégiques (Cocody, Plateau, Riviera, Marcory, Bingerville).') }}</p>
        </div>
      </div>
    </div>

    <div class="overview-media reveal">
      <img src="{{ asset('images/presentation/apercu.jpg') }}" alt="{{ __('Immobilier Abidjan') }}"
           style="border-radius:24px; box-shadow:var(--shadow);" loading="lazy">
    </div>
  </div>
</section>

{{-- MOT DU DIRECTEUR GÉNÉRAL --}}
<section class="presentation">
  <div class="wrap pres-grid">
    <div class="pres-media reveal">
      <div class="frame">
        <img src="{{ asset('images/presentation/silhouette.svg') }}" alt="{{ __('Portrait du Directeur Général') }}" loading="lazy">
      </div>
      <div class="float-card">
        <b class="cnt" data-target="14">0</b>
        <span>{{ __("quartiers d'Abidjan couverts par notre réseau d'agents") }}</span>
      </div>
    </div>
    <div class="pres-body">
      @if ($motDuDirecteur?->etiquette($langue))
        <div class="tag reveal" style="color:var(--accent-etiquette); border-color:var(--gold-500);">{{ $motDuDirecteur->etiquette($langue) }}</div>
      @endif
      <h3 class="reveal">{{ $motDuDirecteur?->titre($langue) ?: __('Bâtir des lieux de vie, pas seulement des bâtiments.') }}</h3>

      @foreach (preg_split('/\R{2,}/u', trim((string) $motDuDirecteur?->chapo($langue))) ?: [] as $paragraphe)
        @continue(blank($paragraphe))
        <p class="reveal">{{ $paragraphe }}</p>
      @endforeach

      <div class="signature reveal">
        <div>
          <div class="sig-hand">TIEMOKO Regis</div>
          <div class="sig-name">{{ __('Le Directeur Général') }}</div>
          <div class="sig-role">{{ __('SCI4K — Société Civile Immobilière Abidjan') }}</div>
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
          <div class="team-card reveal" style="--i:{{ $loop->index }}">
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
                  <p class="team-desc">{{ $membre->biographie($langue) }}</p>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

@endsection
