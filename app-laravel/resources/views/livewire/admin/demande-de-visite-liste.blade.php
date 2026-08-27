{{--
  Rendez-vous demandes depuis les fiches de biens.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Demandes de visite')"
        :fil="[__('Accueil') => route('dashboard'), __('Demandes') => null, __('Visites') => null]" />

    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Rendez-vous demandés depuis les fiches de biens') }}</p>

    @include('livewire.admin.partials.statistiques-de-bloc', ['statistiques' => $statistiques])

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">{{ $message }}</p>
    @endif

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Un nom, un téléphone, un bien…') }}" class="{{ $classeChamp }} sm:max-w-xs">

        <select wire:model.live="statut" class="{{ $classeChamp }} sm:max-w-48">
            <option value="">{{ __('Tous les statuts') }}</option>
            @foreach ($statuts as $cle => $intitule)
                <option value="{{ $cle }}">{{ $intitule }}</option>
            @endforeach
        </select>

        <select wire:model.live="assigne" class="{{ $classeChamp }} sm:max-w-48">
            <option value="">{{ __('Tous les collaborateurs') }}</option>
            @foreach ($collaborateurs as $collaborateur)
                <option value="{{ $collaborateur->id }}">{{ $collaborateur->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Demandeur') }}</th>
                    <th class="px-4 py-3">{{ __('Bien concerné') }}</th>
                    <th class="px-4 py-3">{{ __('Créneau souhaité') }}</th>
                    <th class="px-4 py-3">{{ __('Statut') }}</th>
                    <th class="px-4 py-3">{{ __('Confié à') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($demandes as $demande)
                    <tr wire:key="visite-{{ $demande->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-3">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ $demande->initiales() }}
                                </span>
                                <span>
                                    <span class="block font-medium text-zinc-900 dark:text-white">{{ $demande->nom }}</span>
                                    <a href="tel:{{ $demande->telephone }}" class="block text-xs text-zinc-500 hover:underline dark:text-zinc-400">{{ $demande->telephone }}</a>
                                </span>
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            @if ($demande->bien)
                                <a href="{{ route('admin.biens.edition', $demande->bien) }}" wire:navigate
                                   class="text-zinc-900 hover:underline dark:text-white">{{ $demande->bienLisible($langue) }}</a>
                            @elseif ($demande->bien_intitule)
                                {{-- Le titre recopie survit au retrait du bien : une
                                     demande de visite garde son sens apres la vente. --}}
                                <span class="text-zinc-500 dark:text-zinc-400">{{ $demande->bien_intitule }}</span>
                                <span class="block text-xs text-zinc-500">{{ __('Retiré du catalogue') }}</span>
                            @else
                                <span class="text-zinc-500">{{ __('Non précisé') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                            @if ($demande->creneau_souhaite)
                                <time datetime="{{ $demande->creneau_souhaite->toIso8601String() }}">
                                    {{ $demande->creneau_souhaite->translatedFormat('D d/m — H:i') }}
                                </time>
                            @else
                                <span class="text-zinc-500">{{ __('À convenir') }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <select wire:change="changerLeStatut({{ $demande->id }}, $event.target.value)"
                                    @disabled(! $peutEcrire)
                                    class="rounded-lg border border-zinc-300 px-2 py-1 text-xs disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950">
                                @foreach ($statuts as $cle => $intitule)
                                    <option value="{{ $cle }}" @selected($demande->statut === $cle)>{{ $intitule }}</option>
                                @endforeach
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <select wire:change="assigner({{ $demande->id }}, $event.target.value)"
                                    @disabled(! $peutEcrire)
                                    class="rounded-lg border border-zinc-300 px-2 py-1 text-xs disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="" @selected(! $demande->assigne_a)>{{ __('Personne') }}</option>
                                @foreach ($collaborateurs as $collaborateur)
                                    <option value="{{ $collaborateur->id }}" @selected($demande->assigne_a === $collaborateur->id)>{{ $collaborateur->name }}</option>
                                @endforeach
                            </select>
                        </td>

                        <td class="px-4 py-3 text-end">
                            @if ($peutEcrire)
                                <button type="button" wire:click="supprimer({{ $demande->id }})"
                                        wire:confirm="{{ __('Supprimer définitivement cette demande ?') }}"
                                        class="text-xs text-red-600 hover:underline">{{ __('Supprimer') }}</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Aucune demande de visite pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $demandes->links() }}
</div>
