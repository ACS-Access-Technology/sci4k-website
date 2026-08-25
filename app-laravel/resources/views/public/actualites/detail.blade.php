@extends('public.layout')

@section('titre', $article->metaTitre($langue))
@section('description', $article->metaDescription($langue))
@section('classe-page', 'page-actualite')

@section('contenu')

<section class="page-banner pb-actualites">
  <div class="wrap">
    <div class="tag reveal">{{ $article->categorie->nom($langue) }}</div>
    {{--
      Le titre de l'article est le h1 de sa page. La page statique mettait
      « Actualités SCI4K » en h1 et le titre en h2 : coherent tant que les douze
      articles vivaient dans un seul document, faux des qu'un article a son
      adresse propre.
    --}}
    <p class="reveal">
      <a class="article-back" href="{{ route('actualites.index') }}">&larr; {{ __('Retour aux actualités') }}</a>
    </p>
  </div>
</section>

<section class="news-detail">
  <div class="wrap">

    <article class="article reveal" id="{{ $article->slug }}">
      @if ($article->image_source)
        <div class="article-cover" style="background-image:url('{{ asset($article->image_source) }}');"></div>
      @endif
      <div class="article-body">
        <div class="article-meta">
          <span class="article-cat">{{ $article->categorie->nom($langue) }}</span>
          <span class="article-date">{{ $article->date_publication->translatedFormat('j F Y') }}</span>
        </div>
        <h1>{{ $article->titre($langue) }}</h1>
        @foreach (preg_split('/\R{2,}/u', trim($article->contenu($langue))) as $paragraphe)
          <p>{{ $paragraphe }}</p>
        @endforeach
      </div>
    </article>

  </div>
</section>

<section class="news-cta">
  <div class="wrap">
    <h2>{{ __("Une question sur l'un de ces sujets ?") }}</h2>
    <p>{{ __("Nos conseillers répondent à vos questions sur le foncier, l'achat, la location et la gestion de votre patrimoine à Abidjan.") }}</p>
    <a href="/contact.html" class="cta-btn">{{ __('Contacter SCI4K') }}</a>
  </div>
</section>

@endsection
