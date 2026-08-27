{{--
  Catalogue des biens.

  Les cinq filtres sont ceux de la page publique, aux memes valeurs techniques :
  un bien retrouve ici doit l'etre par le meme filtre cote visiteur.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Biens immobiliers')"
        :fil="[__('Accueil') => route('dashboard'), __('Contenu') => null, __('Biens') => null]">
        <x-slot:actions>
            <x-bascule-langue />
            @if ($peutEcrire)
                <a href="{{ route('admin.biens.creation') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <x-admin.icone nom="plus" />
                    {{ __('Ajouter un bien') }}
                </a>
            @endif
        </x-slot:actions>
    </x-admin.entete-page>

    @include('livewire.admin.partials.statistiques-de-bloc', ['statistiques' => $statistiques])

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            {{ $message }}
        </p>
    @endif

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Un titre, une référence, un quartier…') }}" class="{{ $classeChamp }}">

        <select wire:model.live="type" class="{{ $classeChamp }}">
            <option value="">{{ __('Tous les types') }}</option>
            @foreach ($types as $valeur)
                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
        </select>

        <select wire:model.live="offre" class="{{ $classeChamp }}">
            <option value="">{{ __('Toutes les offres') }}</option>
            @foreach ($offres as $cle => $intitule)
                <option value="{{ $cle }}">{{ $intitule }}</option>
            @endforeach
        </select>

        <select wire:model.live="zone" class="{{ $classeChamp }}">
            <option value="">{{ __('Toutes les zones') }}</option>
            @foreach ($zones as $valeur)
                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
        </select>

        <select wire:model.live="pieces" class="{{ $classeChamp }}">
            <option value="">{{ __('Toutes pièces') }}</option>
            @foreach ($tranchesPieces as $valeur)
                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
        </select>

        <select wire:model.live="surface" class="{{ $classeChamp }}">
            <option value="">{{ __('Toutes surfaces') }}</option>
            @foreach ($tranchesSurface as $valeur)
                <option value="{{ $valeur->valeur }}">{{ $valeur->libelle($langue) }}</option>
            @endforeach
        </select>

        <select wire:model.live="statut" class="{{ $classeChamp }}">
            <option value="">{{ __('Tous les statuts') }}</option>
            @foreach ($statuts as $cle => $intitule)
                <option value="{{ $cle }}">{{ $intitule }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <select wire:model.live="tri" class="{{ $classeChamp }}">
                <option value="recent">{{ __('Plus récent') }}</option>
                <option value="prix_croissant">{{ __('Prix croissant') }}</option>
                <option value="prix_decroissant">{{ __('Prix décroissant') }}</option>
                <option value="surface">{{ __('Surface') }}</option>
            </select>

            <button type="button" wire:click="reinitialiser"
                    class="shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700">
                {{ __('Réinitialiser') }}
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Bien') }}</th>
                    <th class="px-4 py-3">{{ __('Prix') }}</th>
                    <th class="px-4 py-3">{{ __('Type') }}</th>
                    <th class="px-4 py-3">{{ __('Offre') }}</th>
                    <th class="px-4 py-3">{{ __('Statut') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($biens as $bien)
                    <tr wire:key="bien-{{ $bien->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-3">
                                @if ($bien->photos->isNotEmpty())
                                    <img src="{{ asset($bien->photos->first()->fichier) }}" alt=""
                                         loading="lazy" class="h-11 w-16 shrink-0 rounded object-cover">
                                @else
                                    {{-- Les six biens repris du site n'ont pas de photo :
                                         l'illustration dessinee tient lieu de visuel, comme
                                         sur le site. --}}
                                    <span class="flex h-11 w-16 shrink-0 items-center justify-center rounded bg-zinc-100 dark:bg-zinc-800">
                                        <x-public.illustration-bien :type="$bien->type" class="h-7 w-10" />
                                    </span>
                                @endif

                                <span class="min-w-0">
                                    <a href="{{ route('admin.biens.edition', $bien) }}" wire:navigate
                                       class="block truncate font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $bien->titre($langue) }}
                                    </a>
                                    <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $bien->quartier }}@if ($bien->surface_habitable || $bien->surface_terrain) — {{ $bien->surface_habitable ?? $bien->surface_terrain }} m²@endif
                                    </span>
                                </span>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                            {{-- Le tiret plutot qu'un zero : l'agence annonce ses prix de
                                 vive voix, et « 0 FCFA » serait faux. --}}
                            {{ $bien->prixFormate() ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                            {{ $types->firstWhere('valeur', $bien->type)?->libelle($langue) ?? $bien->type }}
                        </td>

                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                            {{ $offres[$bien->offre] ?? $bien->offre }}
                        </td>

                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $bien->statut === \App\Models\Bien::PUBLIE,
                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $bien->statut === \App\Models\Bien::BROUILLON,
                                'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200' => $bien->statut === \App\Models\Bien::VENDU,
                                'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $bien->statut === \App\Models\Bien::ARCHIVE,
                            ])>{{ $statuts[$bien->statut] ?? $bien->statut }}</span>
                        </td>

                        <td class="px-4 py-3 text-end">
                            @if ($peutEcrire)
                                <div class="flex justify-end gap-3 text-xs">
                                    <a href="{{ route('admin.biens.edition', $bien) }}" wire:navigate
                                       class="text-zinc-600 hover:underline dark:text-zinc-300">{{ __('Modifier') }}</a>

                                    <button type="button" wire:click="supprimer({{ $bien->id }})"
                                            wire:confirm="{{ __('Supprimer « :titre » et ses photos ?', ['titre' => $bien->titre_fr]) }}"
                                            class="text-red-600 hover:underline">{{ __('Supprimer') }}</button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Aucun bien ne correspond à ces filtres.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $biens->links() }}
</div>
