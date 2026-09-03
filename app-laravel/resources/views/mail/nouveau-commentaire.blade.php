@if ($enAttente)
{{ __('Un commentaire a été mis de côté sur :site : il n’est PAS visible sur le site tant qu’il n’a pas été approuvé.', ['site' => $nomDuSite]) }}
{{ __('Motif :') }} {{ $motif }}
@else
{{ __('Un commentaire vient d’être publié sur :site.', ['site' => $nomDuSite]) }}
@endif

{{ __('Article :') }} {{ $article }}
{{ __('De :') }} {{ $auteur }}
{{ __('E-mail :') }} {{ $courriel }}

{{ $corps }}

--
{{ __('Modérer depuis le backoffice :') }}
{{ $lien }}
