@extends('public.layout')

@section('titre', $banniere?->texteBilingue('meta_titre', $langue) ?: __('Actualités'))
@section('description', $banniere?->texteBilingue('meta_description', $langue) ?: __("Conseils et actualités immobilières à Abidjan : foncier, marché, gestion locative. Les actualités de SCI4K."))
@section('classe-page', 'page-actualites')

@section('contenu')

{{--
  Les libelles du formulaire de recherche et le bouton de l'appel a l'action
  etaient ecrits en dur et traduits par __() : aucun ecran ne les exposait.
  Les CATEGORIES proposees etaient bien modifiables, mais pas les intitules
  au-dessus des champs.

  Ils viennent maintenant de « Pages du site → Actualités », modules
  « Filtres » et « Appel à l'action ». Chacun retombe sur son texte d'origine
  tant que rien n'est saisi.
--}}
@php($tFiltre = fn (string $nom, string $defaut) => $sectionFiltres?->texteBilingue($nom, $langue) ?: $defaut)
@php($tAppel = fn (string $nom, string $defaut) => $cta?->texteBilingue($nom, $langue) ?: $defaut)

<section class="page-banner pb-actualites">
  <div class="wrap">
    <div class="tag reveal">{{ $banniere?->etiquette($langue) ?: __('Actualités & conseils') }}</div>
    <h1 class="reveal">{{ $banniere?->titre($langue) ?: __('Actualités SCI4K') }}</h1>
    <p class="reveal">{{ $banniere?->chapo($langue) ?: __("Foncier, marché, gestion locative : nos conseils d'experts pour réussir vos projets immobiliers à Abidjan.") }}</p>
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
        <label for="newsQ">{{ $tFiltre('libelle_recherche', __('Rechercher')) }}</label>
        <input type="search" id="newsQ" placeholder="{{ $tFiltre('exemple_recherche', __('Titre, mot-clé…')) }}">
      </div>
      <div class="nt-field">
        <label for="newsCat">{{ $tFiltre('libelle_categorie', __('Catégorie')) }}</label>
        <select id="newsCat">
          <option value="all">{{ $tFiltre('choix_toutes_categories', __('Toutes')) }}</option>
          @foreach ($categories as $categorie)
            <option value="{{ $categorie->nom($langue) }}">{{ $categorie->nom($langue) }}</option>
          @endforeach
        </select>
      </div>
      <div class="nt-field">
        <label for="newsFrom">{{ $tFiltre('libelle_du', __('Du')) }}</label>
        <input type="date" id="newsFrom">
      </div>
      <div class="nt-field">
        <label for="newsTo">{{ $tFiltre('libelle_au', __('Au')) }}</label>
        <input type="date" id="newsTo">
      </div>
      <button class="nt-btn" type="button" id="newsSearch">{{ $tFiltre('libelle_bouton', __('Rechercher')) }}</button>
    </form>

    <div class="news-grid reveal-stagger" id="newsGrid">
      @foreach ($articles as $article)
        <a class="news-card reveal"
           href="{{ route('actualites.detail', $article) }}"
           data-cat="{{ $article->categorie->nom($langue) }}"
           data-date="{{ $article->date_publication->format('Y-m-d') }}">
          @if ($url = $article->urlCouverture())
            <div class="news-card-cover" style="background-image:url('{{ $url }}')"></div>
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
        </a>
      @endforeach

      <p class="news-empty" id="newsEmpty" hidden>{{ $tFiltre('aucun_resultat', __('Aucune actualité ne correspond à votre recherche.')) }}</p>
    </div>

    {{--
      La cle est volontairement plus longue qu'un simple mot : Laravel resout
      d'abord une cle sans point contre ses propres fichiers de langue (auth,
      pagination, passwords, validation) et renverrait le tableau du fichier au
      lieu d'une chaine, ce qui fait tomber la page en 500. Voir le ruling P et
      le test ClesDeTraductionTest, qui garde toutes les vues contre ce piege.
    --}}
    @if ($articles->hasPages())
      <nav class="news-pagination" aria-label="{{ $tFiltre('libelle_pagination', __('Pagination des actualités')) }}">
        {{ $articles->links() }}
      </nav>
    @endif

  </div>
</section>

<section class="news-cta">
  <div class="wrap">
    <h2>{{ $cta?->titre($langue) ?: __("Une question sur l'un de ces sujets ?") }}</h2>
    <p>{{ $cta?->chapo($langue) ?: __("Nos conseillers répondent à vos questions sur le foncier, l'achat, la location et la gestion de votre patrimoine à Abidjan.") }}</p>
    <a href="{{ route('contact.index') }}" class="cta-btn">{{ $tAppel('libelle_bouton', __('Contacter SCI4K')) }}</a>
  </div>
</section>

@endsection
