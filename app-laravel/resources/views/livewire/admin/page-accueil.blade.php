{{--
  L'accueil du site, gere depuis un seul ecran.

  Mise en page reprise de maquettes-backoffice/pages-edit.html : la liste des
  modules a gauche, l'editeur du module ouvert a droite.

  Aucune poignee de deplacement dans la liste : l'ordre des modules est fixe
  dans le gabarit public, et une poignee qui ne deplacerait rien serait un
  ecran menteur. Voir le commentaire de PageAccueil.
--}}
<div class="space-y-6">
    @php($titrePage = __("Page d'accueil"))
    <x-admin.entete-page :titre="$titrePage" :fil="[__('Accueil') => route('dashboard'), $titrePage => null]" />

    <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Tout ce que montre la page d’accueil du site, rassemblé ici.') }}</span>
        <a href="{{ route('home') }}" target="_blank" rel="noopener"
           class="font-medium text-zinc-900 underline underline-offset-4 dark:text-white">
            {{ __('Voir la page') }} →
        </a>
    </div>

    @if ($message)
        <p class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">
            {{ $message }}
        </p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">

        {{-- LES HUIT MODULES, DANS L'ORDRE DU SITE --}}
        <nav class="space-y-1" aria-label="{{ __('Modules de la page') }}">
            @foreach ($modules as $cle => $decrit)
                <button type="button" wire:click="ouvrir('{{ $cle }}')"
                        @class([
                            'w-full rounded-lg border px-3 py-2.5 text-left transition',
                            'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $module === $cle,
                            'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900' => $module !== $cle,
                        ])
                        @if ($module === $cle) aria-current="true" @endif>
                    <span class="flex items-baseline gap-2">
                        <span class="text-xs tabular-nums opacity-60">{{ $loop->iteration }}</span>
                        <span class="text-sm font-semibold">{{ $decrit['intitule'] }}</span>
                    </span>
                    <span @class([
                        'mt-0.5 block text-xs',
                        'opacity-70' => $module === $cle,
                        'text-zinc-500 dark:text-zinc-400' => $module !== $cle,
                    ])>{{ $decrit['resume'] }}</span>
                </button>
            @endforeach
        </nav>

        {{-- L'EDITEUR DU MODULE OUVERT --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $description['intitule'] }}</h2>
                        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description['resume'] }}</p>
                    </div>

                    @if ($description['ancre'] ?? null)
                        <a href="{{ route('home') }}{{ $description['ancre'] }}" target="_blank" rel="noopener"
                           class="shrink-0 text-sm text-zinc-600 underline underline-offset-4 dark:text-zinc-400">
                            {{ __('Voir sur le site') }} →
                        </a>
                    @endif
                </div>

                {{-- Onglets de la langue du CONTENU. « Français » et « English »
                     restent ecrits dans leur propre langue : ce sont des endonymes. --}}
                {{-- Un module d'encart n'a pas de champs propres : son
                     formulaire complet est embarque plus bas, avec sa propre
                     bascule de langue. --}}
                @if ($description['section'] ?? null)
                    <div class="mb-4 inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700">
                        @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $nom)
                            <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                                    @class([
                                        'rounded-md px-3 py-1 text-sm font-medium transition',
                                        'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $langueActive === $code,
                                        'text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                                    ])>{{ $nom }}</button>
                        @endforeach
                    </div>
                @endif

                {{-- Le formulaire n'apparait que si le module pilote un
                     en-tete de section. Un module d'encart n'aurait affiche
                     qu'un bouton « Enregistrer » sans rien a enregistrer. --}}
                @if ($description['section'] ?? null)
                <form wire:submit="enregistrer" class="space-y-4">

                    {{-- EN-TETE DE SECTION --}}
                    @if ($description['section'] ?? null)
                        {{-- La bande deroulante n'affiche que des communes : ni
                             etiquette ni accroche n'y seraient jamais montrees. --}}
                        @php($champsEntete = $description['champsEntete']
                            ?? ['etiquette' => __('Étiquette'), 'titre' => __('Titre'), 'chapo' => __('Accroche')])
                        @foreach ($champsEntete as $champ => $intitule)
                            <label class="block">
                                <span class="text-sm font-medium">{{ $intitule }}</span>
                                @if ($champ === 'chapo')
                                    <textarea wire:model="entete.{{ $champ }}_{{ $langueActive }}" rows="3"
                                              class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                @else
                                    <input wire:model="entete.{{ $champ }}_{{ $langueActive }}"
                                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @endif
                                @error('entete.'.$champ.'_'.$langueActive)
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        @endforeach
                    @endif

                    {{-- LES DEUX BOUTONS DU HERO
                         Ils etaient ecrits en dur dans le gabarit public, et le
                         premier pointait sur /biens.html — une adresse qui ne
                         repond plus que par une redirection. --}}
                    @if ($module === 'hero')
                        <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <legend class="px-1 text-sm font-medium">{{ __('Boutons') }}</legend>
                            @foreach (['bouton1' => __('Bouton principal'), 'bouton2' => __('Bouton secondaire')] as $cle => $intituleBouton)
                                <div class="mb-3 grid gap-3 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="text-sm font-medium">{{ $intituleBouton }}</span>
                                        <input wire:model="boutons.{{ $cle }}_libelle_{{ $langueActive }}"
                                               placeholder="{{ $cle === 'bouton1' ? __('Rechercher un bien') : __('Découvrir SCI4K') }}"
                                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    </label>
                                    <label class="block">
                                        <span class="text-sm font-medium">{{ __('Lien') }}</span>
                                        <input wire:model="boutons.{{ $cle }}_cible"
                                               placeholder="{{ $cle === 'bouton1' ? '/biens' : '/presentation' }}"
                                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    </label>
                                </div>
                            @endforeach
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Laissez vide pour garder le libellé et le lien d’origine.') }}
                            </p>
                        </fieldset>
                    @endif

                    {{-- APPARENCE DE LA BANDE DEROULANTE --}}
                    @if ($module === 'bandeau')
                        <div class="grid gap-4 sm:grid-cols-3">
                            <label class="block">
                                <span class="text-sm font-medium">{{ __('Fond') }}</span>
                                <select wire:model="bandeau.fond" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <option value="sombre">{{ __('Sombre') }}</option>
                                    <option value="clair">{{ __('Clair') }}</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium">{{ __('Séparateur') }}</span>
                                <input wire:model="bandeau.separateur" maxlength="5"
                                       class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium">{{ __('Casse') }}</span>
                                <select wire:model="bandeau.casse" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    <option value="majuscules">{{ __('Majuscules') }}</option>
                                    <option value="normale">{{ __('Normale') }}</option>
                                </select>
                            </label>
                        </div>
                    @endif

                    @if ($peutEcrire)
                        <button type="submit"
                                class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                            {{ __('Enregistrer') }}
                        </button>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Votre rôle ne permet pas de modifier cette page.') }}
                        </p>
                    @endif
                </form>
                @endif
            </div>

            {{-- LES IMAGES DE FOND DU MODULE --}}
            @include('livewire.admin.partials.images-du-module', [
                'images' => $images,
                'module' => $module,
            ])

            {{-- L'ANCIEN ECRAN, ENTIER, DANS LE MODULE

                 Embarque et non reecrit : chaque collection garde ses
                 statistiques, sa recherche, son reordonnancement par
                 glisser-deposer et ses actions. Dupliquer ce corps aurait cree
                 deux versions a corriger a chaque defaut.

                 wire:key est indispensable sur un composant imbrique : sans
                 lui, Livewire reutiliserait l'instance d'un module a l'autre
                 et afficherait les temoignages sous l'intitule « Partenaires ». --}}
            @if ($ecranEmbarque)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-4 text-sm font-semibold">{{ $ecranEmbarque['intitule'] }}</h3>
                    @livewire($ecranEmbarque['composant'],
                        $ecranEmbarque['parametres'] ?? ['embarque' => true],
                        key('embarque-'.$module))
                </div>
            @endif

            {{-- ARTICLES : automatiques, donc montres sans faire croire a un choix --}}
            @if ($module === 'articles')
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-sm font-semibold">{{ __('Articles qui seront affichés') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __("Ce sont les trois articles publiés les plus récents. Ils ne se choisissent pas ici : publiez ou dépubliez un article pour changer cette liste.") }}
                    </p>
                    <ul class="mt-3 divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($articles as $article)
                            <li class="flex items-center justify-between gap-3 py-2">
                                <span class="text-sm">{{ $article->titre($langueActive) }}</span>
                                {{-- Pas de lien vers l'ecran des articles : il
                                     deviendra la page « Actualités » de la
                                     refonte, et renvoyer vers un ecran voue a
                                     disparaitre rendrait celui-ci dependant. --}}
                                <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $article->date_publication?->translatedFormat('j M Y') }}
                                </span>
                            </li>
                        @empty
                            <li class="py-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Aucun article publié pour le moment.') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
