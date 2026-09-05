<div class="max-w-4xl space-y-6">
    <x-admin.entete-page :titre="__('Pages éditables')" :fil="[__('Accueil') => route('dashboard'), __('Pages éditables') => null]" />
    <label class="block"><span class="text-sm font-medium">{{ __('Page') }}</span><select wire:model.live="page" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">@foreach (\App\Models\PageStatique::EDITABLES as $slugEditable => $intituleEditable)<option value="{{ $slugEditable }}">{{ __($intituleEditable) }}</option>@endforeach</select></label>
    {{-- L'ecran doit dire ce que le site sert reellement. Tant que le contenu
         francais est vide, la route retombe sur la page HTML d'origine :
         l'editeur voyait sinon un champ vide sans savoir si sa page etait
         blanche ou intacte. --}}
    @if (trim($contenuFr) === '')
        <p class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200" role="status">
            {{ __("Ce contenu est vide : le site sert encore la page d'origine. Elle sera remplacée dès que vous enregistrerez un texte ici.") }}
        </p>
    @endif

    <form wire:submit="enregistrer" class="space-y-4">
        <div class="grid gap-4 sm:grid-cols-2"><label><span class="text-sm font-medium">{{ __('Titre français') }}</span><input wire:model="titreFr" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></label><label><span class="text-sm font-medium">{{ __('Titre anglais') }}</span><input wire:model="titreEn" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></label></div>
        <div class="grid gap-4 sm:grid-cols-2"><label><span class="text-sm font-medium">{{ __('Contenu français') }}</span><textarea wire:model="contenuFr" rows="18" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea></label><label><span class="text-sm font-medium">{{ __('Contenu anglais') }}</span><textarea wire:model="contenuEn" rows="18" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea></label></div>
        <label class="flex items-center gap-2"><input type="checkbox" wire:model="publie" class="rounded border-zinc-300"><span class="text-sm">{{ __('Page publiée') }}</span></label>

        {{-- LE GABARIT, COMMUN A TOUTES LES PAGES EDITABLES

             La ligne de date s'affiche sous le titre de CHAQUE page legale.
             Elle est la meme partout : la recopier dans chaque contenu aurait
             fait diverger ce qui doit rester identique. --}}
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                <h3 class="text-sm font-semibold">{{ __('Commun à toutes ces pages') }}</h3>

                {{-- « Français » et « English » restent ecrits dans leur propre
                     langue : ce sont des endonymes. --}}
                <div class="inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700">
                    @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $nom)
                        <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                                @class([
                                    'rounded-md px-3 py-1 text-sm font-medium transition',
                                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $langueActive === $code,
                                    'text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                                ])>{{ $nom }}</button>
                    @endforeach
                </div>
            </div>

            @include('livewire.admin.partials.textes-du-module', [
                'legendeDesTextes' => __('Textes du gabarit'),
            ])
        </div>

        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">{{ __('Enregistrer') }}</button>
    </form>
</div>
