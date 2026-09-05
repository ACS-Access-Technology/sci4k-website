{{--
    Gabarit des pages publiques rendues par un composant Livewire.

    Le gabarit historique emploie @yield, qu'un composant pleine page ne sait
    pas remplir : il attend un $slot. Les deux partagent en revanche les MEMES
    partiels — en-tete, pied, donnees structurees — si bien qu'une correction
    apportee a l'un profite a l'autre. Seule l'enveloppe differe.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
{{-- Une page sans titre a elle prend « Titre meta par défaut » de l'ecran
     Configuration, et retombe sur le libelle d'origine s'il est vide. C'est
     ici que le reglage se voit : le catalogue des biens est la seule page
     publique qui n'annonce pas son propre titre. --}}
@php($titrePage = $titre ?? (trim($titreParDefaut ?? '') ?: __('Biens Immobiliers')))
<title>{{ $titrePage }} — {{ $nomDuSite }}</title>
<meta name="description" content="{{ $description ?? $descriptionSite }}">
@unless ($autoriserIndexation)
<meta name="robots" content="noindex, nofollow">
@endunless
@if ($searchConsole)
<meta name="google-site-verification" content="{{ $searchConsole }}">
@endif
<meta property="og:type" content="website">
{{-- Le titre social suit celui de l'onglet : il affichait « — SCI4K »,
     precede d'un blanc, sur toute page rendue sans titre. --}}
<meta property="og:title" content="{{ $titrePage.' — '.$nomDuSite }}">
<meta property="og:description" content="{{ $description ?? $descriptionSite }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url($logoPublic) }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'fr_FR' }}">
<meta name="twitter:card" content="summary">
<link rel="icon" href="{{ asset($faviconPublic) }}">
<link rel="apple-touch-icon" href="{{ asset($faviconPublic) }}">
{{-- L'adresse canonique ignore les filtres : une meme fiche de catalogue
     filtree de six facons ne doit pas compter pour six pages aux yeux d'un
     moteur de recherche. --}}
<link rel="canonical" href="{{ url()->current() }}">
{{-- Voir public/layout.blade.php : les deux versions d'une meme page se
     declarent l'une l'autre, sans quoi un moteur en choisit une et ignore
     l'autre. --}}
@php($adresseFr = \App\Http\Controllers\LangueController::traduireLeChemin(request()->path(), 'fr'))
<link rel="alternate" hreflang="fr" href="{{ $adresseFr }}">
<link rel="alternate" hreflang="en" href="{{ \App\Http\Controllers\LangueController::traduireLeChemin(request()->path(), 'en') }}">
<link rel="alternate" hreflang="x-default" href="{{ $adresseFr }}">
<script>(function(){try{var t=localStorage.getItem('sci4k-theme')||'light';
var sombre=t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
document.documentElement.setAttribute('data-theme',sombre?'dark':'light');}catch(e){}})();</script>
<script>(function(){try{localStorage.setItem('sci4k-lang', '{{ app()->getLocale() }}');}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,600;1,700;1,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ \App\Support\Ressource::url('assets/images.css') }}">
@if ($variablesImagesDeFond)
<style>
:root {
@foreach ($variablesImagesDeFond as $slugImage => $urlImage)
  --img-{{ $slugImage }}: url('{{ $urlImage }}');
@endforeach
}
</style>
@endif
<link rel="stylesheet" href="{{ \App\Support\Ressource::url('assets/style.css') }}">
@if ($googleAnalytics)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($googleAnalytics) }}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{{ $googleAnalytics }}');
</script>
@endif
@include('public.partials.donnees-structurees', ['noeudPage' => $noeudPage ?? null])
</head>
<body class="page-biens">
@include('public.partials.entete')

{{ $slot }}

@include('public.partials.pied')
@include('public.partials.flottants')
<script src="{{ \App\Support\Ressource::url('assets/main.js') }}" defer></script>
@if ($tawkActif && $tawkIdentifiant)
<script>
var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/{{ $tawkIdentifiant }}';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
@endif
</body>
</html>
