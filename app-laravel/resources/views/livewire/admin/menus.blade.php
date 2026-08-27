{{--
  Navigation de l'en-tete et colonnes du pied de page.

  Trois menus editables. Les deux autres colonnes du pied sont montrees en
  LECTURE : « Nos Services » se remplit depuis les services, « Nous contacter »
  depuis les coordonnees de la configuration. Les rendre editables ici aurait
  cree une seconde source pour chacune.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="space-y-6">

    <x-admin.entete-page
        :titre="__('Menus du site')"
        :fil="[__('Accueil') => route('dashboard'), __('Réglages') => null, __('Menus') => null]">
        <x-slot:actions>
            <x-bascule-langue />
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ __('Enregistrer les menus') }}
            </button>
        </x-slot:actions>
    </x-admin.entete-page>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __("Navigation de l'en-tête et colonnes du pied de page") }}</p>

    @if (session('succes'))
        <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
            {{ session('succes') }}
        </p>
    @endif

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

    @foreach ($menus as $menu => $definition)
        <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $definition['intitule'] }}</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $definition['aide'] }}</p>
                </div>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice(':nombre entrée|:nombre entrées', count($entrees[$menu] ?? []), ['nombre' => count($entrees[$menu] ?? [])]) }}
                </span>
            </div>

            <div class="space-y-3 p-4">
                @forelse ($entrees[$menu] ?? [] as $cle => $entree)
                    <div class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto] sm:items-start">
                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Libellé (français)') }}</span>
                            <input type="text" wire:model="entrees.{{ $menu }}.{{ $cle }}.libelle_fr" class="{{ $classeChamp }} mt-1">
                            @error("entrees.$menu.$cle.libelle_fr") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Libellé (anglais)') }}</span>
                            <input type="text" wire:model="entrees.{{ $menu }}.{{ $cle }}.libelle_en" class="{{ $classeChamp }} mt-1">
                            @error("entrees.$menu.$cle.libelle_en") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Cible') }}</span>
                            <input type="text" wire:model="entrees.{{ $menu }}.{{ $cle }}.cible" class="{{ $classeChamp }} mt-1 font-mono">
                            @error("entrees.$menu.$cle.cible") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="flex items-center gap-3 sm:mt-6">
                            <label class="flex items-center gap-1.5 text-xs text-zinc-600 dark:text-zinc-300">
                                <input type="checkbox" wire:model="entrees.{{ $menu }}.{{ $cle }}.visible" value="1"
                                       class="size-4 rounded border-zinc-300 dark:border-zinc-600">
                                {{ __('Visible') }}
                            </label>

                            <button type="button" wire:click="retirer('{{ $menu }}', '{{ $cle }}')"
                                    class="text-xs text-red-600 hover:underline">{{ __('Retirer') }}</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Aucune entrée pour le moment.') }}</p>
                @endforelse

                <button type="button" wire:click="ajouter('{{ $menu }}')"
                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                    {{ __('Ajouter une entrée') }}
                </button>
            </div>
        </section>
    @endforeach

    <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ __("Une cible peut être un chemin du site commençant par « / », une adresse http(s) complète, ou un nom de route de l'application — par exemple « services.index ».") }}
    </p>

    {{-- Les deux colonnes que le site remplit tout seul. --}}
    <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Colonnes remplies automatiquement') }}</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __("Ces deux colonnes du pied de page suivent d'elles-mêmes ce qui est saisi ailleurs. Il n'y a rien à y recopier.") }}</p>
        </div>

        <div class="grid gap-4 p-4 sm:grid-cols-2">
            <div>
                <div class="flex items-baseline justify-between gap-2">
                    <span class="text-sm font-medium">{{ __('Nos Services') }}</span>
                    <a href="{{ route('admin.services.liste') }}" wire:navigate class="text-xs text-zinc-600 hover:underline dark:text-zinc-300">{{ __('Modifier les services') }}</a>
                </div>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @forelse ($servicesDuPied as $serviceDuPied)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            {{ $serviceDuPied->nom($langueActive) }}
                        </span>
                    @empty
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Aucun') }}</span>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="flex items-baseline justify-between gap-2">
                    <span class="text-sm font-medium">{{ __('Nous contacter') }}</span>
                    <a href="{{ route('admin.configuration') }}" wire:navigate class="text-xs text-zinc-600 hover:underline dark:text-zinc-300">{{ __('Modifier les coordonnées') }}</a>
                </div>
                <dl class="mt-2 space-y-1 text-xs">
                    @foreach ($coordonnees as $intitule => $valeur)
                        <div class="flex gap-2">
                            <dt class="shrink-0 text-zinc-500 dark:text-zinc-400">{{ $intitule }}</dt>
                            <dd class="text-zinc-700 dark:text-zinc-200">{{ $valeur ?: __('Non renseignée') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            {{ __('Enregistrer les menus') }}
        </button>
    </div>
</form>
