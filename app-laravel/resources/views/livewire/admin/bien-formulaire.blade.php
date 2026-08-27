{{--
  Fiche d'un bien. Quatre onglets, comme la maquette.
--}}
@php($champ = 'mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')
@php($onglets = ['general' => __('Informations générales'), 'caracteristiques' => __('Caractéristiques'), 'seo' => __('Référencement'), 'photos' => __('Photos')])

<form wire:submit="enregistrer" class="space-y-6">

    <x-admin.entete-page
        :titre="$estCreation ? __('Nouveau bien') : __('Modifier le bien')"
        :fil="[__('Accueil') => route('dashboard'), __('Biens') => route('admin.biens.liste'), ($estCreation ? __('Nouveau') : __('Modifier')) => null]">
        <x-slot:actions>
            <x-bascule-langue />
            @if ($peutEcrire)
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900">
                    {{ __('Enregistrer') }}
                </button>
            @endif
        </x-slot:actions>
    </x-admin.entete-page>

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">{{ $message }}</p>
    @endif

    @if ($traductionActive)
        <p class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 dark:border-sky-800 dark:bg-sky-950 dark:text-sky-100">
            {{ __("Vous pouvez ne remplir qu'une langue : l'autre sera traduite à l'enregistrement. Un texte déjà saisi n'est jamais remplacé.") }}
        </p>
    @endif

    {{-- Les erreurs sont reprises en entier : un champ fautif dans un onglet
         ferme resterait invisible, et le bouton paraitrait mort. --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-red-100">
            <p class="font-medium">{{ __('Rien n’a été enregistré : corrigez les points suivants.') }}</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $erreur) <li>{{ $erreur }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
        @foreach ($onglets as $cle => $intitule)
            <button type="button" wire:click="$set('onglet', '{{ $cle }}')" role="tab"
                    aria-selected="{{ $cle === $onglet ? 'true' : 'false' }}"
                    @class([
                        'rounded-t-lg px-4 py-2 text-sm font-medium transition',
                        'border-b-2 border-zinc-900 text-zinc-900 dark:border-white dark:text-white' => $cle === $onglet,
                        'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' => $cle !== $onglet,
                    ])>{{ $intitule }}</button>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">

            {{-- ------------------------------------------------ général --}}
            @if ($onglet === 'general')
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Référence') }}</span>
                        <input type="text" wire:model="reference" placeholder="SCI4K-0123" class="{{ $champ }}">
                        <span class="mt-1 block text-xs text-zinc-500">{{ __('Facultative. Les six biens repris du site n’en portaient aucune.') }}</span>
                        @error('reference') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __("Identifiant d'URL") }}</span>
                        <input type="text" wire:model="slug" class="{{ $champ }} font-mono">
                        <span class="mt-1 block text-xs text-zinc-500">/biens/{{ $slug ?: '…' }}</span>
                        @error('slug') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                </div>

                @foreach (['fr' => __('français'), 'en' => __('anglais')] as $code => $nomLangue)
                    <div class="{{ $langueActive === $code ? '' : 'hidden' }} space-y-4">
                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Titre') }} ({{ $nomLangue }})</span>
                            <input type="text" wire:model{{ $code === 'fr' ? '.live.debounce.500ms' : '' }}="titre{{ ucfirst($code) }}" class="{{ $champ }}">
                            @error('titre'.ucfirst($code)) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Sous-titre') }} ({{ $nomLangue }})</span>
                            <input type="text" wire:model="sousTitre{{ ucfirst($code) }}" class="{{ $champ }}"
                                   placeholder="{{ __('Villa moderne · F5') }}">
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Accroche') }} ({{ $nomLangue }})</span>
                            <textarea wire:model="accroche{{ ucfirst($code) }}" rows="2" class="{{ $champ }}"></textarea>
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('160 caractères au plus — employée dans les listes et les résultats de recherche.') }}</span>
                            @error('accroche'.ucfirst($code)) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Description complète') }} ({{ $nomLangue }})</span>
                            <textarea wire:model="description{{ ucfirst($code) }}" rows="8" class="{{ $champ }}"></textarea>
                        </label>
                    </div>
                @endforeach
            @endif

            {{-- ---------------------------------------- caractéristiques --}}
            @if ($onglet === 'caracteristiques')
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Type') }}</span>
                        <select wire:model="type" class="{{ $champ }}">
                            @foreach ($types as $valeur)
                                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
                            @endforeach
                        </select>
                        @error('type') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Offre') }}</span>
                        <select wire:model="offre" class="{{ $champ }}">
                            @foreach ($offres as $cle => $intitule)
                                <option value="{{ $cle }}">{{ $intitule }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Prix (FCFA)') }}</span>
                        <input type="number" wire:model="prix" min="0" class="{{ $champ }}">
                        <span class="mt-1 block text-xs text-zinc-500">{{ __("Facultatif. Le site public n'affiche aucun prix aujourd'hui.") }}</span>
                        @error('prix') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Prix exprimé') }}</span>
                        <select wire:model="prixUnite" class="{{ $champ }}">
                            @foreach ($unitesDePrix as $cle => $intitule)
                                <option value="{{ $cle }}">{{ $intitule }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Surface habitable (m²)') }}</span>
                        <input type="number" wire:model="surfaceHabitable" min="0" class="{{ $champ }}">
                        <span class="mt-1 block text-xs text-zinc-500">{{ __('La tranche de recherche s’en déduit : rien à choisir.') }}</span>
                        @error('surfaceHabitable') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Surface du terrain (m²)') }}</span>
                        <input type="number" wire:model="surfaceTerrain" min="0" class="{{ $champ }}">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Pièces') }}</span>
                        <input type="number" wire:model="nombrePieces" min="0" class="{{ $champ }}">
                        <span class="mt-1 block text-xs text-zinc-500">{{ __('Laissez vide pour un terrain : il n’a pas de pièces.') }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Chambres') }}</span>
                        <input type="number" wire:model="nombreChambres" min="0" class="{{ $champ }}">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __("Salles d'eau") }}</span>
                        <input type="number" wire:model="nombreSallesEau" min="0" class="{{ $champ }}">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Zone') }}</span>
                        <select wire:model="zone" class="{{ $champ }}">
                            @foreach ($zones as $valeur)
                                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
                            @endforeach
                        </select>
                        @error('zone') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Quartier') }}</span>
                        <input type="text" wire:model="quartier" class="{{ $champ }}" placeholder="{{ __('Riviera Golf') }}">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Statut juridique') }}</span>
                        <select wire:model="statutJuridique" class="{{ $champ }}">
                            <option value="">{{ __('Non précisé') }}</option>
                            @foreach ($statutsJuridiques as $valeur)
                                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
                            @endforeach
                        </select>
                        @error('statutJuridique') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium">{{ __('Numéro de titre') }}</span>
                        <input type="text" wire:model="numeroTitre" class="{{ $champ }}">
                    </label>
                </div>

                {{-- Une liste libre plutot que des cases a cocher : le site
                     affiche « Cuisine américaine équipée » ou « Fibre optique »,
                     que huit cases n'auraient pas su dire. --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['Fr' => __('français'), 'En' => __('anglais')] as $suffixe => $nomLangue)
                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Équipements') }} ({{ $nomLangue }})</span>
                            <textarea wire:model="equipements{{ $suffixe }}" rows="7" class="{{ $champ }}"
                                      placeholder="{{ __('Un équipement par ligne') }}"></textarea>
                            @error('equipements'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endforeach
                </div>
            @endif

            {{-- ------------------------------------------- référencement --}}
            @if ($onglet === 'seo')
                @foreach (['Fr' => __('français'), 'En' => __('anglais')] as $suffixe => $nomLangue)
                    <div class="space-y-4">
                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Titre meta') }} ({{ $nomLangue }})</span>
                            <input type="text" wire:model="metaTitre{{ $suffixe }}" class="{{ $champ }}">
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('70 caractères au plus : au-delà, Google tronque.') }}</span>
                            @error('metaTitre'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Description meta') }} ({{ $nomLangue }})</span>
                            <textarea wire:model="metaDescription{{ $suffixe }}" rows="3" class="{{ $champ }}"></textarea>
                            @error('metaDescription'.$suffixe) <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    </div>
                @endforeach
            @endif

            {{-- --------------------------------------------------- photos --}}
            @if ($onglet === 'photos')
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm font-medium">{{ __('Galerie') }}</span>
                        <span class="text-xs text-zinc-500">{{ $photos->count() }} / {{ $photosMax }}</span>
                    </div>

                    @if ($photos->isEmpty())
                        {{-- L'absence de photo est le cas NORMAL au démarrage : les
                             six biens repris du site sont des illustrations. --}}
                        <div class="mt-3 flex items-center gap-4 rounded-lg border border-dashed border-zinc-300 p-4 dark:border-zinc-600">
                            <span class="flex h-16 w-24 items-center justify-center rounded bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                                <x-public.illustration-bien :type="$type" class="h-10 w-14" />
                            </span>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">
                                {{ __("Aucune photo. Le site affiche l'illustration ci-contre, choisie d'après le type du bien.") }}
                            </p>
                        </div>
                    @else
                        <div class="mt-3 grid gap-3 sm:grid-cols-3 lg:grid-cols-4">
                            @foreach ($photos as $photo)
                                <div wire:key="photo-{{ $photo->id }}" class="relative">
                                    <img src="{{ asset($photo->fichier) }}" alt="" loading="lazy"
                                         class="h-24 w-full rounded object-cover">
                                    @if ($loop->first)
                                        <span class="absolute left-1 top-1 rounded bg-zinc-900/80 px-1.5 py-0.5 text-xs text-white">{{ __('Principale') }}</span>
                                    @endif
                                    @if ($peutEcrire)
                                        <button type="button" wire:click="retirerPhoto({{ $photo->id }})"
                                                class="mt-1 text-xs text-red-600 hover:underline">{{ __('Retirer') }}</button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">{{ __('La première photo sert de visuel principal.') }}</p>
                    @endif

                    @if ($peutEcrire && $photos->count() < $photosMax)
                        <label class="mt-4 block">
                            <span class="text-sm font-medium">{{ __('Ajouter des photos') }}</span>
                            <input type="file" wire:model="nouvellesPhotos" multiple accept="image/*" class="mt-1 text-sm">
                            <span class="mt-1 block text-xs text-zinc-500">{{ __('JPG ou PNG, 2 Mo au maximum par image.') }}</span>
                            @error('nouvellesPhotos.*') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                    @endif

                    @if ($estCreation)
                        <p class="mt-3 text-xs text-zinc-500">{{ __('Les photos pourront être ajoutées une fois le bien enregistré.') }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- ------------------------------------------------- publication --}}
        <aside class="space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Publication') }}</h2>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Statut') }}</span>
                <select wire:model="statut" class="{{ $champ }}">
                    @foreach ($statuts as $cle => $intitule)
                        <option value="{{ $cle }}">{{ $intitule }}</option>
                    @endforeach
                </select>
                <span class="mt-1 block text-xs text-zinc-500">{{ __('Un bien vendu reste visible sur le site, marqué comme tel.') }}</span>
            </label>

            <label class="block">
                <span class="text-sm font-medium">{{ __('Date de mise en ligne') }}</span>
                <input type="date" wire:model="dateMiseEnLigne" class="{{ $champ }}">
            </label>

            <label class="flex items-start gap-2">
                <input type="checkbox" wire:model="enAvant" class="mt-0.5 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm">{{ __("Mettre en avant sur l'accueil") }}</span>
            </label>

            <label class="flex items-start gap-2">
                <input type="checkbox" wire:model="urgent" class="mt-0.5 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm">{{ __('Signaler comme urgent') }}</span>
            </label>

            @unless ($estCreation)
                <dl class="border-t border-zinc-200 pt-3 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    <div class="flex justify-between gap-2">
                        <dt>{{ __('Créé le') }}</dt>
                        <dd>{{ $bien->created_at?->translatedFormat('d/m/Y à H:i') }}</dd>
                    </div>
                    <div class="mt-1 flex justify-between gap-2">
                        <dt>{{ __('Modifié le') }}</dt>
                        <dd>{{ $bien->updated_at?->translatedFormat('d/m/Y à H:i') }}</dd>
                    </div>
                    @if ($bien->auteur)
                        <div class="mt-1 flex justify-between gap-2">
                            <dt>{{ __('Auteur') }}</dt>
                            <dd>{{ $bien->auteur->name }}</dd>
                        </div>
                    @endif
                </dl>
            @endunless
        </aside>
    </div>
</form>
