@extends('public.layout')

@section('titre', $bien->titre($langue))
@section('description', $bien->meta_description_fr ?: Str::limit(strip_tags($bien->description($langue)), 155))
@section('classe-page', 'page-biens')

@section('contenu')

{{--
  Les libelles de cette fiche etaient ecrits en dur et traduits par __() :
  aucun ecran ne les exposait. Ils viennent maintenant de « Pages du site →
  Biens », modules « Fiche d'un bien » et « Demande de visite ».

  Ce sont LES MEMES sections que celles du catalogue : la fenetre qui s'ouvre
  depuis la grille affiche les memes caracteristiques et le meme formulaire.
  Les dire deux fois aurait laisse les deux versions diverger, et l'editeur
  aurait corrige l'une en croyant avoir corrige les deux.

  La pastille « Vendu » fait exception : elle appartient a la grille, et suit
  donc la section du catalogue.
--}}
@php($tFiche = fn (string $nom, string $defaut) => $sectionFiche?->texteBilingue($nom, $langue) ?: $defaut)
@php($tVisite = fn (string $nom, string $defaut) => $sectionVisite?->texteBilingue($nom, $langue) ?: $defaut)
@php($tGrille = fn (string $nom, string $defaut) => $sectionCatalogue?->texteBilingue($nom, $langue) ?: $defaut)

<section class="page-banner pb-presentation">
  <div class="wrap">
    <h1 class="reveal">{{ $bien->titre($langue) }}</h1>
  </div>
</section>

<section class="properties-section">
  <div class="wrap">

    <p style="margin-bottom:24px;">
      <a href="{{ route('biens.index') }}" wire:navigate>← {{ $tFiche('lien_retour', __('Retour au catalogue')) }}</a>
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
          <div class="tag" style="margin-bottom:12px;">{{ $tGrille('pastille_vendu', __('Vendu')) }}</div>
        @endif

        @if ($bien->sousTitre($langue))
          <div class="prop-type">{{ $bien->sousTitre($langue) }}</div>
        @endif

        <div class="prop-loc" style="margin-bottom:16px;">
          {{ $bien->quartier }}@if ($bien->quartier && $zoneLisible), @endif{{ $zoneLisible }}
        </div>

        <div class="overview-highlights" style="margin-bottom:20px;">
          @foreach ([
              $tFiche('libelle_type', __('Type')) => $typeLisible,
              $tFiche('libelle_surface', __('Surface')) => ($bien->surface_habitable ?? $bien->surface_terrain) ? (($bien->surface_habitable ?? $bien->surface_terrain).' m²') : null,
              $tFiche('libelle_pieces', __('Pièces')) => $bien->nombre_pieces,
              $tFiche('libelle_chambres', __('Chambres')) => $bien->nombre_chambres,
              $tFiche('libelle_salles_eau', __("Salles d'eau")) => $bien->nombre_salles_eau,
              $tFiche('libelle_statut_juridique', __('Statut juridique')) => $statutJuridiqueLisible,
              $tFiche('libelle_numero_titre', __('Numéro de titre')) => $bien->numero_titre,
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
          <h3 class="reveal" style="margin-top:24px;">{{ $tFiche('titre_equipements', __('Équipements')) }}</h3>
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
          <h3>{{ $tVisite('titre', __('Demander une visite')) }}</h3>
          <p class="sub">{{ $tVisite('accroche', __('Laissez vos coordonnées : un conseiller vous rappelle pour convenir d’un créneau.')) }}</p>

          <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
            <label for="visiteSiteWeb">Site web</label>
            <input type="text" id="visiteSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="visiteNom">{{ $tVisite('libelle_nom', __('Nom complet')) }} *</label>
              <input type="text" id="visiteNom" name="nom" required maxlength="80" autocomplete="name">
            </div>
            <div class="form-group">
              <label for="visiteTelephone">{{ $tVisite('libelle_telephone', __('Téléphone')) }} *</label>
              <input type="tel" id="visiteTelephone" name="telephone" required maxlength="40" autocomplete="tel">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="visiteEmail">{{ $tVisite('libelle_email', __('E-mail')) }}</label>
              <input type="email" id="visiteEmail" name="email" maxlength="160" autocomplete="email">
            </div>
            <div class="form-group">
              <label for="visiteCreneau">{{ $tVisite('libelle_creneau', __('Créneau souhaité')) }}</label>
              <input type="date" id="visiteCreneau" name="creneau_souhaite" min="{{ now()->toDateString() }}">
            </div>
          </div>

          <div class="form-group">
            <label for="visiteMessage">{{ $tVisite('libelle_precisions', __('Précisions')) }}</label>
            <textarea id="visiteMessage" name="message" rows="3" maxlength="2000"></textarea>
          </div>

          <button type="submit" class="hero-btn-primary">{{ $tVisite('libelle_bouton', __('Envoyer ma demande')) }}</button>
          <p id="visiteConfirmation" style="display:none;margin-top:12px;">
            {{ $tVisite('confirmation', __('Votre demande est enregistrée. Un conseiller vous rappelle sous 24 heures ouvrées.')) }}
          </p>
        </form>
      </div>
    </div>

    @if ($similaires->isNotEmpty())
      <h3 style="margin-top:56px;">{{ $tFiche('titre_meme_zone', __('Dans la même zone')) }}</h3>
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
