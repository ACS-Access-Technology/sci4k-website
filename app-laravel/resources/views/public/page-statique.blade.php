@extends('public.layout')

@section('titre', $page->titre($langue))
@section('description', Str::limit(strip_tags($page->contenu($langue)), 155))
@section('classe-page', 'page-statique')

@section('contenu')

{{--
  Gabarit des pages editables — mentions legales, politique de confidentialite.

  Il employait « prose », classe de Tailwind Typography qui n'est pas installee
  ici : zero occurrence dans la feuille du site. Tout contenu publie sortait
  donc sans mise en forme, titres et paragraphes colles. Les classes
  legal-hero, legal-section et legal-block existent, elles, depuis les pages
  d'origine et sont stylees dans les deux themes ; le gabarit les reprend, de
  sorte qu'une page saisie depuis le backoffice ressemble a celle qu'elle
  remplace.
--}}
<section class="legal-hero">
  <div class="wrap">
    <h1 class="reveal">{{ $page->titre($langue) }}</h1>
    <p class="reveal">{{ __('Dernière mise à jour : :date', ['date' => $page->updated_at?->translatedFormat('F Y') ?: '—']) }}</p>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    {{-- Le contenu est saisi en HTML depuis le backoffice, par un
         administrateur ou un editeur : il est rendu tel quel, comme l'etaient
         les pages d'origine. --}}
    {!! $page->contenu($langue) !!}
  </div>
</section>

@endsection
