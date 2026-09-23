{{--
  Catalogue des biens, cote visiteur.

  Reprend fidelement la structure de frontoffice/biens.html — meme bandeau,
  meme carte de recherche, memes pastilles, meme grille — mais le tri se fait
  desormais SUR LE SERVEUR. Le site rendait ses biens d'un bloc puis les
  masquait en JavaScript : tout le catalogue traversait le reseau a chaque
  visite.

  Les libelles de cette page etaient ecrits en dur et traduits par __() :
  aucun ecran ne les exposait. Le vocabulaire des filtres — types, zones,
  tranches — etait bien modifiable depuis les referentiels, mais pas les
  INTITULES au-dessus de chaque liste, ni les mots de la grille, ni ceux du
  formulaire de rendez-vous.

  Quatre accesseurs les lisent maintenant, un par section, et chacun retombe
  sur le texte d'origine tant que rien n'est saisi : une base vierge rend
  exactement ce que cette page rendait avant.
--}}
@php($tSite = fn (string $nom, string $defaut) => $chrome?->texteBilingue($nom, $langue) ?: $defaut)
@php($tFiltre = fn (string $nom, string $defaut) => $filtres?->texteBilingue($nom, $langue) ?: $defaut)
@php($tGrille = fn (string $nom, string $defaut) => $sectionCatalogue?->texteBilingue($nom, $langue) ?: $defaut)
@php($tFiche = fn (string $nom, string $defaut) => $sectionFiche?->texteBilingue($nom, $langue) ?: $defaut)
@php($tVisite = fn (string $nom, string $defaut) => $sectionVisite?->texteBilingue($nom, $langue) ?: $defaut)

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
        <div class="libelle-filtres">{{ $filtres?->titre($langue) ?: __('Filtres multicritères') }}</div>
      </div>

      <div class="field-grid">
        <div class="field">
          <label for="filtreType">{{ $tFiltre('libelle_type', __('Type de bien')) }}</label>
          <select id="filtreType" wire:model.live="type">
            <option value="">{{ $tFiltre('choix_tous_types', __('Tous les types')) }}</option>
            @foreach ($types as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtreZone">{{ $tFiltre('libelle_zone', __('Localité')) }}</label>
          <select id="filtreZone" wire:model.live="zone">
            <option value="">{{ $tFiltre('choix_toutes_zones', __('Toutes les zones')) }}</option>
            @foreach ($zones as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtrePieces">{{ $tFiltre('libelle_pieces', __('Nombre de Pièces')) }}</label>
          <select id="filtrePieces" wire:model.live="pieces">
            <option value="">{{ $tFiltre('choix_toutes_pieces', __('Toutes pièces')) }}</option>
            @foreach ($tranchesPieces as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>

        <div class="field">
          <label for="filtreSurface">{{ $tFiltre('libelle_surface', __('Surface (m²)')) }}</label>
          <select id="filtreSurface" wire:model.live="surface">
            <option value="">{{ $tFiltre('choix_toutes_surfaces', __('Toutes surfaces')) }}</option>
            @foreach ($tranchesSurface as $valeur)
              <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
          </select>
        </div>
      </div>

      {{-- PLUS DE BOUTON « Rechercher le bien ideal » ici.
           Il ne cherchait rien — chaque liste relance la recherche d'elle-meme —
           et appelait reinitialiser() : il VIDAIT les criteres que le visiteur
           venait de poser. Le libelle promettait l'inverse de ce que le clic
           faisait, et c'est le libelle que le visiteur lit.
           L'onglet « Tous », juste en dessous, offre cette remise a zero sous
           un nom qui la decrit. --}}
    </div>
  </div>
</section>

{{-- « opacity-50 » est une classe de Tailwind, absente de la feuille du site
     public : l'attenuation annoncee ne se produisait jamais, et seul
     pointer-events:none s'appliquait. Le visiteur n'avait donc AUCUN signe
     que son clic etait pris en compte pendant l'aller-retour serveur.
     L'opacite passe en style en ligne, qui ne depend d'aucune feuille. --}}
{{-- wire:loading.class, et surtout PAS wire:loading.style : « style » n'est pas
     un modificateur Livewire. Faute de le reconnaitre, Livewire retombait sur
     son comportement par defaut et basculait le DISPLAY de cette section —
     inline-block, puis none, puis retour. Une section en inline-block cesse
     d'occuper la largeur : elle se retracte, et toute la mise en page centree
     glissait vers la gauche avant de revenir, a chaque clic de filtre.
     La classe est definie dans la feuille du site, pas empruntee a Tailwind
     qui n'y est pas charge — c'etait le defaut de la version precedente. --}}
<section class="properties-section" wire:loading.class="en-chargement">
  <div class="wrap">
    <div class="filters-bar">
      <div class="filters" id="pillFilters">
        <button type="button" @class(['pill', 'active' => $type === '' && $offre === '']) wire:click="reinitialiser">{{ $tFiltre('onglet_tous', __('Tous')) }}</button>
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

    <div class="catalogue-layout">

    <div class="catalogue-main">
    <div class="prop-grid reveal-stagger">
      @forelse ($biens as $bien)
        {{-- wire:key est indispensable, et il manquait. Sans lui, chaque
             re-rendu du composant — ouvrir une fiche en est un — laisse
             Livewire reconstruire les cartes au lieu de les reutiliser. Les
             images repartent alors de zero : le temps qu'elles reprennent
             leur taille, la grille se retasse et la page bouge sous les yeux
             du visiteur, juste avant que la fiche n'apparaisse. --}}
        <article class="prop-card" wire:key="bien-{{ $bien->id }}" style="--i:{{ $loop->index }}">
          <div class="prop-visual">
            <span @class(['prop-badge', 'vente' => $bien->offre === \App\Models\Bien::VENTE])>
              {{ $offres[$bien->offre] ?? $bien->offre }}
            </span>

            @if ($bien->photos->isNotEmpty())
              <img src="{{ asset($bien->photos->first()->fichier) }}"
                   alt="{{ $bien->photos->first()->texteAlternatif($langue) ?: $bien->titre($langue) }}"
                   loading="lazy" class="visuel-couvrant">
            @else
              {{-- Les six biens repris du site n'ont pas de photo : le dessin
                   tient lieu de visuel, comme aujourd'hui. --}}
              <x-public.illustration-bien :type="$bien->type" />
            @endif

            @if ($bien->estVendu())
              <span class="prop-badge en-bas">{{ $tGrille('pastille_vendu', __('Vendu')) }}</span>
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
              <button type="button" class="prop-btn" wire:click="ouvrirBien({{ $bien->id }})" aria-haspopup="dialog">{{ $tGrille('libelle_fiche', __('Voir la fiche')) }}</button>
            </div>
          </div>
        </article>
      @empty
        <p class="message-aucun-resultat">
          {{ $tGrille('aucun_resultat', __('Aucun bien ne correspond à votre recherche.')) }}
        </p>
      @endforelse
    </div>

    @if ($biens->hasPages())
      <div class="pagination-biens">{{ $biens->links() }}</div>
    @endif
    </div>{{-- .catalogue-main --}}

    {{-- Panneau des equipements — SECOND filtre, multicritere.
         Il se cumule aux cinq listes du haut de page au lieu de les remplacer :
         cocher « Piscine » restreint ce que « Villa » + « Cocody » ont deja
         laisse passer.
         Les cases viennent du referentiel, comme les autres filtres : ajouter
         un equipement depuis le backoffice fait apparaitre sa case ici, sans
         toucher a cette vue.
         Place APRES la grille dans le balisage, et non avant : ce sont les
         biens qui comptent, et un lecteur d'ecran comme une tabulation les
         atteignent donc en premier. La colonne de droite vient de la grille. --}}
    @php($titrePanneau = $tFiltre('titre_equipements', __('Équipements')))
    <aside @class(['equip-panel', 'est-ouvert' => $panneauDeploye]) aria-label="{{ $titrePanneau }}">
      {{-- Deux en-tetes pour une seule raison : sur grand ecran le panneau est
           toujours ouvert et n'a besoin que d'un titre ; sur telephone il se
           replie, et un titre ne se clique pas. Chacun est masque la ou il n'a
           pas de sens, et le nom accessible du panneau est porte par l'aside
           lui-meme pour ne dependre d'aucun des deux. --}}
      <h3 class="equip-titre">{{ $titrePanneau }}</h3>

      {{-- Replie par defaut sur telephone. Deploye, ce panneau occupait tout
           l'ecran AVANT le premier bien, et sa liste piegeait le doigt dans un
           defilement imbrique. Le nombre de cases cochees reste affiche une
           fois referme : un filtre actif qu'on ne voit pas est un filtre qu'on
           ne pense pas a retirer. --}}
      <button type="button" class="equip-toggle" wire:click="basculerLePanneau"
              aria-expanded="{{ $panneauDeploye ? 'true' : 'false' }}" aria-controls="equip-list">
        <span>{{ $titrePanneau }}</span>
        @if ($equipementsChoisis)
          <span class="equip-compte">{{ count($equipementsChoisis) }}</span>
        @endif
        <svg class="equip-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 9l6 6 6-6"/>
        </svg>
      </button>

      <div class="equip-list" id="equip-list">
        @forelse ($equipementsProposes as $equipement)
          <label @class(['equip-option', 'is-active' => in_array($equipement->id, $equipementsChoisis, true)])>
            <input type="checkbox" value="{{ $equipement->id }}" wire:model.live="equipements">
            <span>{{ $equipement->libelle($langue) }}</span>
          </label>
        @empty
          <p class="equip-empty">{{ $tFiltre('aucun_equipement', __('Aucun équipement à filtrer pour le moment.')) }}</p>
        @endforelse

        @if ($equipementsChoisis)
          <button type="button" class="equip-reset" wire:click="viderLesEquipements">
            {{ $tFiltre('bouton_vider_equipements', __('Tout décocher')) }}
          </button>
        @endif
      </div>
    </aside>

    </div>{{-- .catalogue-layout --}}
  </div>
</section>

@if ($bienOuvert)
  {{-- Echap ferme la fiche, au meme titre que la croix et le clic sur le fond.
       Verifie en recette : elle ne fermait rien, et un visiteur au clavier
       n'avait aucune sortie.

       « .window » plutot qu'un ecouteur local : rien ne place le focus DANS la
       fenetre a son ouverture — il reste sur le bouton « Voir la fiche »,
       derriere elle. Un ecouteur pose sur la fenetre ne verrait donc jamais la
       touche passer. --}}
  <div class="modal-overlay active" role="presentation"
       wire:click.self="fermerBien"
       wire:keydown.escape.window="fermerBien">
    <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="bien-modal-titre" tabindex="-1">
      <button type="button" class="modal-close" wire:click="fermerBien" aria-label="{{ $tSite('libelle_fermer', __('Fermer')) }}">×</button>
      <div class="modal-header-badge">{{ $bienOuvert->statut_juridique ?: __('Fiche du bien') }}</div>
      <h2 class="modal-title" id="bien-modal-titre">{{ $bienOuvert->titre($langue) }}</h2>
      <div class="modal-loc">{{ $bienOuvert->quartier }}@if ($bienOuvert->quartier && $zones->firstWhere('valeur', $bienOuvert->zone)), @endif{{ $zones->firstWhere('valeur', $bienOuvert->zone)?->libelle($langue) }}</div>

      {{-- Galerie photo --}}
      @if ($bienOuvert->photos->isNotEmpty())
        <div class="modal-hero-visual">
          <img src="{{ asset($bienOuvert->photos->first()->fichier) }}" alt="{{ $bienOuvert->titre($langue) }}" class="visuel-couvrant">
        </div>
        @if ($bienOuvert->photos->count() > 1)
          {{-- Le survol et le clic sont desormais dans main.js et dans la
               feuille de style : cliquer une vignette remplace la photo
               principale, et la bordure s'allume au survol. --}}
          <div class="galerie-vignettes" data-galerie="bien">
            @foreach ($bienOuvert->photos as $photo)
              <img src="{{ asset($photo->fichier) }}" alt="{{ $photo->texteAlternatif($langue) }}"
                   loading="lazy" class="vignette-galerie">
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
        <p class="prix-modale">{{ $bienOuvert->prixFormate() }}</p>
      @endif

      {{-- Grille de specifications.
           Une liste de definitions : chaque caracteristique est un terme et sa
           valeur. Elle s'ecrivait avec <label>, qui annonce un champ de
           formulaire aux lecteurs d'ecran — il n'y en a aucun ici — et avec
           <val>, qui n'existe pas en HTML. --}}
      <dl class="modal-specs-grid">
        @foreach ([
          $tFiche('libelle_type', __('Type')) => $types->firstWhere('valeur', $bienOuvert->type)?->libelle($langue),
          $tFiche('libelle_surface', __('Surface')) => ($bienOuvert->surface_habitable ?? $bienOuvert->surface_terrain) ? (($bienOuvert->surface_habitable ?? $bienOuvert->surface_terrain).' m²') : null,
          $tFiche('libelle_pieces', __('Pièces')) => $bienOuvert->nombre_pieces,
          $tFiche('libelle_chambres', __('Chambres')) => $bienOuvert->nombre_chambres,
          $tFiche('libelle_salles_eau', __("Salles d'eau")) => $bienOuvert->nombre_salles_eau,
          $tFiche('libelle_statut_juridique', __('Statut juridique')) => $bienOuvert->statut_juridique,
          $tFiche('libelle_numero_titre', __('Numéro de titre')) => $bienOuvert->numero_titre,
        ] as $intitule => $valeur)
          @if ($valeur)<div class="spec-item"><dt>{{ $intitule }}</dt><dd>{{ $valeur }}</dd></div>@endif
        @endforeach
      </dl>

      {{-- Description --}}
      <div class="modal-description">
        <h4>{{ $tGrille('titre_description', __('Description intégrale du bien')) }}</h4>
        <p>{{ $bienOuvert->description($langue) ?: $bienOuvert->accroche($langue) }}</p>
      </div>

      {{-- Equipements --}}
      @if ($bienOuvert->equipements->isNotEmpty())
        <div class="modal-features-list">
          @foreach ($bienOuvert->equipements as $equipement)<span class="feat-tag">✓ {{ $equipement->libelle($langue) }}</span>@endforeach
        </div>
      @endif

      {{-- Formulaire de visite integre --}}
      <div class="contact-card carte-visite-modale">
        <h3>{{ $tVisite('titre', __('Demander une visite')) }}</h3>
        <p class="sub">{{ $tVisite('accroche', __("Laissez vos coordonnées : un conseiller vous rappelle pour convenir d'un créneau.")) }}</p>

        <form id="modalFormulaireVisite"
              data-bien="{{ $bienOuvert->slug }}" data-envoi="visite">
          <div aria-hidden="true" class="champ-piege">
            <label for="modalVisiteSiteWeb">Site web</label>
            <input type="text" id="modalVisiteSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalVisiteNom">{{ $tVisite('libelle_nom', __('Nom complet')) }} *</label>
              <input type="text" id="modalVisiteNom" name="nom" required maxlength="80" autocomplete="name">
            </div>
            <div class="form-group">
              <label for="modalVisiteTelephone">{{ $tVisite('libelle_telephone', __('Téléphone')) }} *</label>
              <input type="tel" id="modalVisiteTelephone" name="telephone" required maxlength="40" autocomplete="tel">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="modalVisiteEmail">{{ $tVisite('libelle_email', __('E-mail')) }}</label>
              <input type="email" id="modalVisiteEmail" name="email" maxlength="160" autocomplete="email">
            </div>
            <div class="form-group">
              <label for="modalVisiteCreneau">{{ $tVisite('libelle_creneau', __('Créneau souhaité')) }}</label>
              <input type="date" id="modalVisiteCreneau" name="creneau_souhaite" min="{{ now()->toDateString() }}">
            </div>
          </div>

          <div class="form-group">
            <label for="modalVisiteMessage">{{ $tVisite('libelle_precisions', __('Précisions')) }}</label>
            <textarea id="modalVisiteMessage" name="message" rows="3" maxlength="2000"></textarea>
          </div>

          <button type="submit" class="hero-btn-primary">{{ $tVisite('libelle_bouton', __('Envoyer ma demande')) }}</button>
          <p data-visite-confirmation id="modalVisiteConfirmation" class="message-confirmation">
            {{ $tVisite('confirmation', __('Votre demande est enregistrée. Un conseiller vous rappelle sous 24 heures ouvrées.')) }}
          </p>
        </form>
      </div>
    </div>
  </div>
@endif

</div>
