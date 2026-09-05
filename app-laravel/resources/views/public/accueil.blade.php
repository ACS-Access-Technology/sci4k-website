@extends('public.layout')

{{-- La mise en page ajoute déjà « — SCI4K » : le répéter ici donnait
     « SCI4K — … — SCI4K » dans l'onglet. --}}
@section('titre', $hero?->texteBilingue('meta_titre', $langue) ?: __('Votre propriété, notre priorité'))
@section('description', $hero?->texteBilingue('meta_description', $langue) ?: __("Société Civile Immobilière à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier."))
@section('classe-page', 'page-accueil')

@section('contenu')

{{--
  Les textes de cette page qui ne sont ni un titre ni une accroche etaient
  ecrits en dur et traduits par __() : aucun ecran ne les exposait. Chacun
  vient desormais du module de SON bloc, dans « Pages du site → Accueil ».

  `$tSite` fait exception : « Fermer » et « Annonce » reviennent sur trois
  pages, et sont donc dits une seule fois, sur l'ecran « Menus », avec le reste
  de l'habillage. Trois champs pour un meme mot auraient ete corriges un par
  un — ou pas.
--}}
@php($tSite = fn (string $nom, string $defaut) => $chrome?->texteBilingue($nom, $langue) ?: $defaut)
@php($tHero = fn (string $nom, string $defaut) => $hero?->texteBilingue($nom, $langue) ?: $defaut)
@php($tArticles = fn (string $nom, string $defaut) => $enteteArticles?->texteBilingue($nom, $langue) ?: $defaut)
@php($tAvis = fn (string $nom, string $defaut) => $enteteTemoignages?->texteBilingue($nom, $langue) ?: $defaut)
@php($tPartenaires = fn (string $nom, string $defaut) => $entetePartenaires?->texteBilingue($nom, $langue) ?: $defaut)

{{--
  Page d'accueil, portee depuis frontoffice/index.html.

  Balisage repris tel quel — memes classes, memes attributs data-svc lus par
  main.js — seuls les textes changent de source. Sept sections, sept origines :
  en-tetes par ReglageDeSection, chiffres, services, articles, temoignages,
  partenaires et encart par leurs tables.

  Chaque en-tete se replie sur le texte d'origine s'il manque en base : la page
  reste complete meme avant que l'import ne soit rejoue.
--}}

<section class="hero" id="accueil">
  <div class="hero-media"></div>
  <div class="hero-orb"></div>
  <div class="wrap hero-inner">
    @if ($hero?->etiquette($langue))
      <div class="eyebrow reveal">{{ $hero->etiquette($langue) }}</div>
    @endif

    {{-- Le titre porte deux lignes, la seconde mise en valeur. Elles sont
         separees par un saut de ligne en base plutot que par un <br> : un
         champ que l'administration ecrit ne doit pas pouvoir injecter du
         balisage dans la page. --}}
    {{-- Le repli est fait de deux clés distinctes : une chaîne contenant
         « \n » serait lue telle quelle par le contrôle des traductions, qui
         lit le texte source et non la valeur résolue. --}}
    @php($lignesTitre = $hero?->titreEnLignes($langue) ?: [__('Votre propriété,'), __('notre priorité.')])
    <h1 class="reveal" style="transition-delay:.1s">
      @foreach ($lignesTitre as $ligne)
        @if ($loop->first)
          {{ $ligne }}
        @else
          <br><em>{{ $ligne }}</em>
        @endif
      @endforeach
    </h1>

    @if ($hero?->chapo($langue))
      <p class="lede reveal" style="transition-delay:.2s">{{ $hero->chapo($langue) }}</p>
    @endif

    {{-- Les deux boutons viennent des options du hero, editables depuis
         « Pages du site → Accueil ». Ils etaient ecrits en dur, et le premier
         pointait sur /biens.html — une adresse qui ne repond plus que par une
         redirection 301 depuis le portage du catalogue. --}}
    <div class="hero-actions reveal" style="transition-delay:.3s">
      <a href="{{ $hero?->option('bouton1_cible') ?: route('biens.index') }}" class="hero-btn-primary">
        <span>{{ $hero?->option('bouton1_libelle_'.$langue) ?: __('Rechercher un bien') }}</span>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
      </a>
      <a href="{{ $hero?->option('bouton2_cible') ?: route('presentation.index') }}" class="hero-btn-secondary">
        {{ $hero?->option('bouton2_libelle_'.$langue) ?: __('Découvrir SCI4K') }}
      </a>
    </div>

    {{-- Les compteurs viennent de la base. data-target porte la valeur que
         main.js anime ; le suffixe est pose apres l'animation, sans quoi le
         compteur ferait defiler « 9, 96, 96 % ». --}}
    @if ($chiffres->isNotEmpty())
      <div class="hero-stats reveal" style="transition-delay:.4s">
        @foreach ($chiffres as $chiffre)
          <div class="stat">
            <b class="cnt" data-target="{{ $chiffre->valeur }}">0</b>{{ $chiffre->suffixe }}
            <span>{{ $chiffre->intitule($langue) }}</span>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <a class="scroll-cue" href="#services" aria-label="{{ $tHero('aria_defilement', __('Faire défiler vers le contenu')) }}">
    <span class="scroll-cue-label">{{ $tHero('libelle_defilement', __('Défilez')) }}</span>
    <span class="scroll-cue-mouse"><span class="scroll-cue-wheel"></span></span>
  </a>
</section>

{{-- BANDEAU DES COMMUNES --}}
{{-- Il existait dans index.html et avait disparu au portage du lot 2 : la
     page servie n'en contenait plus trace, la page statique si. Il revient,
     et depuis la base cette fois — l'administration en regle la liste,
     l'ordre, le separateur, la casse et le fond.

     La liste est repetee DEUX FOIS, comme a l'origine : le bandeau defile en
     boucle, et une seule serie laisserait un blanc a chaque tour. --}}
@if ($communesDuBandeau->isNotEmpty())
  <div @class(['marquee-band', 'marquee-band-clair' => $bandeauFond !== 'sombre'])>
    <div class="marquee-track" @class(['marquee-brut' => $bandeauCasse !== 'majuscules'])>
      @foreach ($communesDuBandeau->concat($communesDuBandeau) as $commune)
        <span>{{ $commune->nom }}</span><span><b>{{ $bandeauSeparateur }}</b></span>
      @endforeach
    </div>
  </div>
@endif

{{-- SERVICES --}}
@if ($services->isNotEmpty())
  <section class="services-section" id="services">
    <div class="wrap">
      <div class="section-head">
        @if ($enteteServices?->etiquette($langue))
          <div class="tag tag-home reveal">{{ $enteteServices->etiquette($langue) }}</div>
        @endif
        <h2 class="reveal" style="transition-delay:.1s">{{ $enteteServices?->titre($langue) ?: __('Un accompagnement sur-mesure, à chaque étape') }}</h2>
        @if ($enteteServices?->chapo($langue))
          <p class="reveal" style="transition-delay:.15s">{{ $enteteServices->chapo($langue) }}</p>
        @endif
      </div>

      <div class="services-grid reveal-stagger">
        @foreach ($services as $service)
          {{-- Meme regle que sur /services : le style en ligne n'est pose que
               pour une image televersee, une image du site statique etant deja
               servie par sa regle CSS, laquelle fournit en prime une variante
               allegee sous 800 pixels. --}}
          <button type="button" class="service-tile reveal service-bg-{{ $service->slug }}"
                  data-svc="{{ $service->slug }}"
                  @if ($service->imageTeleversee()) style="background-image:url('{{ $service->urlImage() }}')" @endif
                  aria-haspopup="dialog" aria-controls="svcModal">
            <span class="service-tile-veil"></span>
            <span class="service-tile-inner">
              @if ($service->icone_svg)
                <span class="service-icon-box">{!! $service->icone_svg !!}</span>
              @endif
              <span class="service-tile-title">{{ $service->nom($langue) }}</span>
              <span class="service-tile-tags">
                @foreach ($service->atouts($langue) as $atout)
                  <span class="feature-tag">{{ $atout }}</span>
                @endforeach
              </span>
            </span>
          </button>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- ENCART — Annonce reelle de l'accueil, geree depuis « Annonces & Actions »
     du backoffice (slug accueil.annonce). --}}
@if ($annonce)
  <section class="ad-section" id="encart-accueil" data-slot="accueil-apres-services" data-mode="maison">
    <div class="wrap">
      <div class="ad-slot reveal">
        <span class="ad-label">{{ $tSite('libelle_annonce', __('Annonce')) }}</span>

        <article class="ad-house">
          {{-- Le visuel de l'encart, et non plus la classe service-bg-foncier
               ecrite en dur : celle-ci affichait l'image du service « foncier »
               quoi qu'on televerse, si bien que le champ Image du backoffice
               ne changeait rien. Elle reste le repli quand l'encart n'a pas
               de visuel a lui. --}}
          @if ($url = $annonce->urlImage())
            <div class="ad-house-media" style="background-image:url('{{ $url }}')"></div>
          @else
            <div class="ad-house-media service-bg-foncier"></div>
          @endif
          <div class="ad-house-body">
            @if ($annonce->etiquette($langue))
              <div class="tag tag-home">{{ $annonce->etiquette($langue) }}</div>
            @endif
            <h3>{{ $annonce->titre($langue) }}</h3>
            @if ($annonce->texte($langue))
              <p>{{ $annonce->texte($langue) }}</p>
            @endif
            <a href="{{ $annonce->cible_bouton ?: '/biens' }}" class="cta-btn">
              <span>{{ $annonce->libelleBouton($langue) ?: __('Voir les parcelles') }}</span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
            </a>
          </div>
        </article>
      </div>
    </div>
  </section>
@endif

{{-- BANDEROLE D'APPEL À L'ACTION --}}
@if ($banderole)
  <section class="city-cta-section">
    <div class="wrap">
      <div class="biens-cta-banner reveal">
        <div class="biens-cta-content">
          <h3>{{ $banderole->titre($langue) }}</h3>
          @if ($banderole->texte($langue))
            <p>{{ $banderole->texte($langue) }}</p>
          @endif
        </div>
        <a href="{{ $banderole->cible_bouton ?: '/biens.html' }}" class="cta-btn" style="padding:16px 32px;font-size:15px;">
          <span>{{ $banderole->libelleBouton($langue) ?: __('Consulter les biens') }}</span>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
        </a>
      </div>
    </div>
  </section>
@endif

{{-- DERNIERS ARTICLES --}}
@if ($articles->isNotEmpty())
  <section class="articles-section" id="articles">
    <div class="wrap">
      <div class="section-head">
        @if ($enteteArticles?->etiquette($langue))
          <div class="tag tag-home reveal">{{ $enteteArticles->etiquette($langue) }}</div>
        @endif
        <h2 class="reveal" style="transition-delay:.1s">{{ $enteteArticles?->titre($langue) ?: __('Nos derniers articles') }}</h2>
        @if ($enteteArticles?->chapo($langue))
          <p class="reveal" style="transition-delay:.15s">{{ $enteteArticles->chapo($langue) }}</p>
        @endif
      </div>

      <div class="articles-grid reveal-stagger">
        @foreach ($articles as $article)
          <a href="{{ route('actualites.detail', $article) }}" class="article-card reveal" style="--i:{{ $loop->index }}">
            <div class="article-card-cover-wrap">
              <div class="article-card-cover"
                   @if ($article->urlCouverture()) style="background-image:url('{{ $article->urlCouverture() }}');" @endif></div>
            </div>
            <div class="article-card-body">
              <div class="article-card-cat">{{ $article->categorie?->nom($langue) }}</div>
              <h4>{{ $article->titre($langue) }}</h4>
              <p>{{ $article->resume($langue) }}</p>
              <span class="link-arrow">
                <span>{{ $tArticles('libelle_lien', __("Lire l'article")) }}</span>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
              </span>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- TÉMOIGNAGES --}}
@if ($temoignages->isNotEmpty())
  <section class="testimonials-section">
    <div class="wrap">
      <div class="section-head" style="max-width:640px;">
        @if ($enteteTemoignages?->etiquette($langue))
          <div class="tag reveal">{{ $enteteTemoignages->etiquette($langue) }}</div>
        @endif
        <h2 class="reveal" style="color:#fff;">{{ $enteteTemoignages?->titre($langue) ?: __('Ce que disent nos clients') }}</h2>
        @if ($enteteTemoignages?->chapo($langue))
          <p class="reveal" style="color:rgba(255,255,255,0.75);">{{ $enteteTemoignages->chapo($langue) }}</p>
        @endif
      </div>

      <div class="testimonials-grid reveal-stagger">
        @foreach ($temoignages as $temoignage)
          <div class="testimonial-card reveal" style="--i:{{ $loop->index }}">
            {{-- La note est annoncee aux lecteurs d'ecran : une suite d'etoiles
                 ne se lit pas, elle se voit. --}}
            <div class="testimonial-stars" aria-label="{{ str_replace(':note', (string) $temoignage->note, $tAvis('aria_note', __(':note sur 5', ['note' => ':note']))) }}">{{ str_repeat('★', $temoignage->note) }}</div>
            <p class="testimonial-quote">{{ $temoignage->citation($langue) }}</p>
            <div class="testimonial-author">
              <div class="testimonial-avatar">{{ $temoignage->initiales }}</div>
              <div>
                <div class="testimonial-name">{{ $temoignage->auteur }}</div>
                <div class="testimonial-role">{{ $temoignage->role($langue) }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- PARTENAIRES --}}
@if ($partenaires->isNotEmpty())
  <section class="partners-section">
    <div class="wrap">
      <div class="slider-controls-wrap reveal">
        <div>
          @if ($entetePartenaires?->etiquette($langue))
            <div class="tag tag-home">{{ $entetePartenaires->etiquette($langue) }}</div>
          @endif
          <h2 style="font-size:clamp(28px,3vw,42px);color:var(--texte-titre);margin-top:8px;">{{ $entetePartenaires?->titre($langue) ?: __('Nos Partenaires Privilégiés') }}</h2>
        </div>
        </div>

      <div class="partners-carousel-viewport reveal" id="partnersViewport">
        @foreach ($partenaires as $partenaire)
          {{-- Deux des sept partenaires n'ont pas de site : leur carte est un
               <div> et non un <a>. En faire un lien vide aurait donne un
               element focalisable qui ne mene nulle part. --}}
          @if ($partenaire->aUnSite())
            <a class="partner-logo-card" href="{{ $partenaire->site }}" target="_blank" rel="noopener noreferrer"
               title="{{ str_replace(':nom', $partenaire->nom, $tPartenaires('titre_lien', __('Ouvrir le site de :nom', ['nom' => ':nom']))) }}">
              <img src="{{ asset($partenaire->logo) }}" alt="{{ $partenaire->nom }}" class="partner-logo-img" loading="lazy">
              <span class="p-name">{{ $partenaire->nom }}</span>
            </a>
          @else
            <div class="partner-logo-card">
              <img src="{{ asset($partenaire->logo) }}" alt="{{ $partenaire->nom }}" class="partner-logo-img" loading="lazy">
              <span class="p-name">{{ $partenaire->nom }}</span>
            </div>
          @endif
        @endforeach
      </div>
    </div>
  </section>
@endif

{{-- Modale des services : main.js la remplit au clic depuis #svcPanel-{slug}.
     Meme mecanisme que sur /services, panneaux rendus une fois en boucle. --}}
@if ($services->isNotEmpty())
  <div class="svc-modal" id="svcModal" role="dialog" aria-modal="true" aria-labelledby="svcModalTitle" hidden>
    <div class="svc-modal-backdrop" data-svc-close></div>
    <div class="svc-modal-box">
      <button type="button" class="svc-modal-close" data-svc-close aria-label="{{ $tSite('libelle_fermer', __('Fermer')) }}">&times;</button>
      <div class="svc-modal-body" id="svcModalBody"></div>
    </div>
  </div>

  <div class="svc-source" hidden>
    @foreach ($services as $service)
      <div class="svc-panel" id="svcPanel-{{ $service->slug }}">
        <h3 class="svc-panel-title">{{ $service->nom($langue) }}</h3>
        @foreach (preg_split('/\R{2,}/u', trim($service->description($langue))) ?: [] as $paragraphe)
          @continue(blank($paragraphe))
          <p class="svc-panel-desc">{{ $paragraphe }}</p>
        @endforeach
        <a class="svc-panel-cta" href="/contact.html">{{ $service->libelleBouton($langue) }}</a>
      </div>
    @endforeach
  </div>
@endif

@endsection
