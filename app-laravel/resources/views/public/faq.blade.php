@extends('public.layout')

@section('titre', __('Questions fréquentes'))
@section('description', __("Toutes les réponses à vos questions sur le foncier, la construction, la gestion locative, l'achat et la vente immobilière à Abidjan."))
@section('classe-page', 'page-faq')

@section('contenu')

{{--
  Bandeau : balisage et textes copies tels quels de frontoffice/faq.html,
  section .page-banner.pb-faq. Textes fixes, hors base : traduits par __(),
  sans attribut data-i18n.
--}}
<section class="page-banner pb-faq">
  <div class="wrap">
    <div class="tag reveal">{{ __('Questions fréquentes') }}</div>
    <h1 class="reveal">{{ __('Toutes les réponses à vos questions') }}</h1>
    <p class="reveal">{{ __('Foncier, construction, gestion locative, achat, vente et administration de biens : retrouvez nos réponses aux questions les plus posées par nos clients à Abidjan.') }}</p>
  </div>
</section>

{{--
  Groupes de questions : meme structure que l'original (.faq-groups > un div
  par rubrique > .faq-group-title + .faq-list). Le conteneur .faq-groups est
  indispensable : c'est lui qui porte l'espacement entre groupes en CSS
  (display:flex, gap:56px), pas .wrap.

  Le titre de groupe EST le nom de la rubrique (voir le controleur) : aucun texte
  de groupe n'est donc fixe ni traduit par __() ici.
--}}
<section class="faq-section">
  <div class="wrap">
    <div class="faq-groups">
      @foreach ($groupes as $questions)
        @php($rubrique = $questions->first()->rubrique)
        <div>
          <div class="faq-group-title">{{ $rubrique->nom($langue) }}</div>
          <div class="faq-list">
            @foreach ($questions as $question)
              <details class="faq-item reveal" @if ($loop->parent->first && $loop->first) open @endif>
                <summary>
                  <span>{{ $question->question($langue) }}</span>
                  <span class="plus">+</span>
                </summary>
                <div class="faq-answer">{{ $question->reponse($langue) }}</div>
              </details>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{--
  Section « poser une question » : balisage et textes copies tels quels de
  frontoffice/faq.html, section .ask-section. Elle pointe vers WhatsApp via
  assets/main.js (handleAskSubmit, deja gate sur body.page-faq) : rien a
  brancher cote serveur.
--}}
<section class="ask-section">
  <div class="wrap">
    <div class="ask-card reveal">
      <h3>{{ __('Vous ne trouvez pas votre réponse ?') }}</h3>
      <p class="sub">{{ __('Posez-nous directement votre question, un conseiller SCI4K vous répondra sous 24 heures ouvrées.') }}</p>
      <div class="ask-alert-success" id="askSuccessAlert">{{ __("✓ Votre question est prête : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour la transmettre à SCI4K.") }}</div>
      <form id="askForm" onsubmit="handleAskSubmit(event)">
        <div class="ask-form-row">
          <div class="ask-form-group">
            <label for="askName">{{ __('Nom complet *') }}</label>
            <input type="text" id="askName" name="nom" required autocomplete="name" maxlength="80" placeholder="Ex: Jean Kouassi">
          </div>
          <div class="ask-form-group">
            <label for="askEmail">{{ __('Adresse Email *') }}</label>
            <input type="email" id="askEmail" name="email" required autocomplete="email" maxlength="100" placeholder="j.kouassi@email.com">
          </div>
        </div>
        <div class="ask-form-group">
          <label for="askQuestion">{{ __('Votre question *') }}</label>
          <textarea id="askQuestion" name="question" rows="4" required maxlength="900" placeholder="{{ __('Écrivez votre question ici...') }}"></textarea>
        </div>
        <button type="submit" class="ask-submit-btn">{{ __('Envoyer ma question →') }}</button>
      </form>
    </div>
  </div>
</section>

@endsection
