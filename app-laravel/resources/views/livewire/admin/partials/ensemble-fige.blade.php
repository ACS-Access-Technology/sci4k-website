{{--
  Corps commun aux trois ecrans d'ensembles figes.

  Tous les elements cote a cote, un seul bouton d'enregistrement. Ni creation
  ni suppression : leur nombre suit la maquette, et en ajouter un casserait la
  mise en page de la page publique sans que rien ne le signale.

  Attend :
    $titre        intitule de l'ecran
    $fil          fil d'Ariane
    $elements     les lignes, dans l'ordre d'affichage
    $champs       [prefixe => intitule] des champs bilingues
    $champsSimples [nom => intitule] des champs sans langue, facultatif
    $intituleRang comment nommer une ligne (« Valeur », « Chiffre »…)
--}}
@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')
@php($champsSimples = $champsSimples ?? [])

<form wire:submit="enregistrer" class="max-w-4xl space-y-6">

    <x-admin.entete-page :titre="$titre" :fil="$fil"
        :resume="trans_choice(':nombre élément|:nombre éléments', count($elements), ['nombre' => count($elements)])">
        <x-slot:actions>
            <x-bascule-langue />
        </x-slot:actions>
    </x-admin.entete-page>

    @if (session('message'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
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

    <div class="space-y-4">
        @foreach ($elements as $rang => $element)
            <fieldset wire:key="ligne-{{ $element->id }}"
                      class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <legend class="px-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">
                    {{ $intituleRang }} {{ $rang + 1 }}
                </legend>

                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($champsSimples as $nom => $intitule)
                        <label class="block sm:col-span-2">
                            <span class="text-sm font-medium">{{ $intitule }}</span>
                            <input type="number" wire:model="lignes.{{ $element->id }}.{{ $nom }}"
                                   @disabled(! $peutEcrire) class="{{ $champ }} sm:max-w-40">
                            @error('lignes.'.$element->id.'.'.$nom)
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    @endforeach

                    @foreach ($champs as $prefixe => $intitule)
                        {{-- Les deux langues sont rendues, celle qui n'est pas
                             active etant simplement masquee : la basculer ne
                             doit pas coûter un aller-retour au serveur ni
                             perdre une saisie en cours. --}}
                        @foreach (['fr', 'en'] as $code)
                            <label class="block {{ $langueActive === $code ? '' : 'hidden' }}">
                                <span class="text-sm font-medium">
                                    {{ $intitule }} ({{ $code === 'fr' ? __('français') : __('anglais') }})
                                </span>
                                <textarea wire:model="lignes.{{ $element->id }}.{{ $prefixe }}_{{ $code }}"
                                          rows="2" @disabled(! $peutEcrire)
                                          class="{{ $champ }}"></textarea>
                                @error('lignes.'.$element->id.'.'.$prefixe.'_'.$code)
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        @endforeach
                    @endforeach
                </div>
            </fieldset>
        @endforeach
    </div>

    @if ($peutEcrire)
        <div class="flex items-center gap-3">
            <button type="submit"
                    class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ __('Enregistrer') }}
            </button>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Un seul bouton : tous les éléments sont enregistrés ensemble.') }}
            </span>
        </div>
    @endif
</form>
