{{--
    Gabarit des pages publiques servies par Laravel.

    Reprend fidelement l'en-tete des douze pages statiques du frontoffice. Les
    pages non encore portees restent servies en HTML depuis public/, copiees par
    tools/sync-frontoffice.sh ; frontoffice/ demeure la seule source de verite.
--}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
{{-- Une page sans titre a elle retombe sur « Titre meta par défaut » de
     l'ecran Configuration, et sur le seul nom du site s'il est vide lui aussi.
     Le gabarit affichait « — SCI4K », precede d'un blanc. --}}
@php($titrePage = trim($__env->yieldContent('titre', $titreParDefaut ?? '')))
<title>{{ $titrePage === '' ? $nomDuSite : $titrePage.' — '.$nomDuSite }}</title>
<meta name="description" content="@yield('description', $descriptionSite)">
@unless ($autoriserIndexation)
<meta name="robots" content="noindex, nofollow">
@endunless
@if ($searchConsole)
<meta name="google-site-verification" content="{{ $searchConsole }}">
@endif
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $titrePage === '' ? $nomDuSite : $titrePage.' — '.$nomDuSite }}">
<meta property="og:description" content="@yield('description', $descriptionSite)">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url($logoPublic) }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'fr_FR' }}">
<meta name="twitter:card" content="summary">
<link rel="icon" href="{{ asset($faviconPublic) }}">
<link rel="apple-touch-icon" href="{{ asset($faviconPublic) }}">
<link rel="canonical" href="{{ url()->current() }}">
{{-- Les deux versions d'une meme page se declarent l'une l'autre. Sans ces
     lignes, un moteur voit deux adresses au contenu proche sans aucun moyen de
     savoir qu'il s'agit de la meme page en deux langues : il en choisit une et
     ignore l'autre, ou penalise les deux pour duplication.

     `x-default` designe la version servie a qui ne demande aucune langue
     particuliere — le francais, marche principal de l'agence. --}}
@php($adresseFr = \App\Http\Controllers\LangueController::traduireLeChemin(request()->path(), 'fr'))
<link rel="alternate" hreflang="fr" href="{{ $adresseFr }}">
<link rel="alternate" hreflang="en" href="{{ \App\Http\Controllers\LangueController::traduireLeChemin(request()->path(), 'en') }}">
<link rel="alternate" hreflang="x-default" href="{{ $adresseFr }}">
{{-- Applique le theme sombre avant le premier rendu, pour eviter le flash clair. --}}
{{-- Apparence posee avant le premier rendu, pour eviter le clignotement.
     Trois valeurs possibles depuis que le backoffice et le site partagent la
     meme preference : « system » suit le reglage du poste, et l'ignorer ici
     aurait affiche le clair a un visiteur dont le poste est en sombre. --}}
<script>(function(){try{var t=localStorage.getItem('sci4k-theme')||'light';
var sombre=t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
document.documentElement.setAttribute('data-theme',sombre?'dark':'light');}catch(e){}})();</script>
{{-- La langue servie fait foi : sans cet alignement, main.js rappliquerait au
     chargement celle qu'il a gardee en memoire et re-basculerait les pages
     statiques dans l'autre sens a la premiere visite suivante. --}}
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
<body class="@yield('classe-page')">
@include('public.partials.entete')

@yield('contenu')

@include('public.partials.pied')
@include('public.partials.flottants')
{{-- defer indispensable : sans lui le bouton flottant reste inerte, corrige en aout 2026. --}}
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
