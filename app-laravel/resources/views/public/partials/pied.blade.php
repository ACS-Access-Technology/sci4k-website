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
      <div class="foot-logo"><img src="{{ asset($logoPublic) }}" alt="{{ __('Logo :site', ['site' => $nomDuSite]) }}" style="height:36px;width:auto;" loading="lazy"> {{ $nomDuSite }}</div>
      <p>{{ $descriptionCourte }}</p>
      @if ($liensSociaux)
        <div class="social-links">
          @foreach ($liensSociaux as $lienSocial)
            <a href="{{ $lienSocial['url'] }}" target="_blank" rel="noopener noreferrer">{{ $lienSocial['intitule'] }}</a>
          @endforeach
        </div>
      @endif
      <div class="newsletter"><input type="email" aria-label="{{ __('Votre adresse email') }}" placeholder="{{ __('Votre adresse email') }}"><button type="button" class="newsletter-btn" aria-label="{{ __("S'inscrire à la newsletter") }}"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg></button></div>
    </div>
    <div>
      <h5>{{ __('Navigation') }}</h5>
      {{-- Meme raison que pour la barre du haut : ces sept liens etaient
           recopies a la main a cote de ceux de l'en-tete, et les deux listes
           divergeaient des qu'on oubliait l'une des deux. --}}
      @foreach ($menuPiedNavigation as $entree)
        <a href="{{ $entree->lien() }}">{{ $entree->libelle($langueDuSite) }}</a>
      @endforeach
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
      {{-- Les coordonnees viennent de Configuration > Contact. Elles etaient
           ecrites ici, et l'ecran de configuration promettait pourtant de les
           piloter : deux sources, dont une seule etait affichee. Le repli sur
           le texte d'origine couvre la base pas encore renseignee. --}}
      <p>{!! nl2br(e($adressePostale)) !!}</p>
      @if ($telephonePublic)
        <p><strong>{{ __('Tél:') }}</strong> {{ $telephonePublic }}</p>
      @endif
      @if ($emailPublic)
        <p><strong>{{ __('Email:') }}</strong> {{ $emailPublic }}</p>
      @endif
    </div>
  </div>
  <div class="bottom-bar">
    <span>{{ str_replace(':annee', (string) now()->year, $copyrightPublic) }}</span>
    <span class="legal-links">@foreach ($menuPiedLegal as $entree)<a href="{{ $entree->lien() }}">{{ $entree->libelle($langueDuSite) }}</a>@if (! $loop->last) · @endif @endforeach</span>
    <span>{{ $sousTitrePied }}</span>
  </div>
 </div>
</footer>
