@extends('public.layout')

@section('titre', $article->metaTitre($langue))
@section('description', $article->metaDescription($langue))
@section('classe-page', 'page-actualite')

@section('contenu')

{{--
  Le lien de retour, les boutons de partage et tout le bloc de commentaires
  etaient ecrits en dur et traduits par __() : aucun ecran ne les exposait.
  L'ecran des commentaires pilotait leur moderation, mais pas un seul des mots
  que le LECTEUR voit autour d'eux.

  Ils viennent maintenant de « Pages du site → Actualités », modules
  « Articles », « Commentaires » et « Appel à l'action ».

  « Site web » n'y figure pas : c'est l'etiquette du champ piege, place hors de
  l'ecran et jamais montre. Le rendre modifiable aurait ajoute un champ dont
  personne ne peut voir l'effet.
--}}
@php($tArticle = fn (string $nom, string $defaut) => $sectionArticle?->texteBilingue($nom, $langue) ?: $defaut)
@php($tCommentaire = fn (string $nom, string $defaut) => $sectionCommentaires?->texteBilingue($nom, $langue) ?: $defaut)
@php($tAppel = fn (string $nom, string $defaut) => $cta?->texteBilingue($nom, $langue) ?: $defaut)

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
      <a class="article-back" href="{{ route('actualites.index') }}">&larr; {{ $tArticle('lien_retour', __('Retour aux actualités')) }}</a>
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
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ $tArticle('partage_facebook', __('Facebook')) }}</a>
            <a href="https://wa.me/?text={{ urlencode($article->titre($langue).' '.$urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ $tArticle('partage_whatsapp', __('WhatsApp')) }}</a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($urlArticle) }}" target="_blank" rel="noopener noreferrer">{{ $tArticle('partage_linkedin', __('LinkedIn')) }}</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($urlArticle) }}&text={{ urlencode($article->titre($langue)) }}" target="_blank" rel="noopener noreferrer">{{ $tArticle('partage_x', __('X/Twitter')) }}</a>
            <button type="button" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $urlArticle }}')">{{ $tArticle('partage_lien', __('Copier le lien')) }}</button>
          </div>
        @endif
      </div>
    </article>

  </div>
</section>

{{--
  COMMENTAIRES

  Le formulaire est un formulaire HTML ordinaire, pas un appel JavaScript : la
  page est servie par Laravel, elle porte donc une session et un jeton CSRF.
  Les trois autres ecritures publiques vivent dans des pages statiques et ont
  du s'en passer ; celle-ci n'a aucune raison de renoncer a cette protection.

  Un commentaire parait tout de suite, sauf si le filtre l'a mis de cote. Le
  message de retour le dit : un commentaire qui ne s'affiche pas sans
  explication passe pour une panne.
--}}
<section class="comments-section" id="commentaires">
  <div class="wrap">
    {{-- Les reponses comptent : elles sont affichees comme des commentaires,
         et n'en pas tenir compte annonçait « 2 commentaires » au-dessus de
         cinq messages. --}}
    @php($nombreDeCommentaires = $commentaires->count() + $commentaires->sum(fn ($c) => $c->reponses->count()))
    <h2 class="comments-title">
      {{ trans_choice(':nombre commentaire|:nombre commentaires', $nombreDeCommentaires, ['nombre' => $nombreDeCommentaires]) }}
    </h2>

    @if (session('commentaire'))
      <p class="comment-alert" role="status">{{ session('commentaire') }}</p>
    @endif

    @forelse ($commentaires as $commentaire)
      <article class="comment">
        <div class="comment-head">
          <span class="comment-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($commentaire->auteur, 0, 1)) }}</span>
          <div>
            <span class="comment-author">{{ $commentaire->auteur }}</span>
            <span class="comment-date">{{ $commentaire->depuis() }}</span>
          </div>
        </div>
        <p class="comment-body">{{ $commentaire->message }}</p>

        @foreach ($commentaire->reponses as $reponse)
          <article class="comment comment-reply">
            <div class="comment-head">
              <span class="comment-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($reponse->auteur, 0, 1)) }}</span>
              <div>
                <span class="comment-author">{{ $reponse->auteur }}</span>
                <span class="comment-date">{{ $reponse->depuis() }}</span>
              </div>
            </div>
            <p class="comment-body">{{ $reponse->message }}</p>
          </article>
        @endforeach

        @if ($article->commentaires_ouverts)
          {{-- Repondre ouvre le meme formulaire, en fixant le parent : un
               second formulaire par commentaire aurait alourdi la page pour
               un geste rare. --}}
          <button type="button" class="comment-reply-btn"
                  data-repondre-a="{{ $commentaire->id }}"
                  data-repondre-nom="{{ $commentaire->auteur }}">{{ $tCommentaire('libelle_repondre', __('Répondre')) }}</button>
        @endif
      </article>
    @empty
      <p class="comments-empty">{{ $tCommentaire('aucun_commentaire', __('Aucun commentaire pour le moment. Soyez le premier à réagir.')) }}</p>
    @endforelse

    @if ($article->commentaires_ouverts)
      <form class="comment-form" method="POST" action="{{ route('commentaires.depot', $article) }}">
        @csrf

        <h3 id="comment-form-title">{{ $tCommentaire('titre_formulaire', __('Laisser un commentaire')) }}</h3>

        {{-- Cible de la reponse. Vide par defaut ; le bouton « Répondre » la
             remplit, et « Annuler » la vide. --}}
        <input type="hidden" name="parent_id" id="commentParent" value="">

        <p class="comment-replying" id="commentReplying" hidden>
          <span></span>
          <button type="button" id="commentCancelReply">{{ $tCommentaire('libelle_annuler_reponse', __('Annuler la réponse')) }}</button>
        </p>

        {{-- Champ piege : invisible et hors du parcours au clavier, un humain
             ne le remplit jamais. Un robot remplit tout ce qu'il trouve, et le
             serveur refuse alors l'envoi. --}}
        <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
          <label for="commentSiteWeb">{{ __('Site web') }}</label>
          <input type="text" id="commentSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
        </div>

        <div class="comment-form-row">
          <div class="comment-form-group">
            <label for="commentAuteur">{{ $tCommentaire('libelle_nom', __('Votre nom *')) }}</label>
            <input type="text" id="commentAuteur" name="auteur" required maxlength="120"
                   autocomplete="name" value="{{ old('auteur') }}">
            @error('auteur') <span class="comment-error">{{ $message }}</span> @enderror
          </div>
          <div class="comment-form-group">
            <label for="commentEmail">{{ $tCommentaire('libelle_email', __('Votre e-mail *')) }}</label>
            <input type="email" id="commentEmail" name="email" required maxlength="160"
                   autocomplete="email" value="{{ old('email') }}">
            <span class="comment-hint">{{ $tCommentaire('aide_email', __('Il ne sera pas affiché.')) }}</span>
            @error('email') <span class="comment-error">{{ $message }}</span> @enderror
          </div>
        </div>

        <div class="comment-form-group">
          <label for="commentMessage">{{ $tCommentaire('libelle_message', __('Votre commentaire *')) }}</label>
          <textarea id="commentMessage" name="message" rows="4" required maxlength="3000">{{ old('message') }}</textarea>
          @error('message') <span class="comment-error">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="cta-btn">{{ $tCommentaire('libelle_bouton', __('Publier mon commentaire')) }}</button>
      </form>

      {{-- « Répondre » ne fait que remplir le champ cache et amener le regard
           sur le formulaire. Sans JavaScript, le formulaire reste utilisable :
           il depose alors un commentaire de premier niveau, ce qui est le cas
           courant. --}}
      <script>
        (function () {
          var parent = document.getElementById('commentParent');
          var bandeau = document.getElementById('commentReplying');
          var libelle = bandeau.querySelector('span');
          {{-- Le modele est calcule AVANT d'entrer dans @json : la directive
               lit ses arguments en comptant les parentheses, et un appel
               imbrique a deux niveaux l'egare — elle coupait l'expression au
               milieu, produisant une vue qui ne compilait plus. --}}
          @php($modeleReponse = str_replace(':nom', '__NOM__', $tCommentaire('en_reponse_a', __('En réponse à :nom', ['nom' => ':nom']))))
          var modele = @json($modeleReponse);

          document.querySelectorAll('[data-repondre-a]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
              parent.value = bouton.dataset.repondreA;
              libelle.textContent = modele.replace('__NOM__', bouton.dataset.repondreNom);
              bandeau.hidden = false;
              document.getElementById('commentMessage').focus();
              document.getElementById('comment-form-title').scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'center'
              });
            });
          });

          document.getElementById('commentCancelReply').addEventListener('click', function () {
            parent.value = '';
            bandeau.hidden = true;
          });
        })();
      </script>
    @else
      <p class="comments-closed">{{ $tCommentaire('commentaires_fermes', __('Les commentaires sont fermés sur cet article.')) }}</p>
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
