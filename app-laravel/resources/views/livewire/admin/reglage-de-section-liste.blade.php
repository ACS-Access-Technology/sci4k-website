@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('En-têtes de section')"
        :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('En-têtes de section') => null]"
        :resume="trans_choice(':nombre section|:nombre sections', $elements->count(), ['nombre' => $elements->count()])">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <p class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ __("Chaque section du site porte une étiquette, un titre et un chapô. Ces en-têtes ne se créent ni ne se suppriment : ils appartiennent aux sections existantes.") }}
    </p>

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __('Une section, un titre…') }}" class="{{ $classeChamp }}">
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    <x-admin.tableau :colonnes="[__('Section'), __('Titre'), __('Étiquette'), __('Actions')]">
        @forelse ($elements as $element)
            <tr wire:key="reglage-{{ $element->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-zinc-500 dark:text-zinc-400">
                    {{ $element->slug }}
                </td>

                <td class="px-4 py-3">
                    @if ($peutEcrire)
                        <a href="{{ route('admin.reglages-de-section.edition', $element) }}" wire:navigate
                           class="font-medium text-zinc-900 hover:underline dark:text-white">
                            {{ $element->titre($langue) ?: __('(sans titre)') }}
                        </a>
                    @else
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $element->titre($langue) ?: __('(sans titre)') }}</span>
                    @endif
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ Str::limit($element->chapo($langue), 90) }}
                    </span>
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                    {{ $element->etiquette($langue) ?: '—' }}
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex items-center justify-end gap-1">
                        @if ($peutEcrire)
                            <a href="{{ route('admin.reglages-de-section.edition', $element) }}" wire:navigate
                               title="{{ __('Modifier') }}"
                               aria-label="{{ __('Modifier :nom', ['nom' => $element->slug]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-12 text-center text-zinc-600 dark:text-zinc-400">
                    {{ __('Aucune section ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>
</div>
