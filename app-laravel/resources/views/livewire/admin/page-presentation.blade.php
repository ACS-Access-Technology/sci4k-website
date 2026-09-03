{{--
  La page Presentation, geree depuis un seul ecran.

  Meme mise en page que « Pages du site → Accueil » : la liste des modules a
  gauche, l'editeur du module ouvert a droite, et l'ancien ecran ENTIER
  embarque partout ou un module pilote une collection.

  Aucune poignee de deplacement dans la liste des modules : leur ordre est fixe
  dans presentation.blade.php.
--}}
<div class="space-y-6">
    @php($titrePage = __('Page Présentation'))
    <x-admin.entete-page :titre="$titrePage" :fil="[__('Accueil') => route('dashboard'), $titrePage => null]" />

    <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Tout ce que montre la page Présentation du site, rassemblé ici.') }}</span>
        <a href="{{ route('presentation.index') }}" target="_blank" rel="noopener"
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

        {{-- LES CINQ MODULES, DANS L'ORDRE DU SITE --}}
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

                {{-- « Français » et « English » restent ecrits dans leur propre
                     langue : ce sont des endonymes. --}}
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

                <form wire:submit="enregistrer" class="space-y-4">

                    {{-- Chaque module declare les champs d'en-tete qu'il
                         emploie : le mot du directeur n'affiche pas d'accroche
                         sur le site, et le champ n'aurait rien montre. --}}
                    @php($tousLesChamps = ['etiquette' => __('Étiquette'), 'titre' => __('Titre'), 'chapo' => __('Accroche')])
                    @php($champsEntete = isset($description['champsEntete'])
                        ? array_intersect_key($tousLesChamps, array_flip($description['champsEntete']))
                        : $tousLesChamps)

                    @foreach ($champsEntete as $champ => $intitule)
                        <label class="block">
                            <span class="text-sm font-medium">{{ $intitule }}</span>
                            @if ($champ === 'chapo')
                                <textarea wire:model="entete.{{ $champ }}_{{ $langueActive }}" rows="2"
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

                    {{-- LE CORPS DE TEXTE
                         Il logeait dans « accroche », un champ prevu pour une
                         phrase. Il a desormais le sien, et l'aide dit comment
                         separer les paragraphes — l'editeur ne pouvait pas le
                         deviner. --}}
                    @if ($description['contenu'] ?? false)
                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Texte de la section') }}</span>
                            <textarea wire:model="entete.contenu_{{ $langueActive }}" rows="10"
                                      class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                            <span class="mt-1 block text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Laissez une ligne vide entre deux paragraphes.') }}
                            </span>
                            @error('entete.contenu_'.$langueActive)
                                <span class="text-sm text-red-600">{{ $message }}</span>
                            @enderror
                        </label>
                    @endif

                    {{-- LES DEUX ATOUTS --}}
                    @if ($description['atouts'] ?? false)
                        <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <legend class="px-1 text-sm font-medium">{{ __('Atouts mis en avant') }}</legend>
                            @foreach (['atout1' => __('Premier atout'), 'atout2' => __('Second atout')] as $cle => $intituleAtout)
                                <div class="mb-3 space-y-2">
                                    <label class="block">
                                        <span class="text-sm font-medium">{{ $intituleAtout }}</span>
                                        <input wire:model="atouts.{{ $cle }}_titre_{{ $langueActive }}"
                                               placeholder="{{ $cle === 'atout1' ? __('Expertise Juridique') : __('Ancrage Abidjanais') }}"
                                               class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    </label>
                                    <textarea wire:model="atouts.{{ $cle }}_texte_{{ $langueActive }}" rows="2"
                                              placeholder="{{ __('Texte de l’atout') }}"
                                              class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                </div>
                            @endforeach
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Laissez vide pour garder le texte d’origine.') }}
                            </p>
                        </fieldset>
                    @endif

                    {{-- LE COMPTEUR --}}
                    @if ($description['compteur'] ?? false)
                        <fieldset class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <legend class="px-1 text-sm font-medium">{{ __('Compteur mis en avant') }}</legend>
                            <div class="grid gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                                <label class="block">
                                    <span class="text-sm font-medium">{{ __('Valeur') }}</span>
                                    <input type="number" min="0" wire:model="compteur.valeur" placeholder="14"
                                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    @error('compteur.valeur') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium">{{ __('Libellé') }}</span>
                                    <input wire:model="compteur.libelle_{{ $langueActive }}"
                                           placeholder="{{ __("quartiers d'Abidjan couverts par notre réseau d'agents") }}"
                                           class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                </label>
                            </div>
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Le nombre est animé au défilement. Laissez vide pour garder la valeur d’origine.') }}
                            </p>
                        </fieldset>
                    @endif

                    @if ($peutEcrire)
                        <button type="submit"
                                class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                            {{ __('Enregistrer') }}
                        </button>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Votre rôle ne permet pas de modifier cette page.') }}
                        </p>
                    @endif
                </form>
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
                    @livewire($ecranEmbarque['composant'], ['embarque' => true], key('embarque-'.$module))
                </div>
            @endif
        </div>
    </div>
</div>
