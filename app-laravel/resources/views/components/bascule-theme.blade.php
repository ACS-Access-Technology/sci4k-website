{{--
  Bascule clair / sombre / système du backoffice.

  Le mecanisme existait deja — un magasin Alpine « appearance », un script joue
  avant le premier rendu, et une ecoute du reglage systeme —, mais il n'etait
  pilotable que depuis /settings/appearance, un ecran sans lien depuis aucune
  page. Personne ne pouvait le trouver. Ce composant lui donne une place dans
  la barre laterale, visible sur tous les ecrans.

  Trois etats et non deux : « systeme » est la valeur par defaut, et suivre le
  reglage du poste est un choix a part entiere, pas l'absence de choix. Un
  bouton unique qui alternerait clair et sombre l'aurait supprime en silence.
--}}
@php($options = [
    'light' => ['intitule' => __('Clair'), 'icone' => 'soleil'],
    'dark' => ['intitule' => __('Sombre'), 'icone' => 'lune'],
    'system' => ['intitule' => __('Système'), 'icone' => 'ecran'],
])

<div x-data class="inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700"
     role="group" aria-label="{{ __('Apparence') }}">
    @foreach ($options as $valeur => $option)
        <button
            type="button"
            x-on:click="$store.appearance.set('{{ $valeur }}')"
            :aria-pressed="$store.appearance.value === '{{ $valeur }}' ? 'true' : 'false'"
            :class="$store.appearance.value === '{{ $valeur }}'
                ? 'bg-zinc-200/70 text-zinc-900 dark:bg-white/10 dark:text-white'
                : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-white'"
            class="cursor-pointer rounded-md p-1.5"
            title="{{ $option['intitule'] }}"
        >
            @switch($option['icone'])
                @case('soleil')
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    @break
                @case('lune')
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8Z"/></svg>
                    @break
                @default
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            @endswitch

            <span class="sr-only">{{ $option['intitule'] }}</span>
        </button>
    @endforeach
</div>
