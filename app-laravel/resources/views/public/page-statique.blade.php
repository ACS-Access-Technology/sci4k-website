@extends('public.layout')

@section('titre', $page->titre($langue))
{{-- contenu() rend des entites (&#039;) ; @section les echapperait une seconde fois. --}}
{{-- La description est tiree du texte lui-meme, SANS ses titres de section :
     elle commencait par « 1. Qui sommes-nous », sauts de ligne compris, dans
     ce que Google affiche sous le lien. --}}
@php($texteSansTitres = preg_replace('#<h[1-6][^>]*>.*?</h[1-6]>#si', ' ', $page->contenu($langue)))
@section('description', Str::limit(Str::squish(html_entity_decode(strip_tags($texteSansTitres), ENT_QUOTES | ENT_HTML5, 'UTF-8')), 155))
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
    <p class="reveal">{{ str_replace(':date', $page->updated_at?->translatedFormat('F Y') ?: '—', $gabarit?->texteBilingue('mention_mise_a_jour', $langue) ?: __('Dernière mise à jour : :date', ['date' => ':date'])) }}</p>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    {{-- Le contenu est saisi en HTML depuis le backoffice, par un
         administrateur. contenu() le passe par HtmlEditorial : seules les
         balises editoriales survivent, sans script ni attribut d'evenement.
         C'est ce filtre, et lui seul, qui rend ce {!! !!} acceptable. --}}
    {!! $page->contenu($langue) !!}
  </div>
</section>

@endsection
