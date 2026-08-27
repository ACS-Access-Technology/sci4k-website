@extends('public.layout')

@section('titre', $bien->titre($langue))
@section('description', $bien->meta_description_fr ?: Str::limit(strip_tags($bien->description($langue)), 155))
@section('classe-page', 'page-biens')

@section('contenu')

<section class="page-banner pb-presentation">
  <div class="wrap">
    <h1 class="reveal">{{ $bien->titre($langue) }}</h1>
  </div>
</section>

<section class="properties-section">
  <div class="wrap">

    <p style="margin-bottom:24px;">
      <a href="{{ route('biens.index') }}" wire:navigate>← {{ __('Retour au catalogue') }}</a>
    </p>

    <div class="pres-grid">
      <div class="pres-media reveal">
        <div class="frame" style="overflow:hidden;">
          @if ($bien->photos->isNotEmpty())
            <img src="{{ asset($bien->photos->first()->fichier) }}"
                 alt="{{ $bien->photos->first()->texteAlternatif($langue) ?: $bien->titre($langue) }}"
                 loading="lazy" style="width:100%;height:100%;object-fit:cover">
          @else
            <x-public.illustration-bien :type="$bien->type" style="width:100%;height:auto;" />
          @endif
        </div>

        @if ($bien->photos->count() > 1)
          <div class="prop-meta" style="margin-top:12px;gap:8px;flex-wrap:wrap;">
            @foreach ($bien->photos->skip(1) as $photo)
              <img src="{{ asset($photo->fichier) }}" alt="{{ $photo->texteAlternatif($langue) }}"
                   loading="lazy" style="width:88px;height:64px;object-fit:cover;border-radius:8px;">
            @endforeach
          </div>
        @endif
      </div>

      <div class="pres-body">
        @if ($bien->estVendu())
          <div class="tag" style="margin-bottom:12px;">{{ __('Vendu') }}</div>
        @endif

        @if ($bien->sousTitre($langue))
          <div class="prop-type">{{ $bien->sousTitre($langue) }}</div>
        @endif

        <div class="prop-loc" style="margin-bottom:16px;">
          {{ $bien->quartier }}@if ($bien->quartier && $zoneLisible), @endif{{ $zoneLisible }}
        </div>

        <div class="overview-highlights" style="margin-bottom:20px;">
          @foreach ([
              __('Type') => $typeLisible,
              __('Surface') => ($bien->surface_habitable ?? $bien->surface_terrain) ? (($bien->surface_habitable ?? $bien->surface_terrain).' m²') : null,
              __('Pièces') => $bien->nombre_pieces,
              __('Chambres') => $bien->nombre_chambres,
              __("Salles d'eau") => $bien->nombre_salles_eau,
              __('Statut juridique') => $statutJuridiqueLisible,
              __('Numéro de titre') => $bien->numero_titre,
          ] as $intitule => $valeur)
            {{-- Une caracteristique absente ne s'affiche pas du tout : un
                 « Chambres : — » sur une annonce de terrain n'apprend rien. --}}
            @if ($valeur)
              <div class="highlight-card">
                <h4>{{ $intitule }}</h4>
                <p>{{ $valeur }}</p>
              </div>
            @endif
          @endforeach
        </div>

        @if ($bien->prixFormate())
          <p style="font-size:20px;font-weight:700;">{{ $bien->prixFormate() }}</p>
        @endif

        @if ($bien->description($langue))
          <p>{{ $bien->description($langue) }}</p>
        @endif

        @if ($bien->equipements($langue))
          <h3 class="reveal" style="margin-top:24px;">{{ __('Équipements') }}</h3>
          <ul class="spec-list">
            @foreach ($bien->equipements($langue) as $equipement)
              <li class="spec-item">{{ $equipement }}</li>
            @endforeach
          </ul>
        @endif

        {{-- La demande de visite part d'ICI, sur la fiche du bien : le
             visiteur n'a pas a recopier de quoi il parle, et l'agence sait
             immediatement quel bien est concerne. Elle arrive dans l'ecran
             « Demandes de visite » du backoffice. --}}
        <form id="formulaireVisite" class="contact-card" style="margin-top:32px;"
              data-bien="{{ $bien->slug }}" onsubmit="handleVisiteSubmit(event)">
          <h3>{{ __('Demander une visite') }}</h3>
          <p class="sub">{{ __('Laissez vos coordonnées : un conseiller vous rappelle pour convenir d’un créneau.') }}</p>

          <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
            <label for="visiteSiteWeb">Site web</label>
            <input type="text" id="visiteSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="visiteNom">{{ __('Nom complet') }} *</label>
              <input type="text" id="visiteNom" name="nom" required maxlength="80" autocomplete="name">
            </div>
            <div class="form-group">
              <label for="visiteTelephone">{{ __('Téléphone') }} *</label>
              <input type="tel" id="visiteTelephone" name="telephone" required maxlength="40" autocomplete="tel">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="visiteEmail">{{ __('E-mail') }}</label>
              <input type="email" id="visiteEmail" name="email" maxlength="160" autocomplete="email">
            </div>
            <div class="form-group">
              <label for="visiteCreneau">{{ __('Créneau souhaité') }}</label>
              <input type="date" id="visiteCreneau" name="creneau_souhaite" min="{{ now()->toDateString() }}">
            </div>
          </div>

          <div class="form-group">
            <label for="visiteMessage">{{ __('Précisions') }}</label>
            <textarea id="visiteMessage" name="message" rows="3" maxlength="2000"></textarea>
          </div>

          <button type="submit" class="hero-btn-primary">{{ __('Envoyer ma demande') }}</button>
          <p id="visiteConfirmation" style="display:none;margin-top:12px;">
            {{ __('Votre demande est enregistrée. Un conseiller vous rappelle sous 24 heures ouvrées.') }}
          </p>
        </form>
      </div>
    </div>

    @if ($similaires->isNotEmpty())
      <h3 style="margin-top:56px;">{{ __('Dans la même zone') }}</h3>
      <div class="prop-grid reveal-stagger" style="margin-top:16px;">
        @foreach ($similaires as $autre)
          <a class="prop-card reveal" href="{{ route('biens.detail', $autre->slug) }}" style="--i:{{ $loop->index }}">
            <div class="prop-visual">
              @if ($autre->photos->isNotEmpty())
                <img src="{{ asset($autre->photos->first()->fichier) }}" alt="" loading="lazy"
                     style="width:100%;height:100%;object-fit:cover">
              @else
                <x-public.illustration-bien :type="$autre->type" />
              @endif
            </div>
            <div class="prop-body">
              <h4>{{ $autre->titre($langue) }}</h4>
              <div class="prop-loc">{{ $autre->quartier }}</div>
            </div>
          </a>
        @endforeach
      </div>
    @endif
  </div>
</section>

@endsection
