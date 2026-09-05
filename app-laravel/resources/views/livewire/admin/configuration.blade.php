{{--
  Parametres generaux du site, en cinq onglets.

  Les champs viennent du composant, sous la meme forme que celle qui alimente
  la validation. Une description unique : un champ ajoute au composant apparait
  ici sans rien y toucher, et ne peut pas y arriver sans regle de validation.
--}}
@php($classeChamp = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<form wire:submit="enregistrer" class="max-w-3xl space-y-6">

    <x-admin.entete-page
        :titre="__('Configuration')"
        :fil="[__('Accueil') => route('dashboard'), __('Réglages') => null, __('Configuration') => null]">
        <x-slot:actions>
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                {{ __('Enregistrer') }}
            </button>
        </x-slot:actions>
    </x-admin.entete-page>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Paramètres généraux du site SCI4K') }}</p>

    @if (session('succes'))
        <p class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100">
            {{ session('succes') }}
        </p>
    @endif

    {{-- Les onglets ne masquent pas la validation : un champ en erreur dans un
         onglet FERME resterait invisible, et l'editeur croirait a un bouton
         mort. Toutes les erreurs sont donc reprises ici, quel que soit
         l'onglet ouvert, avec l'intitule du champ en cause — c'est ce que
         validationAttributes() fournit au composant. --}}
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

    {{-- La variable de boucle ne s'appelle PAS $onglet : ce nom est deja celui
         de la propriete du composant qui retient l'onglet ouvert, et la boucle
         l'aurait masquee — chaque onglet se serait alors cru actif. --}}
    <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
        @foreach ($onglets as $cle => $definition)
            <button type="button" wire:click="$set('onglet', '{{ $cle }}')" role="tab"
                    aria-selected="{{ $cle === $onglet ? 'true' : 'false' }}"
                    @class([
                        'rounded-t-lg px-4 py-2 text-sm font-medium transition',
                        'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white' => $cle === $onglet,
                        'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $cle !== $onglet,
                    ])>
                {{ $definition['intitule'] }}
            </button>
        @endforeach
    </div>

    @php($courant = $onglets[$onglet] ?? reset($onglets))

    <div class="space-y-4">
        @foreach ($courant['champs'] as $cle => $description)
            @php($type = $description['type'] ?? 'texte')

            @if ($type === 'case')
                <label class="flex items-start gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <input type="checkbox" wire:model="valeurs.{{ $cle }}" value="1"
                           class="mt-0.5 size-4 rounded border-zinc-300 dark:border-zinc-600">
                    <span>
                        <span class="text-sm font-medium">{{ $description['intitule'] }}</span>
                        @isset($description['aide'])
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description['aide'] }}</span>
                        @endisset
                    </span>
                </label>
            @else
                <label class="block">
                    <span class="text-sm font-medium">{{ $description['intitule'] }}</span>

                    @if ($type === 'zone')
                        <textarea wire:model="valeurs.{{ $cle }}" rows="3" class="{{ $classeChamp }}"></textarea>
                    @elseif ($type === 'liste')
                        <select wire:model="valeurs.{{ $cle }}" class="{{ $classeChamp }}">
                            @foreach ($description['choix'] as $valeur => $libelle)
                                <option value="{{ $valeur }}">{{ $libelle }}</option>
                            @endforeach
                        </select>
                    @elseif ($type === 'secret')
                        <input type="password" wire:model="valeurs.{{ $cle }}" autocomplete="new-password"
                               placeholder="{{ $this->secretEnregistre() ? __('Enregistré — laissez vide pour le conserver') : __('Aucun mot de passe enregistré') }}"
                               class="{{ $classeChamp }}">
                    @else
                        <input type="{{ match ($type) {
                                            'nombre' => 'number',
                                            'url' => 'url',
                                            'courriel' => 'email',
                                            default => 'text',
                                        } }}"
                               wire:model="valeurs.{{ $cle }}" class="{{ $classeChamp }}">
                    @endif

                    @isset($description['aide'])
                        <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $description['aide'] }}</span>
                    @endisset

                    @error('valeurs.'.$cle) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
            @endif
        @endforeach

        {{-- LES TEXTES DE LA PAGE D'ATTENTE

             Ils sont sur l'onglet Général, sous la case qui ferme le site :
             l'administrateur qui vient de la cocher voudra relire ce que le
             visiteur va lire, et il n'aura pas a chercher ailleurs.

             Ils ne peuvent pas etre des reglages comme le reste de cet ecran :
             ceux-la ne connaissent qu'une langue, et le site en sert deux —
             d'ou la bascule ci-dessous. --}}
        @if ($onglet === 'general')
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
                    <h3 class="text-sm font-semibold">{{ __('Page affichée pendant la maintenance') }}</h3>

                    {{-- « Français » et « English » restent ecrits dans leur
                         propre langue : ce sont des endonymes. --}}
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
                    'description' => ['textes' => \App\Livewire\Admin\Configuration::TEXTES_DE_LA_MAINTENANCE],
                    'legendeDesTextes' => __('Textes de la page d’attente'),
                ])
            </div>
        @endif

        {{-- L'essai d'envoi n'a de sens que sur l'onglet Messagerie. Il part
             vers l'adresse du compte connecte, jamais vers une adresse
             saisie : un destinataire libre ferait de l'ecran un relais
             d'envoi signe du nom de domaine du site. --}}
        @if ($onglet === 'messagerie')
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <button type="button" wire:click="envoyerUnEssai"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                    {{ __('Envoyer un e-mail de test') }}
                </button>

                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __("Le message part vers l'adresse de votre compte, et emploie les réglages ENREGISTRÉS — pensez à enregistrer avant de tester.") }}
                </p>

                @if ($resultatEssai)
                    <p class="mt-2 text-sm text-zinc-800 dark:text-zinc-200">{{ $resultatEssai }}</p>
                @endif
            </div>
        @endif

        {{-- Logo et favicon n'appartiennent qu'a l'onglet general. --}}
        @if ($onglet === 'general')
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([['logo', __('Logo'), $logoActuel], ['favicon', __('Favicon'), $faviconActuel]] as [$nom, $intitule, $actuel])
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <span class="text-sm font-medium">{{ $intitule }}</span>

                        <div class="mt-2 flex items-center gap-3">
                            @if ($actuel)
                                <img src="{{ asset($actuel) }}" alt="" class="h-12 w-16 rounded object-contain">
                            @else
                                <span class="flex h-12 w-16 items-center justify-center rounded border border-dashed border-zinc-300 dark:border-zinc-600">
                                    <span class="text-xs text-zinc-400">{{ __('Aucun') }}</span>
                                </span>
                            @endif

                            <input type="file" wire:model="{{ $nom }}" class="text-sm">
                        </div>

                        @error($nom) <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            {{ __('Enregistrer les modifications') }}
        </button>
    </div>
</form>
