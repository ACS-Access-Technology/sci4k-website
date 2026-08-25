@extends('public.layout')

@section('titre', __('Nos services'))
@section('description', __('Foncier, construction, gestion locative, achat, vente, administration de biens : découvrez tous les services immobiliers de SCI4K à Abidjan.'))
@section('classe-page', 'page-services')

@section('contenu')

{{--
  Bandeau : balisage et textes copies tels quels de frontoffice/services.html,
  section .page-banner.pb-services. Ces textes ne viennent pas de la base et
  gardent donc leur traduction par __(), sans attribut data-i18n : voir la
  regle absolue du lot sur la cohabitation de deux mecanismes de traduction.
--}}
<section class="page-banner pb-services">
  <div class="wrap">
    <div class="tag reveal">{{ __('Expertise Immobilière') }}</div>
    <h1 class="reveal">{{ __('Nos Services & Prestations') }}</h1>
    <p class="reveal">{{ __("SCI4K propose une gamme complète de solutions immobilières adaptées aux exigences des particuliers, propriétaires et investisseurs à Abidjan.") }}</p>
  </div>
</section>

{{--
  Les six tuiles : meme balisage que l'original (bouton, icone, titre, tags),
  mais une seule fois, en boucle sur les services visibles et ordonnes en
  base. Le conteneur reprend exactement les deux classes portees par le meme
  <div> dans la page statique (pas de wrapper supplementaire, pas de
  reveal-stagger : la page originale n'en avait pas ici).
--}}
<section class="services-detail">
  <div class="wrap services-grid">
    @foreach ($services as $service)
      <button type="button" class="service-tile reveal service-bg-{{ $service->slug }}"
              id="{{ $service->slug }}" data-svc="{{ $service->slug }}"
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
</section>

{{--
  Section « processus » : balisage et textes copies tels quels de
  frontoffice/services.html, section .process-section. Ses quatre etapes ne
  viennent pas de la base a ce lot ; elles y passeront au plan 2b.
--}}
<section class="process-section">
  <div class="wrap">
    <div class="section-head reveal" style="max-width:640px;">
      <div class="tag" style="color:var(--gold-300); border-color:rgba(211,182,172,0.5);">{{ __('Notre Méthode') }}</div>
      <h2 style="color:#fff;">{{ __('Comment nous travaillons avec vous') }}</h2>
      <p style="color:rgba(255,255,255,0.75);">{{ __('Une démarche claire en 4 étapes pour la concrétisation en toute sérénité de vos projets.') }}</p>
    </div>
    <div class="process-grid reveal-stagger">
      <div class="process-card reveal" style="--i:0">
        <div class="process-num">01</div>
        <h4>{{ __('Écoute & Analyse') }}</h4>
        <p>{{ __('Prise de contact approfondie pour comprendre vos objectifs, vos critères et votre budget.') }}</p>
      </div>
      <div class="process-card reveal" style="--i:1">
        <div class="process-num">02</div>
        <h4>{{ __('Sélection & Audit') }}</h4>
        <p>{{ __('Propositions ciblées et vérification complète des volets juridiques, techniques et fonciers.') }}</p>
      </div>
      <div class="process-card reveal" style="--i:2">
        <div class="process-num">03</div>
        <h4>{{ __('Négociation & Acte') }}</h4>
        <p>{{ __('Sécurisation des transactions avec notre réseau de notaires et partenaires agréés.') }}</p>
      </div>
      <div class="process-card reveal" style="--i:3">
        <div class="process-num">04</div>
        <h4>{{ __('Suivi Continu') }}</h4>
        <p>{{ __('Un accompagnement permanent même après la signature ou la livraison du bien.') }}</p>
      </div>
    </div>
  </div>
</section>

{{--
  Modale de detail : meme structure que l'original (#svcModal vide, rempli au
  clic par assets/main.js depuis #svcPanel-{slug}). Les panneaux sont rendus
  cote serveur depuis la base, avec les memes classes CSS que l'original
  (svc-panel-title, svc-panel-desc, svc-args…) pour que la modale continue de
  s'afficher et de s'ouvrir exactement comme avant.

  L'accroche (nouveau champ, absent de la page statique) est affichee en
  premier paragraphe du corps, avec la meme classe que la description : aucun
  emplacement dedie n'existait pour elle dans le gabarit original.

  Chaque atout n'a plus qu'un intitule court (svc-arg-tag) : la page statique
  associait aussi un paragraphe d'argumentaire par atout, mais aucune colonne
  ne le stocke en base a ce lot.
--}}
<div class="svc-modal" id="svcModal" role="dialog" aria-modal="true" aria-labelledby="svcModalTitle" hidden>
  <div class="svc-modal-backdrop" data-svc-close></div>
  <div class="svc-modal-box">
    <button type="button" class="svc-modal-close" data-svc-close aria-label="{{ __('Fermer') }}">&times;</button>
    <div class="svc-modal-body" id="svcModalBody"></div>
  </div>
</div>
<div class="svc-source" hidden>
  @foreach ($services as $service)
    <div class="svc-panel" id="svcPanel-{{ $service->slug }}">
      <h3 class="svc-panel-title">{{ $service->nom($langue) }}</h3>
      <p class="svc-panel-desc">{{ $service->accroche($langue) }}</p>
      @foreach (preg_split('/\R{2,}/u', trim($service->description($langue))) as $paragraphe)
        <p class="svc-panel-desc">{{ $paragraphe }}</p>
      @endforeach
      @if ($service->atouts($langue))
        <ul class="svc-args">
          @foreach ($service->atouts($langue) as $atout)
            <li class="svc-arg"><span class="svc-arg-tag">{{ $atout }}</span></li>
          @endforeach
        </ul>
      @endif
      <a class="svc-panel-cta" href="/contact.html">{{ $service->libelleBouton($langue) }}</a>
    </div>
  @endforeach
</div>

@endsection
