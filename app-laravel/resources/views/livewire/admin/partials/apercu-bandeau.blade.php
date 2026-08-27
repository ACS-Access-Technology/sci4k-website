{{--
  Rendu approximatif du bandeau tel qu'il apparaitra sur l'accueil.

  Approximatif et non fidele : le vrai bandeau DEFILE, celui-ci est fige. Le
  dire evite de laisser croire que l'animation est cassee.

  Pleine largeur et sans troncature. Le premier jet coupait la liste a la
  quatrieme commune — il tenait dans une grille a quatre colonnes prevue pour
  les cartes des chiffres cles, et portait un « truncate » par-dessus. Le
  bandeau reel occupe toute la largeur de l'ecran : un apercu au quart de
  l'espace ne montrait pas ce qu'on regle.
--}}
{{-- Les couleurs de la bande ne portent AUCUNE variante « dark: », et c'est
     voulu : elles montrent le fond choisi pour le SITE, pas le theme de
     l'administration. Le premier jet faisait suivre le theme de l'editeur, si
     bien qu'un fond « clair » s'affichait sombre dans une administration
     sombre — l'apercu contredisait le reglage qu'il illustrait. --}}
<div @class([
    'overflow-hidden rounded-lg',
    'bg-zinc-900 text-zinc-100' => $fond === 'sombre',
    'bg-zinc-100 text-zinc-800' => $fond !== 'sombre',
])>
    @if ($noms->isEmpty())
        <p class="px-4 py-3 text-sm opacity-70">{{ __('Aucune commune affichée pour le moment.') }}</p>
    @else
        {{-- Defilement horizontal plutot que troncature : sur un ecran etroit,
             l'editeur peut faire glisser la bande pour voir la fin de sa
             liste. Couper aurait cache ce qu'il vient de saisir. --}}
        <div class="overflow-x-auto">
            <p @class([
                'flex w-max items-center gap-3 whitespace-nowrap px-4 py-3 text-sm tracking-wide',
                'uppercase' => $casse === 'majuscules',
            ])>
                {{-- La liste est doublee, comme sur le site : le bandeau boucle,
                     et une seule serie laisserait un blanc a chaque tour. --}}
                @foreach ($noms->concat($noms) as $nom)
                    <span>{{ $nom }}</span>
                    <span class="opacity-50">{{ $separateur }}</span>
                @endforeach
            </p>
        </div>
    @endif
</div>

<p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
    {{ __('Rendu approximatif : sur le site, la liste défile en boucle. Elle est répétée deux fois pour boucler sans coupure.') }}
</p>
