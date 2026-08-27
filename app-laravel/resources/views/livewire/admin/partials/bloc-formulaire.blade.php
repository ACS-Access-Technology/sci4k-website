{{--
  Corps commun aux six formulaires de blocs.

  Il lit la description des champs fournie par le composant — la MEME que celle
  qui alimente la validation. Une description unique plutot qu'une dans le
  composant et une autre ici, qui pourraient diverger sans que rien ne le
  signale.

  Attend : $champs, $estCreation, $langue, $traductionActive, $fichierGere,
           $intitule, $routeListe, $fil, et $apercuFichier (facultatif).
--}}
@php($classeChamp = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    <x-admin.entete-page
        :titre="$estCreation ? __('Nouveau : :intitule', ['intitule' => $intitule]) : __('Modifier : :intitule', ['intitule' => $intitule])"
        :fil="$fil">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    {{-- Champs sans version par langue : ils ne dependent pas de l'onglet. --}}
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($champs as $nom => $description)
            @continue($description['bilingue'] ?? false)

            <label class="block">
                <span class="text-sm font-medium">{{ $description['intitule'] }}</span>

                @if (($description['type'] ?? 'texte') === 'fige' && ! $estCreation)
                    <p class="{{ $classeChamp }} bg-zinc-50 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $valeurs[$nom] }}</p>
                @else
                    <input type="{{ match ($description['type'] ?? 'texte') {
                                        'nombre' => 'number',
                                        'url' => 'url',
                                        'email' => 'email',
                                        default => 'text',
                                    } }}"
                           wire:model="valeurs.{{ $nom }}" class="{{ $classeChamp }}">
                @endif

                @isset($description['aide'])
                    <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description['aide'] }}</span>
                @endisset

                @error('valeurs.'.$nom) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        @endforeach
    </div>

    @if ($fichierGere)
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <span class="text-sm font-medium">{{ $descriptionFichier['intitule'] }}</span>

            {{-- L'apercu prend la forme de ce qu'il montre : un portrait rond
                 pour une personne, un cadre pour un logo. Une vignette carree
                 pour tout laissait deviner. --}}
            <div class="mt-2 flex items-center gap-3">
                @if ($fichierActuel)
                    <img src="{{ asset($fichierActuel) }}" alt=""
                         class="{{ $descriptionFichier['forme'] === 'rond'
                             ? 'size-16 rounded-full object-cover'
                             : 'h-14 w-20 rounded object-contain' }}">
                    <button type="button" wire:click="retirerFichier"
                            class="text-sm text-red-600 hover:underline">{{ __('Retirer') }}</button>
                @else
                    <span class="{{ $descriptionFichier['forme'] === 'rond'
                        ? 'flex size-16 items-center justify-center rounded-full border border-dashed border-zinc-300 dark:border-zinc-600'
                        : 'flex h-14 w-20 items-center justify-center rounded border border-dashed border-zinc-300 dark:border-zinc-600' }}">
                        <span class="text-xs text-zinc-400">{{ __('Aucune') }}</span>
                    </span>
                @endif
            </div>

            <input type="file" wire:model="fichier" accept="image/*" class="{{ $classeChamp }}">

            @if ($descriptionFichier['aide'])
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $descriptionFichier['aide'] }}</span>
            @endif

            <div wire:loading wire:target="fichier" class="mt-1 text-xs text-zinc-500">{{ __('Envoi en cours…') }}</div>
            @error('fichier') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>
    @endif

    {{-- Onglets de la langue du CONTENU. « Français » et « English » restent
         ecrits dans leur propre langue : ce sont des endonymes. --}}
    @php($aDesChampsBilingues = collect($champs)->contains(fn ($d) => $d['bilingue'] ?? false))

    @if ($aDesChampsBilingues)
        <div class="border-b border-zinc-200 dark:border-zinc-700">
            <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
                @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $nomLangue)
                    <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                            @class([
                                'border-b-2 px-1 py-2 text-sm',
                                'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                                'border-transparent text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                            ])>{{ $nomLangue }}</button>
                @endforeach
            </nav>
        </div>

        <div class="space-y-4">
            @foreach ($champs as $nom => $description)
                @continue(! ($description['bilingue'] ?? false))

                {{-- Les deux langues sont rendues, celle qui n'est pas active
                     etant masquee : la basculer ne doit pas coûter un
                     aller-retour au serveur ni perdre une saisie en cours. --}}
                @foreach (['fr', 'en'] as $code)
                    <label class="block {{ $langueActive === $code ? '' : 'hidden' }}">
                        <span class="text-sm font-medium">
                            {{ $description['intitule'] }} ({{ $code === 'fr' ? __('français') : __('anglais') }})
                        </span>

                        @if (($description['type'] ?? 'texte') === 'zone')
                            <textarea wire:model="valeurs.{{ $nom }}_{{ $code }}" rows="4" class="{{ $classeChamp }}"></textarea>
                        @else
                            <input type="text" wire:model="valeurs.{{ $nom }}_{{ $code }}" class="{{ $classeChamp }}">
                        @endif

                        @isset($description['aide'])
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description['aide'] }}</span>
                        @endisset

                        @error('valeurs.'.$nom.'_'.$code) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                @endforeach
            @endforeach
        </div>
    @endif

    @if ($gereLaVisibilite)
        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="valeurs.visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    @endif

    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            {{ __('Enregistrer') }}
        </button>
        <a href="{{ route($routeListe) }}" wire:navigate
           class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">{{ __('Annuler') }}</a>
    </div>
</form>
