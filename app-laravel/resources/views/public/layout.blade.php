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
<title>@yield('titre') — SCI4K</title>
<meta name="description" content="@yield('description')">
<meta property="og:type" content="website">
<meta property="og:title" content="@yield('titre') — SCI4K">
<meta property="og:description" content="@yield('description')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/images/image%20(3).png') }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'fr_FR' }}">
<meta name="twitter:card" content="summary">
<link rel="canonical" href="{{ url()->current() }}">
{{-- Applique le theme sombre avant le premier rendu, pour eviter le flash clair. --}}
<script>(function(){try{var t=localStorage.getItem('sci4k-theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');}catch(e){}})();</script>
{{-- La langue servie fait foi : sans cet alignement, main.js rappliquerait au
     chargement celle qu'il a gardee en memoire et re-basculerait les pages
     statiques dans l'autre sens a la premiere visite suivante. --}}
<script>(function(){try{localStorage.setItem('sci4k-lang', '{{ app()->getLocale() }}');}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,600;1,700;1,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/images.css') }}">
<link rel="stylesheet" href="{{ asset('assets/style.css') }}">
@include('public.partials.donnees-structurees', ['noeudPage' => $noeudPage ?? null])
</head>
<body class="@yield('classe-page')">
@include('public.partials.entete')

@yield('contenu')

@include('public.partials.pied')
{{-- defer indispensable : sans lui le bouton flottant reste inerte, corrige en aout 2026. --}}
<script src="{{ asset('assets/main.js') }}" defer></script>
</body>
</html>
