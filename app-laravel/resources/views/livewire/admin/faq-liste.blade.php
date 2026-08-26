@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('FAQ')"
        :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('FAQ') => null]"
        :resume="trans_choice(':nombre question|:nombre questions', $elements->count(), ['nombre' => $elements->count()])">
        <x-slot:actions>
            <x-bascule-langue />
            <a href="{{ route('admin.rubriques-faq.liste') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('Rubriques') }}
            </a>
            @hasanyrole('administrateur|editeur')
                <a href="{{ route('admin.faq.creation') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <x-admin.icone nom="plus" />
                    {{ __('Nouvelle question') }}
                </a>
            @endhasanyrole
        </x-slot:actions>
    </x-admin.entete-page>

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __('Une question…') }}" class="{{ $champ }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Visibilité')" pour="visibilite">
            <select id="visibilite" wire:model.live="visibilite" class="{{ $champ }}">
                <option value="">{{ __('Tous') }}</option>
                <option value="visibles">{{ __('Visibles') }}</option>
                <option value="masques">{{ __('Masqués') }}</option>
            </select>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    {{-- L'ordre se regle en deplaçant les lignes par leur poignee : glisser-
         depose ecrit a la main (resources/js/ordre.js), aucune dependance
         ajoutee. --}}
    <x-admin.tableau :colonnes="['', __('Question'), __('Rubrique'), __('Statut'), __('Actions')]" :ordonnable="$peutEcrire">
        <x-slot:pied>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __("Faites glisser une ligne par sa poignée pour changer l'ordre d'affichage sur le site.") }}
            </p>
        </x-slot:pied>

        @forelse ($elements as $element)
            <tr wire:key="question-{{ $element->id }}" data-id="{{ $element->id }}"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="w-10 px-2 py-3">
                    @if ($peutEcrire)
                        <x-admin.poignee-ordre class="poignee" />
                    @endif
                </td>

                <td class="px-4 py-3">
                    @if ($peutEcrire)
                        <a href="{{ route('admin.faq.edition', $element) }}" wire:navigate
                           class="block font-medium text-zinc-900 hover:underline dark:text-white">
                            {{ $element->question($langue) }}
                        </a>
                    @else
                        <span class="block font-medium text-zinc-900 dark:text-white">{{ $element->question($langue) }}</span>
                    @endif
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ Str::limit($element->reponse($langue), 90) }}
                    </span>
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                    {{ $element->rubrique->nom($langue) }}
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <button type="button" wire:click="basculerVisibilite({{ $element->id }})"
                            @disabled(! $peutEcrire)
                            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $element->visible ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                        {{ $element->visible ? __('Visible') : __('Masqué') }}
                    </button>
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex items-center justify-end gap-1">
                        @if ($peutEcrire)
                            <a href="{{ route('admin.faq.edition', $element) }}" wire:navigate
                               title="{{ __('Modifier') }}"
                               aria-label="{{ __('Modifier :nom', ['nom' => $element->question($langue)]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </a>

                            {{-- Suppression definitive, sans corbeille intermediaire :
                                 confirmation obligatoire avant l'appel Livewire. --}}
                            <button type="button"
                                    wire:click="supprimer({{ $element->id }})"
                                    wire:confirm="{{ __('Supprimer définitivement « :question » ? Cette action est irréversible.', ['question' => $element->question($langue)]) }}"
                                    title="{{ __('Supprimer') }}" aria-label="{{ __('Supprimer :nom', ['nom' => $element->question($langue)]) }}"
                                    class="rounded-md p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400">
                                <x-admin.icone nom="corbeille" />
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-4 py-12 text-center text-zinc-600 dark:text-zinc-400">
                    {{ __('Aucune question ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>
</div>
