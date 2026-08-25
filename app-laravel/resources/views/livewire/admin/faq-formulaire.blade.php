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
            <span class="text-sm font-medium">{{ __('Service') }}</span>
            <select wire:model="serviceId" class="{{ $champ }}">
                <option value="">{{ __('Choisir…') }}</option>
                @foreach ($services as $s)
                    <option value="{{ $s->id }}">{{ $s->nom($langue) }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('Le nom du service sert de titre de groupe sur la page FAQ.') }}
            </span>
            @error('serviceId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

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
