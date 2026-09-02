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
                @if (($description['section'] ?? null) || ($description['encart'] ?? null))
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

                <form wire:submit="enregistrer" class="space-y-4">

                    {{-- EN-TETE DE SECTION --}}
                    @if ($description['section'] ?? null)
                        @foreach ([
                            'etiquette' => __('Étiquette'),
                            'titre' => __('Titre'),
                            'chapo' => __('Accroche'),
                        ] as $champ => $intitule)
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

                    {{-- ENCART --}}
                    @if ($description['encart'] ?? null)
                        @foreach ([
                            'etiquette' => __('Étiquette'),
                            'titre' => __('Titre'),
                            'texte' => __('Texte'),
                            'libelle_bouton' => __('Libellé du bouton'),
                        ] as $champ => $intitule)
                            <label class="block">
                                <span class="text-sm font-medium">{{ $intitule }}</span>
                                @if ($champ === 'texte')
                                    <textarea wire:model="encart.{{ $champ }}_{{ $langueActive }}" rows="3"
                                              class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                @else
                                    <input wire:model="encart.{{ $champ }}_{{ $langueActive }}"
                                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @endif
                            </label>
                        @endforeach

                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Lien du bouton') }}</span>
                            <input wire:model="encart.cible_bouton" placeholder="/biens"
                                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        </label>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-medium">{{ __('Diffuser à partir du') }}</span>
                                <input type="date" wire:model="encart.diffusion_de"
                                       class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @error('encart.diffusion_de') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block">
                                <span class="text-sm font-medium">{{ __("Jusqu'au") }}</span>
                                <input type="date" wire:model="encart.diffusion_a"
                                       class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @error('encart.diffusion_a') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                        </div>

                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model="encart.visible" class="rounded border-zinc-300">
                            <span class="text-sm">{{ __('Afficher ce bloc sur le site') }}</span>
                        </label>
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
            </div>

            {{-- IMAGE DE FOND DU MODULE --}}
            @if ($fond)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="text-sm font-semibold">{{ __('Image de fond') }}</h3>
                    <div class="mt-3 flex items-center gap-4">
                        @if ($fond->fichier)
                            <img src="{{ asset($fond->fichier) }}" alt=""
                                 class="h-16 w-28 rounded object-cover">
                        @endif
                        <div class="min-w-0 text-sm">
                            <p class="truncate text-zinc-600 dark:text-zinc-400">{{ $fond->fichier }}</p>
                            <a href="{{ route('admin.images-de-fond.edition', $fond) }}"
                               class="font-medium underline underline-offset-4">{{ __("Remplacer l'image") }}</a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- COLLECTIONS DU MODULE --}}
            @foreach ($collections as $famille => $elements)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <h3 class="text-sm font-semibold">
                            {{ [
                                'chiffres' => __('Chiffres clés'),
                                'communes' => __('Communes défilantes'),
                                'services' => __('Services affichés'),
                                'temoignages' => __('Avis affichés'),
                                'partenaires' => __('Partenaires affichés'),
                            ][$famille] ?? $famille }}
                        </h3>
                        <a href="{{ [
                            'chiffres' => route('admin.chiffres-cles'),
                            'communes' => route('admin.banderole'),
                            'services' => route('admin.services.liste'),
                            'temoignages' => route('admin.temoignages.liste'),
                            'partenaires' => route('admin.partenaires.liste'),
                        ][$famille] }}" class="text-sm underline underline-offset-4">
                            {{ __('Ajouter, réordonner, modifier en détail') }} →
                        </a>
                    </div>

                    @if ($elements->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Aucun élément pour le moment.') }}</p>
                    @else
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach ($elements as $element)
                                <li wire:key="{{ $famille }}-{{ $element->id }}" class="flex items-center justify-between gap-3 py-2">
                                    <span @class(['text-sm', 'text-zinc-400 line-through' => ! $element->visible])>
                                        {{ $this->libelleDeLElement($famille, $element) }}
                                    </span>
                                    @if ($peutEcrire)
                                        <button type="button" wire:click="basculer('{{ $famille }}', {{ $element->id }})"
                                                class="shrink-0 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium dark:border-zinc-600">
                                            {{ $element->visible ? __('Masquer') : __('Afficher') }}
                                        </button>
                                    @else
                                        <span class="shrink-0 text-xs text-zinc-500">{{ $element->visible ? __('Affiché') : __('Masqué') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

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
                                <a href="{{ route('admin.articles.edition', $article) }}" class="shrink-0 text-xs underline underline-offset-4">{{ __('Modifier') }}</a>
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
