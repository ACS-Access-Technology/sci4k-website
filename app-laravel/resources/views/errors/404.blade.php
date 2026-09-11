@extends('public.layout')

{{--
  La page « introuvable », rendue par le site et non par le framework.

  Elle affichait l'ecran par defaut de Laravel : titre « Not Found », fond
  blanc, aucun menu, aucun lien de retour, et le nom SCI4K nulle part. Le
  visiteur qui suit un vieux lien — le site en redirige plusieurs depuis que
  ses pages sont servies depuis la base — se retrouvait devant un ecran
  technique, sans autre issue que le bouton « precedent ».

  UN 404.html EXISTE POURTANT DANS public/, aux couleurs du site. Il n'etait
  jamais servi et ne pouvait pas l'etre : le serveur ne cherche un fichier
  statique que si aucune route ne repond, or c'est Laravel qui traite l'adresse
  et leve l'exception bien avant d'en arriver la. Le corriger demandait donc
  une vue, pas une copie de fichier.

  Elle reprend les classes des pages legales, deja stylees dans les deux
  themes, plutot que d'en inventer : une page d'erreur est le dernier endroit
  ou l'on veuille du style jamais relu.
--}}

@section('titre', __('Cette page est introuvable'))
@section('description', __('Cette adresse ne correspond à aucune page du site.'))
@section('classe-page', 'page-statique')

{{-- Pas d'indexation : la page n'a pas de contenu propre, et un moteur qui la
     retiendrait la proposerait a la place de celle qui etait cherchee. --}}
@section('robots', 'noindex, nofollow')

@section('contenu')

<section class="legal-hero">
  <div class="wrap">
    <p class="reveal" style="font-weight:700; letter-spacing:.14em;">404</p>
    <h1 class="reveal">{{ __('Cette page est introuvable') }}</h1>
    <p class="reveal">{{ __('L’adresse demandée ne correspond à aucune page. Elle a peut-être changé, ou comporte une faute de frappe.') }}</p>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    <div class="legal-block">
      <p>{{ __('Vous pouvez reprendre par l’une de ces pages :') }}</p>
      <p>
        <a href="{{ url('/') }}">{{ __('Accueil') }}</a><br>
        <a href="{{ route('biens.index') }}">{{ __('Biens immobiliers') }}</a><br>
        <a href="{{ route('actualites.index') }}">{{ __('Actualités') }}</a><br>
        <a href="{{ route('contact.index') }}">{{ __('Contact') }}</a>
      </p>
    </div>
  </div>
</section>

@endsection
