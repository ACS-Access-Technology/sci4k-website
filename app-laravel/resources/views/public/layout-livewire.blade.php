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
<title>{{ $titre ?? __('Biens Immobiliers') }} — SCI4K</title>
<meta name="description" content="{{ $description ?? '' }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $titre ?? '' }} — SCI4K">
<meta property="og:description" content="{{ $description ?? '' }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ url('/images/image%20(3).png') }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'fr_FR' }}">
<meta name="twitter:card" content="summary">
{{-- L'adresse canonique ignore les filtres : une meme fiche de catalogue
     filtree de six facons ne doit pas compter pour six pages aux yeux d'un
     moteur de recherche. --}}
<link rel="canonical" href="{{ url()->current() }}">
<script>(function(){try{var t=localStorage.getItem('sci4k-theme')||'light';
var sombre=t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
document.documentElement.setAttribute('data-theme',sombre?'dark':'light');}catch(e){}})();</script>
<script>(function(){try{localStorage.setItem('sci4k-lang', '{{ app()->getLocale() }}');}catch(e){}})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,600;1,700;1,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/images.css') }}">
<link rel="stylesheet" href="{{ asset('assets/style.css') }}">
@include('public.partials.donnees-structurees', ['noeudPage' => $noeudPage ?? null])
</head>
<body class="page-biens">
@include('public.partials.entete')

{{ $slot }}

@include('public.partials.pied')
<script src="{{ asset('assets/main.js') }}" defer></script>
</body>
</html>
