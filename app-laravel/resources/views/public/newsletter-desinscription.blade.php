@extends('public.layout')

{{--
  Le retrait de la lettre d'information.

  Tous les textes viennent de l'ecran « Abonnés newsletter », qui gouverne la
  lettre : la page est servie par le site, hors des sept pages editables, et
  ses mots se changent la ou l'on gere ce dont elle parle.

  Chacun retombe sur son texte d'origine tant que rien n'est saisi.
--}}
@php($t = fn (string $nom, string $defaut) => $textes?->texteBilingue($nom, app()->getLocale()) ?: $defaut)

@section('titre', $t('meta_titre', __('Se désinscrire de la lettre d’information')))
@section('description', $t('meta_description', __('Retirer votre adresse de la lettre d’information de SCI4K.')))
@section('classe-page', 'page-statique')

@section('contenu')

{{--
  Reprend les classes des pages legales — legal-hero, legal-section,
  legal-block — deja stylees dans les deux themes. Une page publique de plus
  n'avait pas a inventer sa propre mise en forme.

  Le jeton n'est JAMAIS affiche, seulement reporte dans l'action du formulaire :
  une adresse qui vaut retrait n'a pas a s'etaler sur une capture d'ecran.
--}}
<section class="legal-hero">
  <div class="wrap">
    <h1 class="reveal">{{ $t('titre_page', __('Lettre d’information')) }}</h1>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    <div class="legal-block">
      @if (session('desinscrit'))
        {{-- Le meme message, que le jeton ait designe un abonne ou non :
             distinguer les deux cas dirait a qui essaie des jetons lesquels
             correspondent a une adresse inscrite. --}}
        <h2>{{ $t('titre_fait', __('C’est fait')) }}</h2>
        <p>{{ $t('texte_fait', __('Votre adresse ne recevra plus notre lettre d’information. Vous pouvez vous réinscrire à tout moment depuis le site.')) }}</p>
        <p><a href="{{ route('home') }}">{{ $t('libelle_retour', __('Retour à l’accueil')) }}</a></p>
      @else
        <h2>{{ $t('titre_confirmation', __('Confirmer la désinscription')) }}</h2>
        <p>{{ $t('texte_confirmation', __('Vous êtes sur le point de retirer votre adresse de notre lettre d’information. Cela n’efface aucune demande que vous nous auriez adressée par ailleurs.')) }}</p>

        {{-- cta-btn et non submit-btn : le second occupe TOUTE la largeur, ce
             qui a du sens dans un formulaire de contact et aucun sous un
             paragraphe. Les deux viennent de la feuille du site — inventer une
             classe ici aurait produit un bouton sans style. --}}
        <form method="POST" action="{{ route('newsletter.desinscription.retirer', $jeton) }}">
          @csrf
          <button type="submit" class="cta-btn" style="border:none; cursor:pointer; font-family:inherit;">
            {{ $t('libelle_bouton', __('Me désinscrire')) }}
          </button>
        </form>

        <p><a href="{{ route('home') }}">{{ $t('libelle_annuler', __('Annuler et revenir à l’accueil')) }}</a></p>
      @endif
    </div>
  </div>
</section>

@endsection
