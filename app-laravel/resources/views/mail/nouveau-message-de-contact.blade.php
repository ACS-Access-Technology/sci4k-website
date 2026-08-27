{{ __('Un message vient d’arriver par le formulaire de :site.', ['site' => $nomDuSite]) }}

{{ __('De :') }} {{ $nom }}
@if ($telephone)
{{ __('Téléphone :') }} {{ $telephone }}
@endif
@if ($courriel)
{{ __('E-mail :') }} {{ $courriel }}
@endif
@if ($sujet)
{{ __('Sujet :') }} {{ $sujet }}
@endif

{{ $corps }}

--
{{ __('Répondre depuis le backoffice :') }}
{{ $lien }}
