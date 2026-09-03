@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    {{-- Le formulaire n'a ni titre de page ni fil d'Ariane : il est ouvert DANS
         la liste des rubriques, rendue depuis « Pages du site → FAQ », qui
         porte les siens. --}}
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-zinc-700">
        <h4 class="text-sm font-semibold">
            {{ $estCreation ? __('Nouvelle rubrique') : __('Modifier la rubrique') }}
        </h4>
        <div class="flex items-center gap-2">
            <x-bascule-langue />
            <button type="button" wire:click="$dispatch('bloc-annule')"
                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium dark:border-zinc-600">
                {{ __('Annuler') }}
            </button>
        </div>
    </div>

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __('Nom (français)') }}</span>
            <input type="text" wire:model="nomFr" class="{{ $champ }}" placeholder="{{ __('Paiements') }}">
            @error('nomFr') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">{{ __('Nom (anglais)') }}</span>
            <input type="text" wire:model="nomEn" class="{{ $champ }}" placeholder="{{ __('Payments') }}">
            @error('nomEn') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 self-end pb-2">
            <input type="checkbox" wire:model="visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    </div>

    <p class="text-xs text-zinc-600 dark:text-zinc-400">
        {{ __("Le nom de la rubrique sert de titre de groupe sur la page FAQ. Masquer une rubrique retire aussi ses questions du site.") }}
    </p>

    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            {{ __('Enregistrer') }}
        </button>
        <button type="button" wire:click="$dispatch('bloc-annule')"
                class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">{{ __('Annuler') }}</button>
    </div>
</form>
