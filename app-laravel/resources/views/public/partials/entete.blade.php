<header id="siteHeader">
  <div class="wrap nav">
    <a href="/index.html" class="logo"><span class="mark"><img src="/images/image (3).png" alt="Logo SCI4K"></span> SCI4K</a>
    <nav class="links">
      <a href="/index.html" data-i18n="nav.home">Accueil</a>
      <a href="/presentation.html" data-i18n="nav.about">Présentation</a>
      <a href="/biens.html" data-i18n="nav.properties">Biens Immobiliers</a>
      <a href="/services.html" data-i18n="nav.services">Nos Services</a>
      <a href="{{ route('actualites.index') }}" class="active" data-i18n="nav.actualites">Actualités</a>
      <a href="/faq.html" data-i18n="nav.faq">FAQ</a>
      <a href="/contact.html" data-i18n="nav.contact">Contact</a>
    </nav>
    <div class="util-switches">
      <button class="theme-toggle" aria-label="Basculer mode sombre / clair" title="Mode sombre / clair">
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      </button>
      <button class="lang-toggle" aria-label="Changer de langue" title="Français / English">EN</button>
    </div>
    <a href="/contact.html" class="cta-btn" data-i18n="nav.cta">Nous contacter</a>
    <button class="burger" id="burgerBtn" aria-label="Menu Mobile">☰</button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="/index.html" data-i18n="nav.home">Accueil</a>
    <a href="/presentation.html" data-i18n="nav.about">Présentation</a>
    <a href="/biens.html" data-i18n="nav.properties">Biens Immobiliers</a>
    <a href="/services.html" data-i18n="nav.services">Nos Services</a>
    <a href="{{ route('actualites.index') }}" data-i18n="nav.actualites">Actualités</a>
    <a href="/faq.html" data-i18n="nav.faq">FAQ</a>
    <a href="/contact.html" data-i18n="nav.contact">Contact</a>
    <div class="util-switches">
      <button class="theme-toggle" aria-label="Basculer mode sombre / clair" title="Mode sombre / clair">
        <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
        <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
      </button>
      <button class="lang-toggle" aria-label="Changer de langue" title="Français / English">EN</button>
    </div>
  </div>
</header>
