@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Services')"
        :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('Services') => null]"
        :resume="trans_choice(':nombre service|:nombre services', $elements->count(), ['nombre' => $elements->count()])">
        <x-slot:actions>
            <x-bascule-langue />
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
                   placeholder="{{ __('Nom du service…') }}" class="{{ $champ }}">
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
    <x-admin.tableau :colonnes="['', __('Service'), __('Visuel'), __('Catégorie'), __('Statut'), __('Actions')]" :ordonnable="$peutEcrire">
        <x-slot:pied>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __("Faites glisser une ligne par sa poignée pour changer l'ordre d'affichage sur le site.") }}
            </p>
        </x-slot:pied>

        @forelse ($elements as $element)
            <tr wire:key="service-{{ $element->id }}" data-id="{{ $element->id }}"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="w-10 px-2 py-3">
                    @if ($peutEcrire)
                        <x-admin.poignee-ordre class="poignee" />
                    @endif
                </td>

                {{-- Le nom n'est pas encore un lien : la route d'edition arrive
                     a la tache 8, qui enveloppera ce bloc. Le referencer ici
                     ferait echouer les tests de la tache 6 sur une route
                     inexistante. --}}
                <td class="px-4 py-3">
                    <span class="block font-medium text-zinc-900 dark:text-white">
                        {{ $element->nom($langue) }}
                    </span>
                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $element->accroche($langue) }}
                    </span>
                </td>

                <td class="px-4 py-3">
                    @if ($element->image_source)
                        <img src="{{ asset($element->image_source) }}" alt="" loading="lazy" class="h-11 w-16 rounded object-cover">
                    @else
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Aucun') }}</span>
                    @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">
                    {{ $element->categorie->nom($langue) }}
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <button type="button" wire:click="basculerVisibilite({{ $element->id }})"
                            @disabled(! $peutEcrire)
                            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $element->visible ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                        {{ $element->visible ? __('Visible') : __('Masqué') }}
                    </button>
                </td>

                {{-- La colonne Actions reste vide jusqu'a la tache 8, pour la
                     meme raison. --}}
                <td class="whitespace-nowrap px-4 py-3"></td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-zinc-600 dark:text-zinc-400">
                    {{ __('Aucun service ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>
</div>
