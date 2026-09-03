@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    {{-- Embarquee dans un ecran de page, la liste n'a ni titre ni fil d'Ariane
         a elle, et son bouton d'ajout ouvre le formulaire SUR PLACE. --}}
    @if ($embarque ?? false)
        @if ($peutEcrire)
            <div class="flex justify-end">
                <button type="button" wire:click="ouvrirCreation"
                        class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <x-admin.icone nom="plus" />
                    {{ __('Nouvel article') }}
                </button>
            </div>
        @endif

        @if ($formulaireOuvert !== null)
            @include('livewire.admin.partials.formulaire-sur-place', [
                'composant' => 'admin.article-formulaire',
                'parametres' => $articleEnEdition
                    ? ['article' => $articleEnEdition, 'embarque' => true]
                    : ['embarque' => true],
                'cle' => $formulaireOuvert,
            ])
        @endif
    @else
        <x-admin.entete-page
            :titre="__('Articles & actualités')"
            :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('Articles') => null]"
            :resume="trans_choice(':nombre article publié|:nombre articles publiés', $indicateurs['publies'], ['nombre' => $indicateurs['publies']])
                     .' · '.trans_choice(':nombre affiché|:nombre affichés', $articles->count(), ['nombre' => $articles->count()])">
            <x-slot:actions>
                <x-bascule-langue />
                @hasanyrole('administrateur|editeur')
                    <a href="{{ route('admin.articles.creation') }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                        <x-admin.icone nom="plus" />
                        {{ __('Nouvel article') }}
                    </a>
                @endhasanyrole
            </x-slot:actions>
        </x-admin.entete-page>
    @endif

    @if (session('message'))
        <div role="status"
             class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.carte-indicateur :valeur="$indicateurs['publies']" :intitule="__('Articles publiés')" ton="vert" icone="document" />
        <x-admin.carte-indicateur :valeur="$indicateurs['brouillons']" :intitule="__('Brouillons')" ton="ambre" icone="crayon" />
        <x-admin.carte-indicateur :valeur="$indicateurs['archives']" :intitule="__('Archivés')" ton="zinc" icone="archive" />
        <x-admin.carte-indicateur :valeur="$indicateurs['vues']" :intitule="__('Vues cumulées')" ton="bleu" icone="oeil" />
    </div>

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche">
            <input type="search" id="recherche" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __("Titre de l'article…") }}" class="{{ $champ }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Catégorie')" pour="categorie">
            <select id="categorie" wire:model.live="categorieId" class="{{ $champ }}">
                <option value="">{{ __('Toutes les catégories') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nom($langue) }}</option>
                @endforeach
            </select>
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Statut')" pour="statut">
            <select id="statut" wire:model.live="statut" class="{{ $champ }}">
                <option value="">{{ __('Tous les statuts') }}</option>
                <option value="publie">{{ __('Publié') }}</option>
                <option value="brouillon">{{ __('Brouillon') }}</option>
                <option value="archive">{{ __('Archivé') }}</option>
            </select>
        </x-admin.champ-filtre>

        {{-- Les filtres s'appliquent a la frappe : le bouton ne sert qu'a les
             remettre a zero, ce qu'aucun autre geste ne permet. --}}
        <x-admin.champ-filtre :intitule="__('Réinitialiser')">
            <button type="button" wire:click="$set('recherche', ''); $set('categorieId', ''); $set('statut', '')"
                    class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                {{ __('Tout afficher') }}
            </button>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    <x-admin.tableau :colonnes="[__('Article'), __('Catégorie'), __('Date'), __('Vues'), __('Statut'), __('Actions')]">
        @forelse ($articles as $article)
            <tr wire:key="article-{{ $article->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        @if ($url = $article->urlCouverture())
                            <img src="{{ $url }}" alt="" loading="lazy"
                                 class="h-11 w-11 shrink-0 rounded-lg object-cover">
                        @else
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <x-admin.icone nom="document" />
                            </span>
                        @endif

                        <div class="min-w-0">
                            @hasanyrole('administrateur|editeur')
                                {{-- Le titre est lui aussi un raccourci vers la
                                     fiche : embarque, il ouvre le formulaire sur
                                     place au lieu de faire sortir l'editeur. --}}
                                <a @if ($embarque ?? false) href="#" wire:click.prevent="ouvrirEdition({{ $article->id }})" @else href="{{ route('admin.articles.edition', $article) }}" wire:navigate @endif
                                   class="block truncate font-medium text-zinc-900 hover:underline dark:text-white">
                                    {{ $article->titre($langue) }}
                                </a>
                            @else
                                <span class="block truncate font-medium text-zinc-900 dark:text-white">{{ $article->titre($langue) }}</span>
                            @endhasanyrole
                            {{-- dark:text-zinc-400 et non zinc-500 : sur fond
                                 sombre, zinc-500 tombe a 3,8 de contraste, sous
                                 le seuil de 4,5 exige pour du petit texte. --}}
                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">/actualites/{{ $article->slug }}</span>
                        </div>
                    </div>
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $article->categorie->nom($langue) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $article->date_publication->format('d/m/Y') }}</td>

                <td class="whitespace-nowrap px-4 py-3">
                    <span class="inline-flex items-center gap-1.5 tabular-nums text-zinc-600 dark:text-zinc-300">
                        <x-admin.icone nom="oeil" class="h-4 w-4 text-zinc-400" />
                        {{ number_format($article->vues, 0, ',', "\u{202F}") }}
                    </span>
                </td>

                <td class="whitespace-nowrap px-4 py-3"><x-admin.pastille-statut :statut="$article->statut" /></td>

                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex items-center justify-end gap-1">
                        @hasanyrole('administrateur|editeur')
                            <a @if ($embarque ?? false) href="#" wire:click.prevent="ouvrirEdition({{ $article->id }})" @else href="{{ route('admin.articles.edition', $article) }}" wire:navigate @endif
                               title="{{ __('Modifier') }}" aria-label="{{ __('Modifier :titre', ['titre' => $article->titre($langue)]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="crayon" />
                            </a>
                        @endhasanyrole

                        @if ($article->statut === 'publie')
                            <a href="{{ route('actualites.detail', $article) }}" target="_blank" rel="noopener"
                               title="{{ __('Voir sur le site') }}" aria-label="{{ __('Voir :titre sur le site', ['titre' => $article->titre($langue)]) }}"
                               class="rounded-md p-2 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800 dark:hover:text-white">
                                <x-admin.icone nom="oeil" />
                            </a>
                        @endif

                        @hasanyrole('administrateur|editeur')
                            {{-- Confirmation obligatoire : la suppression est
                                 definitive, l'article n'ayant pas de corbeille. --}}
                            <button type="button"
                                    wire:click="supprimer({{ $article->id }})"
                                    wire:confirm="{{ __('Supprimer définitivement « :titre » ? Cette action est irréversible.', ['titre' => $article->titre($langue)]) }}"
                                    title="{{ __('Supprimer') }}" aria-label="{{ __('Supprimer :titre', ['titre' => $article->titre($langue)]) }}"
                                    class="rounded-md p-2 text-zinc-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400">
                                <x-admin.icone nom="corbeille" />
                            </button>
                        @endhasanyrole
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-12 text-center text-zinc-500">
                    {{ __('Aucun article ne correspond à votre recherche.') }}
                </td>
            </tr>
        @endforelse

        <x-slot:pied>
            {{ $articles->links() }}
        </x-slot:pied>
    </x-admin.tableau>
</div>
