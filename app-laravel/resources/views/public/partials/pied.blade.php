<footer>
 <div class="wrap">
  <div class="footer-top">
    <div>
      <div class="foot-logo"><img src="/images/image (3).png" alt="Logo SCI4K" style="height:36px;width:auto;" loading="lazy"> SCI4K</div>
      <p data-i18n="footer.about">Société Civile Immobilière basée à Abidjan — Cocody, Cité des Arts. Achat, vente, location, construction et gestion de patrimoine immobilier.</p>
      <div class="newsletter"><input type="email" aria-label="Votre adresse email" placeholder="Votre adresse email" data-i18n-ph="footer.newsletterPh"><button type="button" class="newsletter-btn" aria-label="S'inscrire à la newsletter" data-i18n-aria="footer.newsletterBtn"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg></button></div>
    </div>
    <div>
      <h5 data-i18n="footer.navTitle">Navigation</h5>
      <a href="/index.html" data-i18n="nav.home">Accueil</a>
      <a href="/presentation.html" data-i18n="nav.about">Présentation</a>
      <a href="/biens.html" data-i18n="nav.properties">Biens immobiliers</a>
      <a href="/services.html" data-i18n="nav.services">Nos Services</a>
      <a href="{{ route('actualites.index') }}" data-i18n="nav.actualites">Actualités</a>
      <a href="/faq.html" data-i18n="nav.faq">FAQ</a>
      <a href="/contact.html" data-i18n="nav.contact">Contact</a>
    </div>
    <div>
      <h5 data-i18n="footer.servicesTitle">Nos Services</h5>
      {{--
        Liste tiree de la base, et non plus des six liens ecrits en dur :
        depuis que l'administration cree et supprime des services, une liste
        figee annoncerait des services disparus et tairait les nouveaux.

        L'ancre pointe vers /services (la route Blade) et non /services.html,
        qui ne fait plus que rediriger : un lien du pied de page ne devrait
        pas couter une redirection a chaque clic.

        Pas d'attribut data-i18n ici : la cle svc.*.name n'existe que pour les
        six services d'origine, et le dictionnaire de main.js ecraserait le
        nom d'un service cree par l'administration. Le nom vient donc de la
        base, dans la langue rendue par le serveur.
      --}}
      @foreach ($servicesDuPied as $serviceDuPied)
        <a href="{{ route('services.index') }}#{{ $serviceDuPied->slug }}">{{ $serviceDuPied->nom(app()->getLocale()) }}</a>
      @endforeach
    </div>
    <div>
      <h5 data-i18n="footer.contactTitle">Nous contacter</h5>
      <p data-i18n-html="footer.address">Cocody, Cité des Arts<br>Résidence Paon, 3ème étage<br>Abidjan, Côte d'Ivoire</p>
      <p><strong data-i18n="footer.telLabel">Tél:</strong> +225 07 06 16 50 29</p>
      <p><strong data-i18n="footer.emailLabel">Email:</strong> contact@sci4k.com</p>
    </div>
  </div>
  <div class="bottom-bar">
    <span data-i18n="footer.copyright">© 2026 SCI4K — Tous droits réservés.</span>
    <span class="legal-links"><a href="/mentions-legales.html" data-i18n="footer.legal">Mentions légales</a> · <a href="/politique-confidentialite.html" data-i18n="footer.privacy">Politique de confidentialité</a></span>
    <span data-i18n="footer.tagline">Société Civile Immobilière — Abidjan, Côte d'Ivoire</span>
  </div>
 </div>
</footer>
