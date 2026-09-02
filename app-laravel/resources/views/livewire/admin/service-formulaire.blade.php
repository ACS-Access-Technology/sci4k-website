@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    {{-- Ouvert dans une liste, le formulaire n'a ni titre de page ni fil
         d'Ariane : la page qui l'accueille porte les siens. --}}
    @if ($embarque ?? false)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 pb-3 dark:border-zinc-700">
            <h4 class="text-sm font-semibold">
                {{ $estCreation ? __('Nouveau service') : __('Modifier le service') }}
            </h4>
            <div class="flex items-center gap-2">
                <x-bascule-langue />
                <button type="button" wire:click="$dispatch('bloc-annule')"
                        class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium dark:border-zinc-600">
                    {{ __('Annuler') }}
                </button>
            </div>
        </div>
    @else
        <x-admin.entete-page
            :titre="$estCreation ? __('Nouveau service') : __('Modifier le service')"
            :fil="[__('Accueil') => route('dashboard'), __('Services') => route('admin.services.liste'), ($estCreation ? __('Nouveau service') : $service->nom($langue)) => null]">
            <x-slot:actions>
                <x-bascule-langue />
            </x-slot:actions>
        </x-admin.entete-page>
    @endif

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    {{-- Le slug ne se saisit qu'a la creation. Ensuite il n'est plus qu'affiche :
         il porte l'ancre du pied de page, l'identifiant de la tuile et la classe
         CSS du fond, que le modifier casserait toutes les trois. --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium">{{ __("Identifiant d'adresse") }}</span>
            @if ($estCreation)
                <input type="text" wire:model="slug" class="{{ $champ }}" placeholder="gestion-location">
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __("Minuscules, chiffres et traits d'union. Définitif : il sert d'adresse au service sur le site.") }}
                </span>
            @else
                <p class="{{ $champ }} bg-zinc-50 text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400">{{ $service->slug }}</p>
                <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __("L'identifiant d'adresse ne se modifie pas : il sert de lien depuis le pied de page du site.") }}
                </span>
            @endif
            @error('slug') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="block">
            <span class="text-sm font-medium">{{ __('Catégorie') }}</span>
            <select wire:model="categorieId" class="{{ $champ }}">
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}">{{ $c->nom($langue) }}</option>
                @endforeach
            </select>
            @error('categorieId') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </label>

        <label class="flex items-center gap-2 self-end pb-2">
            <input type="checkbox" wire:model="visible" class="rounded border-zinc-300">
            <span class="text-sm font-medium">{{ __('Visible sur le site') }}</span>
        </label>
    </div>

    {{-- Choix ferme plutot que saisie libre : la vue publique rend ce tracé
         sans échappement, et un champ libre y ferait entrer n'importe quel
         balisage. Les pictogrammes proposés sont ceux déjà en base. --}}
    @if (count($icones))
        <fieldset>
            <legend class="text-sm font-medium">{{ __('Pictogramme de la tuile') }}</legend>
            <div class="mt-2 flex flex-wrap gap-2">
                <label class="cursor-pointer">
                    <input type="radio" wire:model="iconeSvg" value="" class="peer sr-only">
                    <span class="flex h-14 w-14 items-center justify-center rounded-lg border border-zinc-300 text-xs text-zinc-500 peer-checked:border-zinc-900 peer-checked:ring-2 peer-checked:ring-zinc-900 dark:border-zinc-700 dark:peer-checked:border-white dark:peer-checked:ring-white">
                        {{ __('Aucun') }}
                    </span>
                </label>
                @foreach ($icones as $indice => $icone)
                    <label class="cursor-pointer">
                        <input type="radio" wire:model="iconeSvg" value="{{ $icone }}" class="peer sr-only">
                        <span class="flex h-14 w-14 items-center justify-center rounded-lg border border-zinc-300 text-zinc-700 peer-checked:border-zinc-900 peer-checked:ring-2 peer-checked:ring-zinc-900 dark:border-zinc-700 dark:text-zinc-200 dark:peer-checked:border-white dark:peer-checked:ring-white"
                              aria-label="{{ __('Pictogramme :numero', ['numero' => $indice + 1]) }}">
                            {!! $icone !!}
                        </span>
                    </label>
                @endforeach
            </div>
            @error('iconeSvg') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </fieldset>
    @endif

    {{-- Onglets de la langue du CONTENU. « Français » et « English » restent
         ecrits dans leur propre langue : ce sont des endonymes qui designent la
         version que l'on redige, pas la langue de l'interface. --}}
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

    {{-- Les deux blocs restent dans le DOM, masques par `hidden` : une erreur de
         validation sur la langue inactive est ainsi conservee, et visible des
         qu'on revient sur son onglet. --}}
    @foreach (['fr' => 'Fr', 'en' => 'En'] as $code => $suffixe)
        <div class="space-y-4" @if ($langueActive !== $code) hidden @endif>
            <label class="block">
                <span class="text-sm font-medium">{{ __('Nom') }}</span>
                <input type="text" wire:model="nom{{ $suffixe }}" class="{{ $champ }}">
                @error('nom'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Accroche') }}</span>
                <input type="text" wire:model="accroche{{ $suffixe }}" class="{{ $champ }}">
                <span class="mt-1 block text-xs text-zinc-600 dark:text-zinc-400">{{ __('Phrase courte affichée sur la tuile.') }}</span>
                @error('accroche'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Description') }}</span>
                <textarea wire:model="description{{ $suffixe }}" rows="8" class="{{ $champ }}"></textarea>
                @error('description'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <fieldset class="space-y-2">
                <legend class="text-sm font-medium">{{ __('Atouts') }}</legend>
                <span class="block text-xs text-zinc-600 dark:text-zinc-400">{{ __('Trois au maximum, affichés sous le nom du service.') }}</span>
                @foreach ([1, 2, 3] as $n)
                    <input type="text" wire:model="atout{{ $n }}{{ $suffixe }}" class="{{ $champ }}"
                           aria-label="{{ __('Atout :rang', ['rang' => $n]) }}">
                    @error('atout'.$n.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                @endforeach
            </fieldset>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Libellé du bouton') }}</span>
                <input type="text" wire:model="libelleBouton{{ $suffixe }}" class="{{ $champ }}">
                @error('libelleBouton'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
            </label>
        </div>
    @endforeach

    <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <span class="block text-sm font-medium">{{ __('Image du service') }}</span>

        {{-- L'apercu n'est demande que si le fichier est valide : temporaryUrl()
             leve une exception sur un type non previsualisable, et la page
             tomberait au lieu d'afficher le message d'erreur. --}}
        @if ($image && ! $errors->has('image'))
            <img src="{{ $image->temporaryUrl() }}" alt="" class="h-40 rounded object-cover">
            <p class="text-xs text-zinc-500">{{ __("Nouvelle image, enregistrée à la validation du formulaire.") }}</p>
        @elseif ($imageActuelle && ! $imageARetirer)
            <img src="{{ asset($imageActuelle) }}" alt="" class="h-40 rounded object-cover">
            <button type="button" wire:click="supprimerImage" class="text-sm text-red-600 hover:underline">
                {{ __("Retirer l'image") }}
            </button>
        @elseif ($imageARetirer)
            <p class="text-sm text-amber-700 dark:text-amber-300">
                {{ __("L'image sera retirée à l'enregistrement.") }}
            </p>
        @else
            <p class="text-sm text-zinc-500">{{ __('Aucune image.') }}</p>
        @endif

        <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm">
        <span class="block text-xs text-zinc-500">{{ __('JPEG, PNG ou WebP, 4 Mo au maximum.') }}</span>
        <div wire:loading wire:target="image" class="text-xs text-zinc-500">{{ __('Envoi en cours…') }}</div>
        @error('image') <span class="block text-sm text-red-600">{{ $message }}</span> @enderror
    </div>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('Enregistrer') }}
        </button>
        @if ($embarque ?? false)
            <button type="button" wire:click="$dispatch('bloc-annule')"
                    class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
                {{ __('Annuler') }}
            </button>
        @else
            <a href="{{ route('admin.services.liste') }}" class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">
                {{ __('Annuler') }}
            </a>
        @endif
    </div>
</form>
