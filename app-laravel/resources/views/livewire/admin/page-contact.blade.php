{{--
  La page Contact, geree depuis un seul ecran.

  Meme mise en page que les cinq precedentes : la liste des modules a gauche,
  l'editeur du module ouvert a droite, et l'ancien ecran ENTIER embarque
  partout ou un module pilote une collection.
--}}
<div class="space-y-6">
    @php($titrePage = __('Page Contact'))
    <x-admin.entete-page :titre="$titrePage" :fil="[__('Accueil') => route('dashboard'), $titrePage => null]" />

    <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Tout ce que montre la page Contact du site, rassemblé ici.') }}</span>
        <a href="{{ route('contact.index') }}" target="_blank" rel="noopener"
           class="font-medium text-zinc-900 underline underline-offset-4 dark:text-white">
            {{ __('Voir la page') }} →
        </a>
    </div>

    @if ($message)
        <p class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">
            {{ $message }}
        </p>
    @endif

    <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">

        {{-- LES QUATRE MODULES, DANS L'ORDRE DU SITE --}}
        <nav class="space-y-1" aria-label="{{ __('Modules de la page') }}">
            @foreach ($modules as $cle => $decrit)
                <button type="button" wire:click="ouvrir('{{ $cle }}')"
                        @class([
                            'w-full rounded-lg border px-3 py-2.5 text-left transition',
                            'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $module === $cle,
                            'border-zinc-200 bg-white hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900' => $module !== $cle,
                        ])
                        @if ($module === $cle) aria-current="true" @endif>
                    <span class="flex items-baseline gap-2">
                        <span class="text-xs tabular-nums opacity-60">{{ $loop->iteration }}</span>
                        <span class="text-sm font-semibold">{{ $decrit['intitule'] }}</span>
                    </span>
                    <span @class([
                        'mt-0.5 block text-xs',
                        'opacity-70' => $module === $cle,
                        'text-zinc-500 dark:text-zinc-400' => $module !== $cle,
                    ])>{{ $decrit['resume'] }}</span>
                </button>
            @endforeach
        </nav>

        {{-- L'EDITEUR DU MODULE OUVERT --}}
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold">{{ $description['intitule'] }}</h2>
                    <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ $description['resume'] }}</p>
                </div>

                {{-- Le module « Messages reçus » n'edite rien : il ne porte ni
                     en-tete de section ni reglage, seulement l'ecran embarque.
                     Ni bascule de langue ni formulaire, donc. --}}
                @php($aQuoiEditer = ($description['section'] ?? null) || $reglagesDuModule)

                @if ($aQuoiEditer)
                    {{-- La bascule de langue ne sert qu'a du contenu bilingue.
                         Le module Coordonnées n'en a pas : une adresse et des
                         horaires ne se traduisent pas, et l'ecran Configuration
                         ne les propose que dans une seule version.

                         « Français » et « English » restent ecrits dans leur
                         propre langue : ce sont des endonymes. --}}
                    @if ($description['section'] ?? null)
                        <div class="mb-4 inline-flex rounded-lg border border-zinc-200 p-0.5 dark:border-zinc-700">
                            @foreach (['fr' => 'Français', 'en' => 'English'] as $code => $nom)
                                <button type="button" wire:click="$set('langueActive', '{{ $code }}')"
                                        @class([
                                            'rounded-md px-3 py-1 text-sm font-medium transition',
                                            'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $langueActive === $code,
                                            'text-zinc-600 dark:text-zinc-400' => $langueActive !== $code,
                                        ])>{{ $nom }}</button>
                            @endforeach
                        </div>
                    @endif

                    <form wire:submit="enregistrer" class="space-y-4">
                        @php($tousLesChamps = ['etiquette' => __('Étiquette'), 'titre' => __('Titre'), 'chapo' => __('Accroche')])
                        @php($champsEntete = ($description['section'] ?? null)
                            ? (isset($description['champsEntete'])
                                ? array_intersect_key($tousLesChamps, array_flip($description['champsEntete']))
                                : $tousLesChamps)
                            : [])

                        @foreach ($champsEntete as $champ => $intitule)
                            <label class="block">
                                <span class="text-sm font-medium">{{ $intitule }}</span>
                                @if ($champ === 'chapo')
                                    <textarea wire:model="entete.{{ $champ }}_{{ $langueActive }}" rows="3"
                                              class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                @else
                                    <input wire:model="entete.{{ $champ }}_{{ $langueActive }}"
                                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @endif
                                @error('entete.'.$champ.'_'.$langueActive)
                                    <span class="text-sm text-red-600">{{ $message }}</span>
                                @enderror
                            </label>
                        @endforeach

                        {{-- Les textes du bloc qui ne sont pas un en-tete.
                             Le formulaire etait recopie ici et dans deux autres
                             ecrans de page : trois copies finissent par
                             diverger, et c'est la divergence qui trompe
                             l'editeur. --}}
                        @include('livewire.admin.partials.textes-du-module', [
                            'legendeDesTextes' => __('Textes du formulaire'),
                        ])

                        {{-- Les reglages du site, et non du contenu : ce sont
                             les memes cles que l'onglet « Contact » de la
                             configuration, dont la declaration est reprise
                             telle quelle. Leur ecriture reste donc reservee
                             aux administrateurs, comme la-bas. --}}
                        @if ($reglagesDuModule)
                            <fieldset class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700"
                                      @disabled(! $peutReglerLeSite)>
                                <legend class="px-1 text-sm font-semibold">{{ __('Réglages du site') }}</legend>

                                @unless ($peutReglerLeSite)
                                    <p class="text-xs text-amber-700 dark:text-amber-400">
                                        {{ __('Ces valeurs sont partagées par tout le site : seul un administrateur peut les modifier.') }}
                                    </p>
                                @endunless

                                @foreach ($reglagesDuModule as $cle => $decrit)
                                    <label class="block">
                                        <span class="text-sm font-medium">{{ $decrit['intitule'] }}</span>
                                        @if (($decrit['type'] ?? 'texte') === 'zone')
                                            <textarea wire:model="reglages.{{ $cle }}" rows="3"
                                                      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                        @else
                                            <input type="{{ ($decrit['type'] ?? '') === 'courriel' ? 'email' : 'text' }}"
                                                   wire:model="reglages.{{ $cle }}"
                                                   class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                        @endif
                                        @if ($decrit['aide'] ?? null)
                                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">{{ $decrit['aide'] }}</span>
                                        @endif
                                        @error('reglages.'.$cle) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif

                        @if ($peutEcrire && ($reglagesDuModule === [] || $peutReglerLeSite))
                            <button type="submit"
                                    class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                                {{ __('Enregistrer') }}
                            </button>
                        @elseif (! $peutEcrire)
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Votre rôle ne permet pas de modifier cette page.') }}
                            </p>
                        @endif
                    </form>
                @endif
            </div>

            {{-- LES IMAGES DE FOND DU MODULE --}}
            @include('livewire.admin.partials.images-du-module', [
                'images' => $images,
                'module' => $module,
            ])

            {{-- L'ANCIEN ECRAN, ENTIER, DANS LE MODULE --}}
            @if ($ecranEmbarque)
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h3 class="mb-4 text-sm font-semibold">{{ $ecranEmbarque['intitule'] }}</h3>
                    @livewire($ecranEmbarque['composant'],
                        $ecranEmbarque['parametres'] ?? ['embarque' => true],
                        key('embarque-'.$module))
                </div>
            @endif
        </div>
    </div>
</div>
