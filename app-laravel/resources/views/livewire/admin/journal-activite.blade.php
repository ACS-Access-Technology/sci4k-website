{{--
  Journal complet des actions faites depuis l'administration.

  Lecture seule : un journal qu'on peut effacer ne sert a rien.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Journal des activités')"
        :fil="[__('Accueil') => route('dashboard'), __('Journal') => null]" />

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Qui a fait quoi, et quand. Cette page ne se modifie pas.') }}
    </p>

    <div class="flex flex-wrap gap-3">
        <select wire:model.live="action" class="{{ $classeChamp }} sm:max-w-56">
            <option value="">{{ __('Toutes les actions') }}</option>
            @foreach ($actions as $valeur => $intitule)
                <option value="{{ $valeur }}">{{ $intitule }}</option>
            @endforeach
        </select>

        <select wire:model.live="auteur" class="{{ $classeChamp }} sm:max-w-56">
            <option value="">{{ __('Tous les auteurs') }}</option>
            @foreach ($auteurs as $compte)
                <option value="{{ $compte->id }}">{{ $compte->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <tr>
                    {{-- L'auteur vient EN PREMIER : c'est de lui que ce journal
                         parle. Le contenu touche n'est que le complement. --}}
                    <th class="px-4 py-3">{{ __('Auteur') }}</th>
                    <th class="px-4 py-3">{{ __('Action') }}</th>
                    <th class="px-4 py-3">{{ __('Contenu') }}</th>
                    <th class="px-4 py-3">{{ __('Quand') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lignes as $ligne)
                    @php($lien = $ligne->lienDEdition())

                    <tr class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-2">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ $ligne->initialesDeLAuteur() }}
                                </span>
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $ligne->nomDeLAuteur() }}</span>
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{-- Le mot porte l'information ; la couleur ne fait que
                                 l'appuyer. Une pastille seule laisse un lecteur
                                 daltonien deviner. --}}
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $ligne->action === \App\Models\ActiviteJournalisee::PUBLICATION,
                                'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' => $ligne->action === \App\Models\ActiviteJournalisee::SUPPRESSION,
                                'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200' => $ligne->action === \App\Models\ActiviteJournalisee::CREATION,
                                'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $ligne->action === \App\Models\ActiviteJournalisee::MODIFICATION,
                            ])>{{ ucfirst($ligne->verbe()) }}</span>
                        </td>

                        <td class="px-4 py-3">
                            @if ($lien)
                                <a href="{{ $lien }}" wire:navigate class="text-zinc-900 hover:underline dark:text-white">
                                    {{ $ligne->sujet_intitule }}
                                </a>
                            @else
                                <span class="text-zinc-500 line-through dark:text-zinc-400">{{ $ligne->sujet_intitule }}</span>
                            @endif
                            <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $ligne->famille() }}</span>
                        </td>

                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            <time datetime="{{ $ligne->created_at?->toIso8601String() }}"
                                  title="{{ $ligne->created_at?->translatedFormat('d F Y à H:i') }}">
                                {{ $ligne->created_at?->diffForHumans() }}
                            </time>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Aucune activité enregistrée pour ce filtre.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $lignes->links() }}
</div>
