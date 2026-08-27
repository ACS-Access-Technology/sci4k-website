@props(['type' => 'villa'])

{{--
  Illustration d'un bien sans photo.

  Les six biens repris du site n'ont aucune photo : chacun portait un dessin
  SVG ecrit dans le JavaScript. Ces traces ne sont PAS stockes en base, et
  c'est deliberé — un SVG saisi en administration puis rendu sans echappement
  est une injection, exactement le defaut releve au lot 2 sur les icones de
  valeur. Le dessin est donc choisi ici, d'apres le TYPE du bien, parmi quatre
  formes ecrites en toutes lettres.

  L'agence perd la variation dessin par dessin ; elle gagne un catalogue ou
  aucun bien ne peut injecter de balisage dans la page d'un visiteur. Le jour
  ou de vraies photos arrivent, ces illustrations disparaissent d'elles-memes.
--}}
<svg {{ $attributes->merge(['viewBox' => '0 0 200 140', 'fill' => 'none', 'aria-hidden' => 'true']) }}>
    @switch($type)
        @case('appartement')
            <rect x="30" y="45" width="140" height="80" stroke="currentColor" stroke-width="2.5"/>
            <line x1="30" y1="70" x2="170" y2="70" stroke="currentColor" stroke-width="2"/>
            <line x1="30" y1="95" x2="170" y2="95" stroke="currentColor" stroke-width="2"/>
            <line x1="75" y1="45" x2="75" y2="125" stroke="currentColor" stroke-width="2"/>
            <line x1="120" y1="45" x2="120" y2="125" stroke="currentColor" stroke-width="2"/>
            @break

        @case('terrain')
            <path d="M25 115 100 30l75 85" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round"/>
            <line x1="25" y1="115" x2="175" y2="115" stroke="currentColor" stroke-width="2.5"/>
            @break

        @case('immeuble')
            <rect x="55" y="25" width="90" height="100" stroke="currentColor" stroke-width="2.5"/>
            <line x1="55" y1="50" x2="145" y2="50" stroke="currentColor" stroke-width="2"/>
            <line x1="55" y1="75" x2="145" y2="75" stroke="currentColor" stroke-width="2"/>
            <line x1="55" y1="100" x2="145" y2="100" stroke="currentColor" stroke-width="2"/>
            <line x1="100" y1="25" x2="100" y2="125" stroke="currentColor" stroke-width="2"/>
            @break

        @default
            {{-- Villa, et repli pour tout type inconnu : une maison se lit
                 mieux qu'un carre vide. --}}
            <rect x="40" y="55" width="120" height="70" stroke="currentColor" stroke-width="2.5"/>
            <path d="M40 55 100 25l60 30" stroke="currentColor" stroke-width="2.5"/>
            <rect x="90" y="90" width="20" height="35" stroke="currentColor" stroke-width="2"/>
    @endswitch
</svg>
