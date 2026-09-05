@extends('public.layout')

@section('titre', __('Site en cours de maintenance'))
@section('description', __('Le site est momentanément indisponible. Nous revenons très vite.'))
@section('classe-page', 'page-statique')

@section('contenu')

{{--
  La page d'attente, servie avec un code 503.

  Reprend les classes des pages legales — legal-hero, legal-section,
  legal-block — deja stylees dans les deux themes. Les coordonnees restent
  affichees : quelqu'un qui tombe sur cette page pendant des travaux doit
  pouvoir joindre l'agence autrement.
--}}
<section class="legal-hero">
  <div class="wrap">
    <h1 class="reveal">{{ __('Nous revenons très vite') }}</h1>
    <p class="reveal">{{ __('Le site est momentanément en cours de maintenance.') }}</p>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    <div class="legal-block">
      <p>{{ __('Nos équipes restent joignables pendant l’opération :') }}</p>
      <p>
        <a href="tel:{{ preg_replace('/\s+/', '', $telephonePublic ?? '') }}">{{ $telephonePublic }}</a><br>
        <a href="mailto:{{ $emailPublic }}">{{ $emailPublic }}</a>
      </p>
    </div>
  </div>
</section>

@endsection
