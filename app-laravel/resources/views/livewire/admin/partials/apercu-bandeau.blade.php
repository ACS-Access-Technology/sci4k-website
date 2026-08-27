{{--
  Rendu approximatif du bandeau tel qu'il apparaitra sur l'accueil.

  Approximatif et non fidele : le vrai bandeau defile, celui-ci est fige. Le
  dire evite de laisser croire que l'animation est cassee.
--}}
<div @class([
    'overflow-hidden rounded-lg px-4 py-3',
    'bg-zinc-900 text-zinc-100' => $fond === 'sombre',
    'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100' => $fond !== 'sombre',
])>
    @if ($noms->isEmpty())
        <p class="text-sm opacity-70">{{ __('Aucune commune affichée pour le moment.') }}</p>
    @else
        <p @class([
            'truncate text-sm tracking-wide',
            'uppercase' => $casse === 'majuscules',
        ])>
            {{-- La liste est doublee, comme sur le site : le bandeau boucle. --}}
            @foreach ($noms->concat($noms) as $nom)
                <span>{{ $nom }}</span>
                <span class="opacity-60">{{ $separateur }}</span>
            @endforeach
        </p>
    @endif
</div>

<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
    {{ __('Rendu approximatif : sur le site, la liste défile en boucle. Elle est répétée deux fois pour boucler sans coupure.') }}
</p>
