{{--
  Pied des pages publiques.

  Comme l'en-tete, tout le texte passe par __() et plus aucun attribut
  data-i18n ne subsiste : sur une page Blade, la langue est celle que le
  serveur a rendue, point. Faire cohabiter le dictionnaire client de main.js
  avec le rendu serveur produisait un pied anglais sous une page francaise.

  L'annee du copyright suit l'horloge plutot que d'etre figee a 2026 : la page
  se serait trompee dans quatre mois.
--}}
<footer>
 <div class="wrap">
  <div class="footer-top">
    <div>
      <div class="foot-logo"><img src="/images/image (3).png" alt="{{ __('Logo SCI4K') }}" style="height:36px;width:auto;" loading="lazy"> SCI4K</div>
      <p>{{ __('Société Civile Immobilière basée à Abidjan — Cocody, Cité des Arts. Achat, vente, location, construction et gestion de patrimoine immobilier.') }}</p>
      <div class="newsletter"><input type="email" aria-label="{{ __('Votre adresse email') }}" placeholder="{{ __('Votre adresse email') }}"><button type="button" class="newsletter-btn" aria-label="{{ __("S'inscrire à la newsletter") }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg></button></div>
    </div>
    <div>
      <h5>{{ __('Navigation') }}</h5>
      <a href="/index.html">{{ __('Accueil') }}</a>
      <a href="/presentation.html">{{ __('Présentation') }}</a>
      <a href="/biens.html">{{ __('Biens immobiliers') }}</a>
      <a href="{{ route('services.index') }}">{{ __('Nos Services') }}</a>
      <a href="{{ route('actualites.index') }}">{{ __('Actualités') }}</a>
      <a href="{{ route('faq.index') }}">{{ __('FAQ') }}</a>
      <a href="/contact.html">{{ __('Contact') }}</a>
    </div>
    <div>
      <h5>{{ __('Nos Services') }}</h5>
      {{--
        Liste tiree de la base, et non plus des six liens ecrits en dur :
        depuis que l'administration cree et supprime des services, une liste
        figee annoncerait des services disparus et tairait les nouveaux.

        L'ancre pointe vers /services (la route Blade) et non /services.html,
        qui ne fait plus que rediriger : un lien du pied de page ne devrait
        pas couter une redirection a chaque clic.
      --}}
      @foreach ($servicesDuPied as $serviceDuPied)
        <a href="{{ route('services.index') }}#{{ $serviceDuPied->slug }}">{{ $serviceDuPied->nom(app()->getLocale()) }}</a>
      @endforeach
    </div>
    <div>
      <h5>{{ __('Nous contacter') }}</h5>
      {{-- Trois lignes distinctes plutot qu'une chaine a sauts de ligne : le
           contrôle des traductions lit le texte source, ou « \n » compte pour
           deux caracteres, et ne retrouverait jamais la cle resolue. --}}
      <p>{{ __('Cocody, Cité des Arts') }}<br>{{ __('Résidence Paon, 3ème étage') }}<br>{{ __("Abidjan, Côte d'Ivoire") }}</p>
      <p><strong>{{ __('Tél:') }}</strong> +225 07 06 16 50 29</p>
      <p><strong>{{ __('Email:') }}</strong> contact@sci4k.com</p>
    </div>
  </div>
  <div class="bottom-bar">
    <span>{{ __('© :annee SCI4K — Tous droits réservés.', ['annee' => now()->year]) }}</span>
    <span class="legal-links"><a href="/mentions-legales.html">{{ __('Mentions légales') }}</a> · <a href="/politique-confidentialite.html">{{ __('Politique de confidentialité') }}</a></span>
    <span>{{ __('Société Civile Immobilière — Abidjan, Côte d\'Ivoire') }}</span>
  </div>
 </div>
</footer>
