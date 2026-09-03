@props([
    'compte',
    'taille' => 'size-8',
])

{{--
  La vignette d'un compte : sa photo, ou ses initiales a defaut.

  Le repli vit ICI et nulle part ailleurs. Les initiales etaient recopiees dans
  quatre gabarits ; ajouter la photo aurait voulu dire les modifier tous, et en
  oublier un aurait laisse un compte sans visage a un seul endroit.

  L'image porte un alt VIDE : le nom du compte est deja ecrit a cote, et le
  repeter ferait entendre deux fois la meme chose a un lecteur d'ecran.
--}}
@if ($url = $compte?->urlPhoto())
    <img src="{{ $url }}" alt=""
         {{ $attributes->merge(['class' => $taille.' shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge([
        'class' => $taille.' flex shrink-0 items-center justify-center rounded-full '
            .'bg-zinc-900 text-xs font-medium text-white dark:bg-white dark:text-zinc-900',
    ]) }}>
        {{ $compte?->initials() }}
    </span>
@endif
