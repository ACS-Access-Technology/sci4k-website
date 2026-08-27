@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    <x-admin.entete-page
        :titre="$question ? __('Modifier la question') : __('Nouvelle question')"
        :fil="[__('Accueil') => route('dashboard'), __('FAQ') => route('admin.faq.liste'), ($question ? __('Modifier') : __('Nouvelle question')) => null]">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __('Rubrique') }}</span>
            {{-- wire:model.live et non wire:model : le choix de « créer une
                 rubrique » doit faire apparaitre les deux champs de nom sans
                 attendre l'enregistrement. --}}
            <select wire:model.live="rubriqueId" class="{{ $champ }}">
                <option value="">{{ __('Choisir…') }}</option>
                @foreach ($rubriques as $r)
                    <option value="{{ $r->id }}">{{ $r->nom($langue) }}</option>
                @endforeach
                <option value="{{ \App\Livewire\Admin\FaqFormulaire::NOUVELLE_RUBRIQUE }}">
                    {{ __('+ Créer une rubrique…') }}
                </option>
            </select>
            <span class="mt-1 block text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('Le nom de la rubrique sert de titre de groupe sur la page FAQ.') }}
            </span>
            @error('rubriqueId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        @if ($creeUneRubrique)
            {{-- Les deux langues sont demandees comme partout ailleurs. Si la
                 traduction automatique est active, remplir une seule suffit :
                 l'autre est completee a l'enregistrement. --}}
            <div class="sm:col-span-2 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="mb-3 text-sm font-medium">{{ __('Nouvelle rubrique') }}</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm">{{ __('Nom (français)') }}</span>
                        <input type="text" wire:model="nouvelleRubriqueFr" class="{{ $champ }}" placeholder="{{ __('Paiements') }}">
                        @error('nouvelleRubriqueFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="block">
                        <span class="text-sm">{{ __('Nom (anglais)') }}</span>
                        <input type="text" wire:model="nouvelleRubriqueEn" class="{{ $champ }}" placeholder="{{ __('Payments') }}">
                        @error('nouvelleRubriqueEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-400">
                    {{ __('La rubrique sera créée et placée en fin de liste. Vous pourrez la renommer ou la déplacer depuis l’écran des rubriques.') }}
                </p>
            </div>
        @endif

        <label class="flex items-center gap-2 self-end pb-2">
            <input type="checkbox" wire:model="visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    </div>

    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex gap-4" aria-label="{{ __('Langue du contenu') }}">
            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $intitule)
                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                        @class([
                            'border-b-2 px-1 py-2 text-sm',
                            'border-zinc-900 font-medium dark:border-white' => $langueActive === $code,
                            'border-transparent text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                        ])>{{ $intitule }}</button>
            @endforeach
        </nav>
    </div>

    @foreach (['fr' => 'Fr', 'en' => 'En'] as $code => $suffixe)
        <div class="space-y-4" @if ($langueActive !== $code) hidden @endif>
            <label class="block">
                <span class="text-sm font-medium">{{ __('Question') }}</span>
                <input type="text" wire:model="question{{ $suffixe }}" class="{{ $champ }}">
                @error('question'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Réponse') }}</span>
                <textarea wire:model="reponse{{ $suffixe }}" rows="8" class="{{ $champ }}"></textarea>
                @error('reponse'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    @endforeach

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('Enregistrer') }}
        </button>
        <a href="{{ route('admin.faq.liste') }}" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
            {{ __('Annuler') }}
        </a>
    </div>
</form>
