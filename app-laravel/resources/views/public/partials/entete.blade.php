@php($t = fn (string $nom, string $defaut) => $chrome?->texteBilingue($nom, $langueDuSite ?? app()->getLocale()) ?: $defaut)
@php($t = fn (string $nom, string $defaut) => $chrome?->texteBilingue($nom, $langueDuSite ?? app()->getLocale()) ?: $defaut)
{{--
  En-tete des pages publiques.

  Tout le texte passe par __(), sans aucun attribut data-i18n. Ce partiel
  echangeait auparavant son texte cote client, par le dictionnaire de main.js,
  pendant que le corps des pages etait rendu par le serveur selon la session :
  les deux mecanismes ne se parlaient pas, et un visiteur choisissant l'anglais
  obtenait un en-tete anglais sur une page francaise.

  La bascule de langue est donc devenue un lien vers la route serveur. main.js
  garde sa propre bascule pour les pages restees statiques, qui n'ont pas de
  serveur pour les rendre ; son gestionnaire de clic s'execute encore ici et
  aligne localStorage avant que la navigation ne parte, ce qui laisse les deux
  familles de pages d'accord sur la langue choisie.

  Les sept liens etaient ecrits DEUX FOIS — une fois pour l'ecran large, une
  fois pour le menu telephone — et recopies a la main. Ils viennent desormais
  de la base, en un seul endroit : ajouter une page ne demande plus de penser
  aux deux listes, ni de remarquer l'oubli en reduisant la fenetre.

  La classe « active » etait par ailleurs posee EN DUR sur Actualites, si bien
  que ce lien s'affichait actif sur toutes les pages du site. Elle suit
  maintenant l'adresse reellement demandee.
--}}
@php($langue = app()->getLocale())
@php($autreLangue = $langue === 'fr' ? 'en' : 'fr')
{{-- La bascule mene desormais a la MEME PAGE dans l'autre langue, et non a une
     route qui reecrivait la session. La langue vit dans l'adresse : basculer,
     c'est changer d'adresse. Un visiteur qui lisait « Nos services » en anglais
     et bascule arrive sur « Nos services » en francais, et non sur l'accueil. --}}
@php($adresseAutreLangue = \App\Http\Controllers\LangueController::traduireLeChemin(request()->path(), $autreLangue))

<header id="siteHeader">
  <div class="wrap nav">
    <a href="{{ route('home') }}" class="logo"><span class="mark"><img src="{{ asset($logoPublic) }}" alt="{{ $t('aria_logo', $t('aria_logo', __('Logo :site', ['site' => $nomDuSite]))) }}"></span> {{ $nomDuSite }}</a>
    <nav class="links">
      @foreach ($menuPrincipal as $entree)
        <a href="{{ $entree->lien() }}" @class(['active' => $entree->estCourante()])>{{ $entree->libelle($langue) }}</a>
      @endforeach
    </nav>
    <div class="util-switches">
      <button class="theme-toggle" aria-label="{{ $t('aria_theme', $t('aria_theme', __('Basculer mode sombre / clair'))) }}" title="{{ $t('titre_theme', $t('titre_theme', __('Mode sombre / clair'))) }}">
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      </button>
      <a class="lang-toggle" href="{{ $adresseAutreLangue }}"
         aria-label="{{ $t('aria_langue', $t('aria_langue', __('Changer de langue'))) }}" title="Français / English">{{ strtoupper($autreLangue) }}</a>
    </div>
    @if ($ctaHeaderActif)
      <a href="{{ $ctaHeaderUrl }}" class="cta-btn">{{ $ctaHeaderLibelle }}</a>
    @endif
    <button class="burger" id="burgerBtn" aria-label="{{ $t('aria_menu_mobile', $t('aria_menu_mobile', __('Menu Mobile'))) }}">☰</button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    @foreach ($menuPrincipal as $entree)
      <a href="{{ $entree->lien() }}" @class(['active' => $entree->estCourante()])>{{ $entree->libelle($langue) }}</a>
    @endforeach
    <div class="util-switches">
      <button class="theme-toggle" aria-label="{{ $t('aria_theme', $t('aria_theme', __('Basculer mode sombre / clair'))) }}" title="{{ $t('titre_theme', $t('titre_theme', __('Mode sombre / clair'))) }}">
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      </button>
      <a class="lang-toggle" href="{{ $adresseAutreLangue }}"
         aria-label="{{ $t('aria_langue', $t('aria_langue', __('Changer de langue'))) }}" title="Français / English">{{ strtoupper($autreLangue) }}</a>
    </div>
    @if ($ctaHeaderActif)
      <a href="{{ $ctaHeaderUrl }}" class="cta-btn">{{ $ctaHeaderLibelle }}</a>
    @endif
  </div>
</header>
