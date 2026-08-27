{{--
  Valeurs des listes deroulantes du site public.

  Cinq familles editees ensemble, un seul bouton. Les categories d'articles et
  les rubriques de FAQ sont montrees en LECTURE, avec un renvoi vers leur
  ecran : elles ont deja leur table depuis les lots 1 et 2a, et les redonner en
  edition ici aurait cree deux sources pour la meme information.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="space-y-6">

    <x-admin.entete-page
        :titre="__('Référentiels')"
        :fil="[__('Accueil') => route('dashboard'), __('Réglages') => null, __('Référentiels') => null]">
        <x-slot:actions>
            <x-bascule-langue />
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ __('Enregistrer') }}
            </button>
        </x-slot:actions>
    </x-admin.entete-page>

    @if (session('succes'))
        <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
            {{ session('succes') }}
        </p>
    @endif

    <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
        <p class="font-medium">{{ __('Pourquoi cet écran') }}</p>
        <p class="mt-1">{{ __("Les filtres de la page des biens étaient écrits en dur dans le HTML. Les valeurs ci-dessous alimenteront à la fois ces filtres et les listes déroulantes de la fiche d'un bien, ce qui garantit un vocabulaire identique des deux côtés.") }}</p>
    </div>

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            <p class="font-medium">{{ __('Rien n’a été enregistré : corrigez les points suivants.') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @foreach ($familles as $famille => $definition)
        <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $definition['intitule'] }}</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $definition['aide'] }}</p>
                </div>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice(':nombre valeur|:nombre valeurs', count($lignes[$famille] ?? []), ['nombre' => count($lignes[$famille] ?? [])]) }}
                </span>
            </div>

            <div class="space-y-3 p-4">
                @forelse ($lignes[$famille] ?? [] as $cle => $ligne)
                    <div class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-start">
                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Libellé (français)') }}</span>
                            <input type="text" wire:model="lignes.{{ $famille }}.{{ $cle }}.libelle_fr" class="{{ $classeChamp }} mt-1">
                            @error("lignes.$famille.$cle.libelle_fr") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Libellé (anglais)') }}</span>
                            <input type="text" wire:model="lignes.{{ $famille }}.{{ $cle }}.libelle_en" class="{{ $classeChamp }} mt-1">
                            @error("lignes.$famille.$cle.libelle_en") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Valeur technique') }}</span>
                            <input type="text" wire:model="lignes.{{ $famille }}.{{ $cle }}.valeur" class="{{ $classeChamp }} mt-1 font-mono">
                            @error("lignes.$famille.$cle.valeur") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="flex items-center gap-3 sm:mt-6">
                            <label class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-300">
                                <input type="checkbox" wire:model="lignes.{{ $famille }}.{{ $cle }}.visible" value="1"
                                       class="size-4 rounded border-zinc-300 dark:border-zinc-600">
                                {{ __('Visible') }}
                            </label>

                            <button type="button" wire:click="retirer('{{ $famille }}', '{{ $cle }}')"
                                    class="text-xs text-red-600 hover:underline">{{ __('Retirer') }}</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Aucune valeur pour le moment.') }}</p>
                @endforelse

                <button type="button" wire:click="ajouter('{{ $famille }}')"
                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                    {{ __('Ajouter une valeur') }}
                </button>
            </div>
        </section>
    @endforeach

    {{-- Les deux familles qui vivent ailleurs. Affichees pour que l'ecran ne
         paraisse pas incomplet face a la maquette, en lecture pour qu'il n'y
         ait qu'un seul endroit ou les modifier. --}}
    <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Référentiels gérés ailleurs') }}</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __("Ces deux listes ont leur propre écran. Elles sont rappelées ici pour mémoire ; on les modifie là-bas, pour qu'il n'existe qu'un seul endroit où le faire.") }}</p>
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-2">
            @foreach ([
                [__("Catégories d'articles"), $categoriesArticles, 'admin.articles.liste'],
                [__('Rubriques de FAQ'), $rubriquesFaq, 'admin.rubriques-faq.liste'],
            ] as [$intitule, $elements, $route])
                <div>
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-sm font-medium">{{ $intitule }}</span>
                        <a href="{{ route($route) }}" wire:navigate class="text-xs text-zinc-600 hover:underline dark:text-zinc-300">{{ __('Modifier') }}</a>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($elements as $element)
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $element->nom($langueActive) }}
                            </span>
                        @empty
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Aucune') }}</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            {{ __('Enregistrer') }}
        </button>
    </div>
</form>
