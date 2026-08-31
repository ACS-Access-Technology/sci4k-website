{{--
  Catalogue des biens, cote visiteur.

  Reprend fidelement la structure de frontoffice/biens.html — meme bandeau,
  meme carte de recherche, memes pastilles, meme grille — mais le tri se fait
  desormais SUR LE SERVEUR. Le site rendait ses biens d'un bloc puis les
  masquait en JavaScript : tout le catalogue traversait le reseau a chaque
  visite.
--}}
<div>

<section class="page-hero">
  <div class="wrap">
    <div class="page-title">
      <div class="tag">{{ $banniere?->etiquette($langue) ?: __('Catalogue de biens') }}</div>
      <h1>{{ $banniere?->titre($langue) ?: __('Biens Immobiliers à Abidjan') }}</h1>
      <p>{{ $banniere?->chapo($langue) ?: __("Trouvez le bien idéal à l'achat ou à la location. Cliquez sur un bien pour consulter sa fiche descriptive intégrale.") }}</p>
    </div>

    <div class="search-card" id="searchCard">
      <div class="search-top-bar">
        <div class="seg">
          @foreach ([\App\Models\Bien::LOCATION, \App\Models\Bien::VENTE] as $cle)
            <button type="button" wire:click="$set('offre', '{{ $offre === $cle ? '' : $cle }}')"
                    @class(['active' => $offre === $cle])>{{ $offres[$cle] }}</button>
          @endforeach
        </div>
        <div style="font-size:13px; font-weight:700; color:var(--texte-appuye);">{{ $filtres?->titre($langue) ?: __('Filtres multicritères') }}</div>
      </div>

      <div class="field-grid">
        <div class="field">
          <label for="filtreType">{{ __('Type de bien') }}</label>
          <select id="filtreType" wire:model.live="type">
            <option value="">{{ __('Tous les types') }}</option>
            @foreach ($types as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtreZone">{{ __('Localité') }}</label>
          <select id="filtreZone" wire:model.live="zone">
            <option value="">{{ __('Toutes les zones') }}</option>
            @foreach ($zones as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtrePieces">{{ __('Nombre de Pièces') }}</label>
          <select id="filtrePieces" wire:model.live="pieces">
            <option value="">{{ __('Toutes pièces') }}</option>
            @foreach ($tranchesPieces as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtreSurface">{{ __('Surface (m²)') }}</label>
          <select id="filtreSurface" wire:model.live="surface">
            <option value="">{{ __('Toutes surfaces') }}</option>
            @foreach ($tranchesSurface as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- Le bouton ne declenche plus la recherche : chaque liste la relance
           d'elle-meme. Il ramene a la liste complete, ce qui est la seule
           action qui manquait au visiteur. --}}
      <button type="button" class="search-submit" wire:click="reinitialiser">{{ __('Rechercher le bien idéal') }} →</button>
    </div>
  </div>
</section>

<section class="properties-section" wire:loading.class="opacity-50" wire:loading.style="pointer-events:none;transition:opacity .2s">
  <div class="wrap">
    <div class="filters-bar">
      <div class="filters" id="pillFilters">
        <button type="button" @class(['pill', 'active' => $type === '' && $offre === '']) wire:click="reinitialiser">{{ __('Tous') }}</button>
        @foreach ([\App\Models\Bien::LOCATION, \App\Models\Bien::VENTE] as $cle)
          <button type="button" @class(['pill', 'active' => $offre === $cle]) wire:click="$set('offre', '{{ $offre === $cle ? '' : $cle }}')">{{ $offres[$cle] }}</button>
        @endforeach
        @foreach ($types as $valeur)
          <button type="button" @class(['pill', 'active' => $type === $valeur->valeur]) wire:click="$set('type', '{{ $type === $valeur->valeur ? '' : $valeur->valeur }}')">{{ $valeur->libelle($langue) }}</button>
        @endforeach
      </div>

      <div class="results-count">
        {{ trans_choice(':nombre bien disponible|:nombre biens disponibles', $biens->total(), ['nombre' => $biens->total()]) }}
      </div>
    </div>

    <div class="prop-grid reveal-stagger">
      @forelse ($biens as $bien)
        <article class="prop-card" style="--i:{{ $loop->index }}">
          <div class="prop-visual">
            <span @class(['prop-badge', 'vente' => $bien->offre === \App\Models\Bien::VENTE])>
              {{ $offres[$bien->offre] ?? $bien->offre }}
            </span>

            @if ($bien->photos->isNotEmpty())
              <img src="{{ asset($bien->photos->first()->fichier) }}"
                   alt="{{ $bien->photos->first()->texteAlternatif($langue) ?: $bien->titre($langue) }}"
                   loading="lazy" style="width:100%;height:100%;object-fit:cover">
            @else
              {{-- Les six biens repris du site n'ont pas de photo : le dessin
                   tient lieu de visuel, comme aujourd'hui. --}}
              <x-public.illustration-bien :type="$bien->type" />
            @endif

            @if ($bien->estVendu())
              <span class="prop-badge" style="top:auto;bottom:12px;">{{ __('Vendu') }}</span>
            @endif
          </div>

          <div class="prop-body">
            <div class="prop-type">{{ $bien->sousTitre($langue) }}</div>
            <h4>{{ $bien->titre($langue) }}</h4>
            <div class="prop-loc">
              {{ $bien->quartier }}@if ($bien->quartier && $zones->firstWhere('valeur', $bien->zone)), @endif{{ $zones->firstWhere('valeur', $bien->zone)?->libelle($langue) }}
            </div>

            <div class="prop-meta">
              @if ($bien->nombre_chambres)
                <span class="spec-item">{{ trans_choice(':nombre chambre|:nombre chambres', $bien->nombre_chambres, ['nombre' => $bien->nombre_chambres]) }}</span>
              @elseif ($bien->nombre_pieces)
                <span class="spec-item">{{ trans_choice(':nombre pièce|:nombre pièces', $bien->nombre_pieces, ['nombre' => $bien->nombre_pieces]) }}</span>
              @endif

              @if ($bien->nombre_salles_eau)
                <span class="spec-item">{{ trans_choice(":nombre salle d'eau|:nombre salles d'eau", $bien->nombre_salles_eau, ['nombre' => $bien->nombre_salles_eau]) }}</span>
              @endif

              @if ($bien->surface_habitable || $bien->surface_terrain)
                <span class="spec-item">{{ $bien->surface_habitable ?? $bien->surface_terrain }} m²</span>
              @endif
            </div>

            <div class="prop-footer-line">
              <button type="button" class="prop-btn" wire:click="ouvrirBien({{ $bien->id }})" aria-haspopup="dialog">{{ __('Voir la fiche') }}</button>
            </div>
          </div>
        </article>
      @empty
        <p style="grid-column:1/-1;text-align:center;padding:48px 0;">
          {{ __('Aucun bien ne correspond à votre recherche.') }}
        </p>
      @endforelse
    </div>

    @if ($biens->hasPages())
      <div style="margin-top:32px;">{{ $biens->links() }}</div>
    @endif
  </div>
</section>

@if ($bienOuvert)
  <div class="modal-overlay active" role="presentation" wire:click.self="fermerBien">
    <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="bien-modal-titre" tabindex="-1">
      <button type="button" class="modal-close" wire:click="fermerBien" aria-label="{{ __('Fermer') }}">×</button>
      <div class="modal-header-badge">{{ $bienOuvert->statut_juridique ?: __('Fiche du bien') }}</div>
      <h2 class="modal-title" id="bien-modal-titre">{{ $bienOuvert->titre($langue) }}</h2>
      <div class="modal-loc">{{ $bienOuvert->quartier }}@if ($bienOuvert->quartier && $zones->firstWhere('valeur', $bienOuvert->zone)), @endif{{ $zones->firstWhere('valeur', $bienOuvert->zone)?->libelle($langue) }}</div>

      {{-- Galerie photo --}}
      @if ($bienOuvert->photos->isNotEmpty())
        <div class="modal-hero-visual">
          <img src="{{ asset($bienOuvert->photos->first()->fichier) }}" alt="{{ $bienOuvert->titre($langue) }}" style="width:100%;height:100%;object-fit:cover">
        </div>
        @if ($bienOuvert->photos->count() > 1)
          <div style="display:flex;gap:8px;overflow-x:auto;margin-bottom:24px;padding-bottom:4px;">
            @foreach ($bienOuvert->photos as $photo)
              <img src="{{ asset($photo->fichier) }}" alt="{{ $photo->texteAlternatif($langue) }}"
                   loading="lazy" style="width:80px;height:60px;object-fit:cover;border-radius:8px;flex-shrink:0;cursor:pointer;border:2px solid transparent;transition:border-color .2s;"
                   onmouseover="this.style.borderColor='var(--gold-500)'" onmouseout="this.style.borderColor='transparent'"
                   onclick="var m=document.querySelector('.modal-hero-visual img');if(m)m.src=this.src">
            @endforeach
          </div>
        @endif
      @else
        <div class="modal-hero-visual">
          <x-public.illustration-bien :type="$bienOuvert->type" />
        </div>
      @endif

      {{-- Prix --}}
      @if ($bienOuvert->prixFormate())
        <p style="font-size:22px;font-weight:800;color:var(--gold-300);margin-bottom:20px;">{{ $bienOuvert->prixFormate() }}</p>
      @endif

      {{-- Grille de specifications --}}
      <div class="modal-specs-grid">
        @foreach ([
          __('Type') => $types->firstWhere('valeur', $bienOuvert->type)?->libelle($langue),
          __('Surface') => ($bienOuvert->surface_habitable ?? $bienOuvert->surface_terrain) ? (($bienOuvert->surface_habitable ?? $bienOuvert->surface_terrain).' m²') : null,
          __('Pièces') => $bienOuvert->nombre_pieces,
          __('Chambres') => $bienOuvert->nombre_chambres,
          __("Salles d'eau") => $bienOuvert->nombre_salles_eau,
          __('Statut juridique') => $bienOuvert->statut_juridique,
          __('Numéro de titre') => $bienOuvert->numero_titre,
        ] as $intitule => $valeur)
          @if ($valeur)<div class="spec-item"><label>{{ $intitule }}</label><val>{{ $valeur }}</val></div>@endif
        @endforeach
      </div>

      {{-- Description --}}
      <div class="modal-description">
        <h4>{{ __('Description intégrale du bien') }}</h4>
        <p>{{ $bienOuvert->description($langue) ?: $bienOuvert->accroche($langue) }}</p>
      </div>

      {{-- Equipements --}}
      @if ($bienOuvert->equipements($langue))
        <div class="modal-features-list">
          @foreach ($bienOuvert->equipements($langue) as $equipement)<span class="feat-tag">✓ {{ $equipement }}</span>@endforeach
        </div>
      @endif

      {{-- Formulaire de visite integre --}}
      <div class="contact-card" style="margin-top:28px;background:var(--dark-surface);border-color:var(--dark-border);">
        <h3 style="color:var(--dark-text);">{{ __('Demander une visite') }}</h3>
        <p class="sub" style="color:var(--dark-text-muted);">{{ __("Laissez vos coordonnées : un conseiller vous rappelle pour convenir d'un créneau.") }}</p>

        <form id="modalFormulaireVisite" style="margin:0;padding:0;background:transparent;border:none;box-shadow:none;"
              data-bien="{{ $bienOuvert->slug }}" onsubmit="handleModalVisiteSubmit(event)">
          <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
            <label for="modalVisiteSiteWeb">Site web</label>
            <input type="text" id="modalVisiteSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalVisiteNom">{{ __('Nom complet') }} *</label>
              <input type="text" id="modalVisiteNom" name="nom" required maxlength="80" autocomplete="name">
            </div>
            <div class="form-group">
              <label for="modalVisiteTelephone">{{ __('Téléphone') }} *</label>
              <input type="tel" id="modalVisiteTelephone" name="telephone" required maxlength="40" autocomplete="tel">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalVisiteEmail">{{ __('E-mail') }}</label>
              <input type="email" id="modalVisiteEmail" name="email" maxlength="160" autocomplete="email">
            </div>
            <div class="form-group">
              <label for="modalVisiteCreneau">{{ __('Créneau souhaité') }}</label>
              <input type="date" id="modalVisiteCreneau" name="creneau_souhaite" min="{{ now()->toDateString() }}">
            </div>
          </div>

          <div class="form-group">
            <label for="modalVisiteMessage">{{ __('Précisions') }}</label>
            <textarea id="modalVisiteMessage" name="message" rows="3" maxlength="2000"></textarea>
          </div>

          <button type="submit" class="hero-btn-primary">{{ __('Envoyer ma demande') }}</button>
          <p id="modalVisiteConfirmation" style="display:none;margin-top:12px;color:var(--dark-text-muted);">
            {{ __('Votre demande est enregistrée. Un conseiller vous rappelle sous 24 heures ouvrées.') }}
          </p>
        </form>
      </div>
    </div>
  </div>
@endif

</div>
