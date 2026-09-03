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
    <div class="tag reveal">{{ $banniere?->etiquette($langue) ?: __('Questions fréquentes') }}</div>
    <h1 class="reveal">{{ $banniere?->titre($langue) ?: __('Toutes les réponses à vos questions') }}</h1>
    <p class="reveal">{{ $banniere?->chapo($langue) ?: __('Foncier, construction, gestion locative, achat, vente et administration de biens : retrouvez nos réponses aux questions les plus posées par nos clients à Abidjan.') }}</p>
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
  Section « poser une question » : meme balisage que frontoffice/faq.html,
  section .ask-section. Elle pointe vers WhatsApp via assets/main.js
  (handleAskSubmit, deja gate sur body.page-faq) : rien n'est ecrit en base.

  Tous ses textes viennent en revanche du backoffice, libelles du formulaire
  compris : « Pages du site → FAQ → Poser une question » les edite. Ce qui
  suit chaque appel reste la VALEUR DE REPLI — une base vierge affiche donc
  exactement ce qu'affichait la page statique, et les cles __() servent
  encore de version anglaise par defaut.
--}}
@php($texte = fn (string $nom, string $defaut) => $sectionQuestion?->texteBilingue($nom, $langue) ?: $defaut)

<section class="ask-section">
  <div class="wrap">
    <div class="ask-card reveal">
      <h3>{{ $sectionQuestion?->titre($langue) ?: __('Vous ne trouvez pas votre réponse ?') }}</h3>
      <p class="sub">{{ $sectionQuestion?->chapo($langue) ?: __('Posez-nous directement votre question, un conseiller SCI4K vous répondra sous 24 heures ouvrées.') }}</p>
      <div class="ask-alert-success" id="askSuccessAlert">{{ $texte('confirmation', __("✓ Votre question est prête : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour la transmettre à SCI4K.")) }}</div>
      <form id="askForm" onsubmit="handleAskSubmit(event)">
        <div class="ask-form-row">
          <div class="ask-form-group">
            <label for="askName">{{ $texte('libelle_nom', __('Nom complet *')) }}</label>
            <input type="text" id="askName" name="nom" required autocomplete="name" maxlength="80" placeholder="{{ $texte('exemple_nom', 'Ex: Jean Kouassi') }}">
          </div>
          <div class="ask-form-group">
            <label for="askEmail">{{ $texte('libelle_email', __('Adresse Email *')) }}</label>
            <input type="email" id="askEmail" name="email" required autocomplete="email" maxlength="100" placeholder="{{ $texte('exemple_email', 'j.kouassi@email.com') }}">
          </div>
        </div>
        <div class="ask-form-group">
          <label for="askQuestion">{{ $texte('libelle_question', __('Votre question *')) }}</label>
          <textarea id="askQuestion" name="question" rows="4" required maxlength="900" placeholder="{{ $texte('exemple_question', __('Écrivez votre question ici...')) }}"></textarea>
        </div>
        <div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
          <label for="askSiteWeb">Site web</label>
          <input type="text" id="askSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
        </div>
        <button type="submit" class="ask-submit-btn">{{ $texte('libelle_bouton', __('Envoyer ma question →')) }}</button>
      </form>
    </div>
  </div>
</section>

{{--
  Le formulaire ouvre une conversation WhatsApp depuis assets/main.js, qui lit
  window.SCI4K_WHATSAPP. Cette ligne n'existait que sur la page Contact : ici,
  main.js retombait sur le numero ecrit en dur dans le script, et le numero
  regle dans « Configuration » etait ignore par ce seul formulaire.
--}}
<script>window.SCI4K_WHATSAPP = @json($whatsappPublic);</script>

@endsection
