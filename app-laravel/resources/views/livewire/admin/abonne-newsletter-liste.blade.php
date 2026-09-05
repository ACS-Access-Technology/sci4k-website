{{--
  Abonnes a la lettre d'information.

  Rien a modifier chez un abonne : il n'a saisi qu'une adresse. On peut le
  desinscrire, jamais l'effacer — la trace du retrait est ce qui empeche de le
  reinscrire par erreur.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Abonnés newsletter')"
        :fil="[__('Accueil') => route('dashboard'), __('Demandes') => null, __('Newsletter') => null]">
        <x-slot:actions>
            @if ($peutEcrire)
                <button type="button" wire:click="exporter"
                        class="rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                    {{ __('Exporter en CSV') }}
                </button>
            @endif
        </x-slot:actions>
    </x-admin.entete-page>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Adresses recueillies par le champ du pied de page.') }}
    </p>

    @include('livewire.admin.partials.statistiques-de-bloc', ['statistiques' => $statistiques])

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            {{ $message }}
        </p>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Une adresse…') }}" class="{{ $classeChamp }} sm:max-w-xs">

        <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
            <input type="checkbox" wire:model.live="avecDesinscrits" class="size-4 rounded border-zinc-300 dark:border-zinc-600">
            {{ __('Afficher les désinscrits') }}
        </label>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Adresse') }}</th>
                    <th class="px-4 py-3">{{ __('Inscrite le') }}</th>
                    <th class="px-4 py-3">{{ __('État') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($abonnes as $abonne)
                    <tr wire:key="abonne-{{ $abonne->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">{{ $abonne->email }}</td>

                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            <time datetime="{{ $abonne->created_at?->toIso8601String() }}">
                                {{ $abonne->created_at?->translatedFormat('d F Y') }}
                            </time>
                        </td>

                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => ! $abonne->estDesinscrit(),
                                'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $abonne->estDesinscrit(),
                            ])>
                                {{ $abonne->estDesinscrit() ? __('Désinscrit') : __('Actif') }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-end">
                            @if ($peutEcrire)
                                <button type="button" wire:click="basculerLAbonnement({{ $abonne->id }})"
                                        class="text-xs text-zinc-600 hover:underline dark:text-zinc-300">
                                    {{ $abonne->estDesinscrit() ? __('Réinscrire') : __('Désinscrire') }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Aucune adresse inscrite pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{-- LES TEXTES DE LA PAGE DE DESINSCRIPTION

         Cette page est servie par le site, hors des sept pages editables :
         c'est ici, sur l'ecran qui gouverne la lettre d'information, que ses
         mots se changent. La chercher ailleurs n'aurait eu aucun sens. --}}
    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div class="mb-4 flex flex-wrap items-baseline justify-between gap-3">
            <div>
                <h2 class="text-sm font-semibold">{{ __('Page de désinscription') }}</h2>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Ce que lit l’abonné qui suit le lien de retrait posé dans vos envois.') }}
                </p>
            </div>

            {{-- « Français » et « English » restent ecrits dans leur propre
                 langue : ce sont des endonymes. --}}
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

        <form wire:submit="enregistrerLesTextes" class="space-y-4">
            @include('livewire.admin.partials.textes-du-module', [
                'legendeDesTextes' => __('Textes de la page'),
            ])

            @if ($peutEcrire)
                <button type="submit"
                        class="rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                    {{ __('Enregistrer') }}
                </button>
            @endif
        </form>
    </section>
</div>
