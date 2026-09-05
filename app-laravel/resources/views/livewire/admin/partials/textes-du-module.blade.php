{{--
  Les textes d'un bloc qui ne sont ni un titre ni une accroche.

  Libelles d'un formulaire, exemples de saisie, mot d'un bouton, message quand
  une liste est vide : autant de textes que la page publique affiche et
  qu'aucun ecran n'exposait. Laisse vide, chacun retombe sur son texte
  d'origine, montre ici en filigrane.

  Ce bloc etait recopie a l'identique dans « Pages du site → FAQ » et
  « → Contact ». Il en fallait un troisieme pour les biens : trois copies d'un
  meme formulaire finissent par diverger, et c'est la divergence qui trompe
  l'editeur.

  Attend : $description (la declaration du module) et $langueActive.
--}}
@if ($description['textes'] ?? [])
    <fieldset class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <legend class="px-1 text-sm font-semibold">{{ $legendeDesTextes ?? __('Textes du bloc') }}</legend>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Laissez un champ vide pour garder le texte d’origine, rappelé en filigrane.') }}
        </p>

        @foreach ($description['textes'] as $nom => $decrit)
            <label class="block">
                <span class="text-sm font-medium">{{ __($decrit['intitule']) }}</span>
                @if ($decrit['long'] ?? false)
                    <textarea wire:model="textes.{{ $nom }}_{{ $langueActive }}" rows="3"
                              placeholder="{{ $langueActive === 'fr' ? $decrit['defaut'] : __($decrit['defaut'], [], 'en') }}"
                              class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                @else
                    <input wire:model="textes.{{ $nom }}_{{ $langueActive }}"
                           placeholder="{{ $langueActive === 'fr' ? $decrit['defaut'] : __($decrit['defaut'], [], 'en') }}"
                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                @endif
                @error('textes.'.$nom.'_'.$langueActive)
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
    </fieldset>
@endif
