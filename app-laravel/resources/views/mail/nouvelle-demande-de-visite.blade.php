{{ __('Une visite vient d’être demandée depuis :site.', ['site' => $nomDuSite]) }}

{{ __('De :') }} {{ $nom }}
{{ __('Téléphone :') }} {{ $telephone }}
@if ($courriel)
{{ __('E-mail :') }} {{ $courriel }}
@endif
@if ($bien)
{{ __('Bien :') }} {{ $bien }}
@endif
@if ($creneau)
{{ __('Créneau souhaité :') }} {{ $creneau }}
@endif
@if ($corps)

{{ $corps }}
@endif

--
{{ __('Répondre depuis le backoffice :') }}
{{ $lien }}
