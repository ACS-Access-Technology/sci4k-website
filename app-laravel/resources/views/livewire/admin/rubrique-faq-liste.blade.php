@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    {{-- Cette liste a son propre balisage et ne passe pas par bloc-liste. Elle
         est rendue depuis « Pages du site → FAQ », qui porte son titre et son
         fil d'Ariane : elle n'en a pas a elle. --}}
    @if ($peutEcrire)
        <div class="flex justify-end">
            <button type="button" wire:click="ouvrirCreation"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <x-admin.icone nom="plus" />
                {{ __('Nouvelle rubrique') }}
            </button>
        </div>
    @endif

    @if ($composantFormulaire && $formulaireOuvert !== null)
        @include('livewire.admin.partials.formulaire-sur-place', [
            'composant' => $composantFormulaire,
            'parametres' => $elementEnEdition
                ? [$parametreDuFormulaire => $elementEnEdition, 'embarque' => true]
                : ['embarque' => true],
            'cle' => $formulaireOuvert,
        ])
    @endif

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    {{-- Une suppression refusée doit se lire, sinon le clic semble n'avoir eu
         aucun effet et l'éditeur recommence. --}}
    @if (session('erreur'))
        <div role="alert" class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            {{ session('erreur') }}
        </div>
    @endif

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __('Nom de la rubrique…') }}" class="{{ $champ }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Visibilité')" pour="visibilite">
            <select id="visibilite" wire:model.live="visibilite" class="{{ $champ }}">
                <option value="">{{ __('Tous') }}</option>
                <option value="visibles">{{ __('Visibles') }}</option>
                <option value="masques">{{ __('Masqués') }}</option>
            </select>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    {{-- La poignée disparaît dès qu'un filtre est actif : le glisser-déposer
         n'envoie que les lignes affichées, et les renuméroter « à partir de 1 »
         écraserait les rangs des lignes cachées. --}}
    <x-admin.tableau :colonnes="['', __('Rubrique'), __('Questions'), __('Statut'), __('Actions')]"
                     :ordonnable="$peutEcrire && $recherche === '' && $visibilite === ''">
        <x-slot:pied>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                @if ($recherche === '' && $visibilite === '')
                    {{ __("Faites glisser une ligne par sa poignée pour changer l'ordre des groupes sur la page FAQ.") }}
                @else
                    {{ __("Retirez les filtres pour pouvoir réordonner : le glisser-déposer a besoin de la liste entière.") }}
                @endif
            </p>
        </x-slot:pied>

        @forelse ($elements as $element)
            <tr wire:key="rubrique-{{ $element->id }}" data-id="{{ $element->id }}"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="w-10 px-2 py-3">
                    @if ($peutEcrire && $recherche === '' && $visibilite === '')
                        <x-admin.poignee-ordre class="poignee" />
                    @endif
                </td>

                <td class="px-4 py-3">
                    @if ($peutEcrire)
                        <button type="button" wire:click="ouvrirEdition({{ $element->id }})"
                                class="block text-left font-medium text-zinc-900 hover:underline dark:text-white">
                            {{ $element->nom($langue) }}
                        </button>
                    @else
                        <span class="block font-medium text-zinc-900 dark:text-white">{{ $element->nom($langue) }}</span>
                    @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                    {{ trans_choice(':nombre question|:nombre questions', $element->questions_count, ['nombre' => $element->questions_count]) }}
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
                            <button type="button" wire:click="ouvrirEdition({{ $element->id }})"
                                    title="{{ __('Modifier') }}"
                                    aria-label="{{ __('Modifier :nom', ['nom' => $element->nom($langue)]) }}"
                                    class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </button>

                            {{-- Une rubrique portant des questions est refusée par
                                 le composant, pas ici : le bouton reste actif et
                                 explique pourquoi il n'a rien fait, plutôt que
                                 d'être grisé sans raison. --}}
                            <button type="button"
                                    wire:click="supprimer({{ $element->id }})"
                                    wire:confirm="{{ __('Supprimer définitivement la rubrique « :nom » ? Cette action est irréversible.', ['nom' => $element->nom($langue)]) }}"
                                    title="{{ __('Supprimer') }}"
                                    aria-label="{{ __('Supprimer :nom', ['nom' => $element->nom($langue)]) }}"
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
                    {{ __('Aucune rubrique ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>
</div>
