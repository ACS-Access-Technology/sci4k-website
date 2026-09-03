{{-- La liste de résultats se ferme au clic extérieur et à Échap : elle
     recouvre la page, et l'obliger à vider le champ pour la refermer aurait
     coûté un geste inutile. --}}
<div class="relative w-full max-w-md" x-data="{ ouvert: false }"
     x-on:click.outside="ouvert = false" x-on:keydown.escape.window="ouvert = false">

    <label class="relative block">
        <span class="sr-only">{{ __('Rechercher dans le contenu') }}</span>

        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3 text-zinc-400">
            <x-admin.icone nom="loupe" />
        </span>

        <input type="search"
               wire:model.live.debounce.300ms="terme"
               x-on:focus="ouvert = true"
               x-on:input="ouvert = true"
               placeholder="{{ __('Rechercher un article, un service…') }}"
               class="w-full rounded-lg border border-zinc-300 py-2 pe-3 ps-10 text-sm dark:border-zinc-700 dark:bg-zinc-950">
    </label>

    @if (strlen(trim($terme)) >= 2)
        <div x-show="ouvert" x-cloak
             class="absolute z-50 mt-2 w-full overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @if (count($resultats))
                <ul class="max-h-96 divide-y divide-zinc-200 overflow-y-auto dark:divide-zinc-700">
                    @foreach ($resultats as $resultat)
                        <li>
                            <a href="{{ route($resultat['route']) }}" wire:navigate
                               wire:click="vider" x-on:click="ouvert = false"
                               class="flex items-center gap-3 px-4 py-2.5 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                    <x-admin.icone :nom="$resultat['icone']" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $resultat['intitule'] }}</span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $resultat['famille'] }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="px-4 py-6 text-center text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Aucun résultat pour « :terme ».', ['terme' => $terme]) }}
                </p>
            @endif
        </div>
    @endif
</div>
