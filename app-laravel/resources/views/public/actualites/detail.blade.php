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
      @if ($url = $article->urlCouverture())
        <div class="article-cover" style="background-image:url('{{ $url }}');"></div>
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
        @if ($partageActif)
          @php($urlArticle = route('actualites.detail', $article))
          <div class="article-share">
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ __('Facebook') }}</a>
            <a href="https://wa.me/?text={{ urlencode($article->titre($langue).' '.$urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ __('WhatsApp') }}</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ __('LinkedIn') }}</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($urlArticle) }}&text={{ urlencode($article->titre($langue)) }}" target="_blank" rel="noopener noreferrer">{{ __('X/Twitter') }}</a>
            <button type="button" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $urlArticle }}')">{{ __('Copier le lien') }}</button>
          </div>
        @endif
      </div>
    </article>

  </div>
</section>

<section class="news-cta">
  <div class="wrap">
    <h2>{{ $cta?->titre($langue) ?: __("Une question sur l'un de ces sujets ?") }}</h2>
    <p>{{ $cta?->chapo($langue) ?: __("Nos conseillers répondent à vos questions sur le foncier, l'achat, la location et la gestion de votre patrimoine à Abidjan.") }}</p>
    <a href="{{ route('contact.index') }}" class="cta-btn">{{ __('Contacter SCI4K') }}</a>
  </div>
</section>

@endsection
