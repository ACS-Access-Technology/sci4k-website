{{--
  Corps commun aux six listes de blocs.

  Reprend le motif eprouve au lot 2a : recherche, filtre de visibilite,
  reordonnancement par glisser-deposer, et la poignee qui disparait des qu'un
  filtre est actif — le glisser-deposer n'envoie que les lignes affichees, et
  les renumeroter « a partir de 1 » ecraserait les rangs des lignes cachees.

  Attend :
    $titre, $fil, $intitulePluriel
    $colonnes      [cle => intitule] des colonnes de contenu
    $cellule       fermeture (element, cle) => texte de la cellule
    $routeEdition, $routeCreation (facultative)
    $libelleCreation, $placeholder, $messageVide
    $nomLisible    fermeture (element) => texte cite dans les confirmations
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')
@php($routeCreation = $routeCreation ?? null)
@php($ordonnable = $peutEcrire && $recherche === '' && $visibilite === '')

<div class="space-y-6">

    {{-- Embarque dans un ecran de page, ce bloc n'a pas de titre a lui : la
         page qui l'accueille porte deja le sien. Le bouton de creation, lui,
         reste indispensable — sans quoi la liste ne servirait qu'a consulter. --}}
    @unless ($embarque ?? false)
        <x-admin.entete-page :titre="$titre" :fil="$fil"
            :resume="trans_choice(':nombre élément|:nombre éléments', $elements->count(), ['nombre' => $elements->count()])">
            <x-slot:actions>
                <x-bascule-langue />
                @if ($routeCreation)
                    @hasanyrole('administrateur|editeur')
                        <a href="{{ route($routeCreation) }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <x-admin.icone nom="plus" />
                            {{ $libelleCreation }}
                        </a>
                    @endhasanyrole
                @endif
            </x-slot:actions>
        </x-admin.entete-page>
    @else
        @if ($routeCreation)
            @hasanyrole('administrateur|editeur')
                <div class="flex justify-end">
                    @if ($composantFormulaire)
                        <button type="button" wire:click="ouvrirCreation"
                                class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <x-admin.icone nom="plus" />
                            {{ $libelleCreation }}
                        </button>
                    @else
                        <a href="{{ route($routeCreation) }}" wire:navigate
                           class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <x-admin.icone nom="plus" />
                            {{ $libelleCreation }}
                        </a>
                    @endif
                </div>
            @endhasanyrole
        @endif
    @endunless

    {{-- LE FORMULAIRE, OUVERT SUR PLACE

         Embarquee dans un ecran de page, la liste n'envoie plus l'editeur sur
         une autre adresse : elle ouvre le formulaire ici meme, sous le tableau.

         wire:key porte l'identifiant edite. Sans lui, Livewire reutiliserait
         l'instance d'une ligne a l'autre et afficherait les valeurs de la
         precedente — le meme defaut que celui corrige sur les cartes de biens. --}}
    @if ($composantFormulaire && $formulaireOuvert !== null)
        <div class="rounded-xl border border-zinc-300 bg-white p-5 dark:border-zinc-600 dark:bg-zinc-900">
            @livewire($composantFormulaire,
                $elementEnEdition
                    ? ['element' => $elementEnEdition, 'embarque' => true]
                    : ['embarque' => true],
                key('formulaire-'.$formulaireOuvert))
        </div>
    @endif

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    @isset($statistiques)
        @include('livewire.admin.partials.statistiques-de-bloc', ['statistiques' => $statistiques])
    @endisset

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ $placeholder }}" class="{{ $classeChamp }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Visibilité')" pour="visibilite">
            <select id="visibilite" wire:model.live="visibilite" class="{{ $classeChamp }}">
                <option value="">{{ __('Tous') }}</option>
                <option value="visibles">{{ __('Visibles') }}</option>
                <option value="masques">{{ __('Masqués') }}</option>
            </select>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    <x-admin.tableau :colonnes="array_merge([''], array_values($colonnes), [__('Statut'), __('Actions')])"
                     :ordonnable="$ordonnable">
        <x-slot:pied>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                @if ($recherche === '' && $visibilite === '')
                    {{ __("Faites glisser une ligne par sa poignée pour changer l'ordre d'affichage sur le site.") }}
                @else
                    {{ __("Retirez les filtres pour pouvoir réordonner : le glisser-déposer a besoin de la liste entière.") }}
                @endif
            </p>
        </x-slot:pied>

        @forelse ($elements as $element)
            <tr wire:key="bloc-{{ $element->id }}" data-id="{{ $element->id }}"
                class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="w-10 px-2 py-3">
                    @if ($ordonnable)
                        <x-admin.poignee-ordre class="poignee" />
                    @endif
                </td>

                {{-- Chaque cellule echappe elle-meme ses donnees avec e(), et
                     rend parfois du balisage — une vignette, des etoiles. Les
                     deux branches sortent donc du HTML : escaper la premiere
                     colonne seulement aurait affiche ses balises en clair. --}}
                @foreach ($colonnes as $cle => $intitule)
                    <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                        @if ($loop->first && $peutEcrire && $composantFormulaire)
                            <button type="button" wire:click="ouvrirEdition({{ $element->id }})"
                                    class="text-left font-medium text-zinc-900 hover:underline dark:text-white">
                                {!! $cellule($element, $cle) !!}
                            </button>
                        @elseif ($loop->first && $peutEcrire)
                            <a href="{{ route($routeEdition, $element) }}" wire:navigate
                               class="font-medium text-zinc-900 hover:underline dark:text-white">
                                {!! $cellule($element, $cle) !!}
                            </a>
                        @else
                            {!! $cellule($element, $cle) !!}
                        @endif
                    </td>
                @endforeach

                <td class="whitespace-nowrap px-4 py-3">
                    <button type="button" wire:click="basculerVisibilite({{ $element->id }})"
                            @disabled(! $peutEcrire)
                            class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $element->visible ? 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                        {{ $element->visible ? __('Visible') : __('Masqué') }}
                    </button>
                </td>

                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex items-center justify-end gap-1">
                        @if ($peutEcrire && $composantFormulaire)
                            <button type="button" wire:click="ouvrirEdition({{ $element->id }})"
                                    title="{{ __('Modifier') }}"
                                    aria-label="{{ __('Modifier :nom', ['nom' => $nomLisible($element)]) }}"
                                    class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </button>
                        @elseif ($peutEcrire)
                            <a href="{{ route($routeEdition, $element) }}" wire:navigate
                               title="{{ __('Modifier') }}"
                               aria-label="{{ __('Modifier :nom', ['nom' => $nomLisible($element)]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </a>

                            @if ($routeCreation)
                                {{-- Le bouton de suppression suit celui de
                                     creation : une collection dont le slug
                                     designe un emplacement du site ne se
                                     supprime pas davantage qu'elle ne se cree. --}}
                                <button type="button"
                                        wire:click="supprimer({{ $element->id }})"
                                        wire:confirm="{{ __('Supprimer définitivement « :nom » ? Cette action est irréversible.', ['nom' => $nomLisible($element)]) }}"
                                        title="{{ __('Supprimer') }}"
                                        aria-label="{{ __('Supprimer :nom', ['nom' => $nomLisible($element)]) }}"
                                        class="rounded-md p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400">
                                    <x-admin.icone nom="corbeille" />
                                </button>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($colonnes) + 3 }}" class="px-4 py-12 text-center text-zinc-600 dark:text-zinc-400">
                    {{ $messageVide }}
                </td>
            </tr>
        @endforelse
    </x-admin.tableau>

    @isset($recommandations)
        {{-- Les consignes de format vivent sous la liste plutot que dans le
             formulaire : c'est en regardant l'ensemble qu'on remarque une
             image trop lourde ou mal cadrée, pas en en éditant une seule. --}}
        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Recommandations') }}</h2>

            <div class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($recommandations as $recommandation)
                    <div>
                        <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $recommandation['titre'] }}</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $recommandation['texte'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endisset
</div>
