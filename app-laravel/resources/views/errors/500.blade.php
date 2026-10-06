{{--
  La page « erreur serveur », rendue sans la base de donnees.

  Laravel affichait son ecran par defaut, « Server Error », en anglais, sans
  rien du site. Et l'ancien 500.html des maquettes n'est plus servi : il
  repondait 200, une fausse page d'erreur indexable.

  CETTE VUE NE TOUCHE NI A LA BASE, NI AUX REGLAGES. Une erreur 500 arrive
  souvent PARCE QUE la base est injoignable : une page qui la lirait pour
  afficher son menu ou son logo tomberait a son tour, et Laravel reviendrait a
  son ecran d'usine. D'ou, a la difference de la 404 :

  - pas de gabarit public.layout — ses composeurs lisent les reglages ;
  - la langue lue dans l'ADRESSE, et non dans la session ou la base ;
  - des textes figes, passes par __() et lang/en.json, qui ne demandent rien
    d'autre que les fichiers de l'application ;
  - le nom du site pris dans la configuration (APP_NAME), le logo d'origine.

  Elle reprend les classes des pages legales, deja stylees dans les deux
  themes, comme la 404.
--}}
@php($langue = request()->is('en', 'en/*') ? 'en' : 'fr')
@php($accueil = url($langue === 'en' ? '/en' : '/'))
<!DOCTYPE html>
<html lang="{{ $langue }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ __('Erreur du serveur', [], $langue) }} — {{ config('app.name') }}</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="{{ asset('images/image (3).png') }}">
<script @nonce>(function(){try{var t=localStorage.getItem('sci4k-theme')||'light';
var sombre=t==='dark'||(t==='system'&&window.matchMedia('(prefers-color-scheme: dark)').matches);
document.documentElement.setAttribute('data-theme',sombre?'dark':'light');}catch(e){}})();</script>
<link rel="stylesheet" href="{{ \App\Support\Ressource::url('assets/style.css') }}">
</head>
<body class="page-statique">
<header id="siteHeader">
  <div class="wrap nav">
    <a href="{{ $accueil }}" class="logo"><span class="mark"><img src="{{ asset('images/image (3).png') }}" alt=""></span> {{ config('app.name') }}</a>
  </div>
</header>

<section class="legal-hero">
  <div class="wrap">
    <p style="font-weight:700; letter-spacing:.14em;">500</p>
    <h1>{{ __('Le site rencontre un problème', [], $langue) }}</h1>
    <p>{{ __('Cette page n’a pas pu être affichée. Réessayez dans quelques instants : l’incident est peut-être déjà en cours de résolution.', [], $langue) }}</p>
  </div>
</section>

<section class="legal-section">
  <div class="wrap">
    <div class="legal-block">
      <p><a href="{{ $accueil }}">{{ __('Revenir à l’accueil', [], $langue) }}</a></p>
    </div>
  </div>
</section>
</body>
</html>
