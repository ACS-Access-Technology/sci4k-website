@extends('public.layout')

@section('titre', __('Actualités'))
@section('description', __("Conseils et actualités immobilières à Abidjan : foncier, marché, gestion locative. Les actualités de SCI4K."))
@section('classe-page', 'page-actualites')

@section('contenu')

<section class="page-banner pb-actualites">
  <div class="wrap">
    <div class="tag reveal">{{ __('Actualités & conseils') }}</div>
    <h1 class="reveal">{{ __('Actualités SCI4K') }}</h1>
    <p class="reveal">{{ __("Foncier, marché, gestion locative : nos conseils d'experts pour réussir vos projets immobiliers à Abidjan.") }}</p>
  </div>
</section>

<section class="news-section">
  <div class="wrap">

    {{--
      Le filtrage reste client, assure par main.js sur les attributs data-cat et
      data-date des cartes. Les options de categorie sont rendues depuis la base
      et non ecrites en dur : elles suivent donc la langue du serveur, et une
      categorie ajoutee au backoffice apparait sans retoucher cette vue.
      Elles ne portent pas de data-i18n, sans quoi main.js les retraduirait
      cote client par-dessus le rendu serveur — deux mecanismes pour un meme
      libelle.
    --}}
    <form class="news-toolbar reveal" onsubmit="return false">
      <div class="nt-field nt-search">
        <label for="newsQ">{{ __('Rechercher') }}</label>
        <input type="search" id="newsQ" placeholder="{{ __('Titre, mot-clé…') }}">
      </div>
      <div class="nt-field">
        <label for="newsCat">{{ __('Catégorie') }}</label>
        <select id="newsCat">
          <option value="all">{{ __('Toutes') }}</option>
          @foreach ($categories as $categorie)
            <option value="{{ $categorie->nom($langue) }}">{{ $categorie->nom($langue) }}</option>
          @endforeach
        </select>
      </div>
      <div class="nt-field">
        <label for="newsFrom">{{ __('Du') }}</label>
        <input type="date" id="newsFrom">
      </div>
      <div class="nt-field">
        <label for="newsTo">{{ __('Au') }}</label>
        <input type="date" id="newsTo">
      </div>
      <button class="nt-btn" type="button" id="newsSearch">{{ __('Rechercher') }}</button>
    </form>

    <div class="news-grid reveal-stagger" id="newsGrid">
      @foreach ($articles as $article)
        {{--
          La carte n'est pas encore un lien : la route de detail n'existe qu'a
          la tache suivante. Elle l'enveloppera alors dans un <a>.
        --}}
        <div class="news-card reveal"
             data-cat="{{ $article->categorie->nom($langue) }}"
             data-date="{{ $article->date_publication->format('Y-m-d') }}">
          @if ($article->image_source)
            <div class="news-card-cover" style="background-image:url('{{ asset($article->image_source) }}')"></div>
          @else
            <div class="news-card-cover"></div>
          @endif
          <div class="news-card-body">
            <div class="news-card-meta">
              <span class="news-date">{{ $article->date_publication->translatedFormat('j F Y') }}</span>
            </div>
            <h3>{{ $article->titre($langue) }}</h3>
            <p>{{ $article->resume($langue) }}</p>
          </div>
        </div>
      @endforeach

      <p class="news-empty" id="newsEmpty" hidden>{{ __('Aucune actualité ne correspond à votre recherche.') }}</p>
    </div>

    {{--
      La cle est volontairement plus longue qu'un simple mot : Laravel resout
      d'abord une cle sans point contre ses propres fichiers de langue (auth,
      pagination, passwords, validation) et renverrait le tableau du fichier au
      lieu d'une chaine, ce qui fait tomber la page en 500. Voir le ruling P et
      le test ClesDeTraductionTest, qui garde toutes les vues contre ce piege.
    --}}
    <nav class="news-pagination" aria-label="{{ __('Pagination des actualités') }}">
      <span class="is-disabled" aria-hidden="true">&larr;</span>
      <a class="is-current" href="#" aria-current="page">1</a>
      <span class="is-disabled" aria-hidden="true">&rarr;</span>
    </nav>

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
