{{--
  Messages recus par le formulaire de contact.

  Liste a gauche, message ouvert a droite. Le « bien concerne » de la maquette
  n'y figure pas : il designe une fiche de bien, et les biens sont le lot 3.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Messages de contact')"
        :fil="[__('Accueil') => route('dashboard'), __('Demandes') => null, __('Messages') => null]" />

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ __('Formulaire de la page Contact') }}
    </p>

    @include('livewire.admin.partials.statistiques-de-bloc', ['statistiques' => $statistiques])

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            {{ $message }}
        </p>
    @endif

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Un nom, un sujet, un mot du message…') }}" class="{{ $classeChamp }} sm:max-w-xs">

        <select wire:model.live="filtre" class="{{ $classeChamp }} sm:max-w-48">
            <option value="">{{ __('Tous') }}</option>
            @foreach ($statuts as $valeur => $intitule)
                <option value="{{ $valeur }}">{{ $intitule }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid gap-4 lg:grid-cols-5">

        {{-- La liste --}}
        <div class="lg:col-span-2 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($messages as $ligne)
                    <li wire:key="message-{{ $ligne->id }}">
                        <button type="button" wire:click="ouvrir({{ $ligne->id }})"
                                @class([
                                    'flex w-full items-start gap-3 px-4 py-3 text-left transition',
                                    'bg-zinc-100 dark:bg-zinc-800' => $ouvert === $ligne->id,
                                    'hover:bg-zinc-50 dark:hover:bg-zinc-900' => $ouvert !== $ligne->id,
                                ])>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                                {{ $ligne->initiales() }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex items-baseline justify-between gap-2">
                                    <span @class([
                                        'truncate text-sm text-zinc-900 dark:text-white',
                                        'font-semibold' => $ligne->statut === \App\Models\MessageDeContact::NOUVEAU,
                                        'font-medium' => $ligne->statut !== \App\Models\MessageDeContact::NOUVEAU,
                                    ])>{{ $ligne->nom }}</span>
                                    <span class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400">{{ $ligne->created_at->diffForHumans(short: true) }}</span>
                                </span>

                                <span class="block truncate text-xs text-zinc-600 dark:text-zinc-300">{{ $ligne->intitule() }}</span>

                                <span @class([
                                    'mt-1 inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200' => $ligne->statut === \App\Models\MessageDeContact::NOUVEAU,
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $ligne->statut === \App\Models\MessageDeContact::EN_COURS,
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $ligne->statut === \App\Models\MessageDeContact::TRAITE,
                                    'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $ligne->statut === \App\Models\MessageDeContact::ARCHIVE,
                                ])>{{ $statuts[$ligne->statut] ?? $ligne->statut }}</span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="px-4 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Aucun message pour le moment.') }}
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Le message ouvert --}}
        <div class="lg:col-span-3 rounded-xl border border-zinc-200 dark:border-zinc-700">
            @if ($ouvertement)
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $ouvertement->intitule() }}</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Reçu :quand via le formulaire de contact', ['quand' => $ouvertement->created_at->diffForHumans()]) }}
                    </p>
                </div>

                <dl class="grid gap-3 border-b border-zinc-200 px-5 py-4 text-sm sm:grid-cols-2 dark:border-zinc-700">
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Expéditeur') }}</dt>
                        <dd class="text-zinc-900 dark:text-white">{{ $ouvertement->nom }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Statut') }}</dt>
                        <dd>
                            <select wire:change="changerLeStatut({{ $ouvertement->id }}, $event.target.value)"
                                    @disabled(! $peutEcrire)
                                    class="mt-1 rounded-lg border border-zinc-300 px-2 py-1 text-xs disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950">
                                @foreach ($statuts as $valeur => $intitule)
                                    <option value="{{ $valeur }}" @selected($ouvertement->statut === $valeur)>{{ $intitule }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Téléphone') }}</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            @if ($ouvertement->telephone)
                                <a href="tel:{{ $ouvertement->telephone }}" class="hover:underline">{{ $ouvertement->telephone }}</a>
                            @else
                                <span class="text-zinc-500">{{ __('Non renseigné') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('E-mail') }}</dt>
                        <dd class="text-zinc-900 dark:text-white">
                            @if ($ouvertement->email)
                                <a href="mailto:{{ $ouvertement->email }}" class="hover:underline">{{ $ouvertement->email }}</a>
                            @else
                                <span class="text-zinc-500">{{ __('Non renseigné') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Confié à') }}</dt>
                        <dd>
                            <select wire:change="assigner({{ $ouvertement->id }}, $event.target.value)"
                                    @disabled(! $peutEcrire)
                                    class="mt-1 rounded-lg border border-zinc-300 px-2 py-1 text-xs disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950">
                                <option value="" @selected(! $ouvertement->assigne_a)>{{ __('Personne') }}</option>
                                @foreach ($collaborateurs as $collaborateur)
                                    <option value="{{ $collaborateur->id }}" @selected($ouvertement->assigne_a === $collaborateur->id)>{{ $collaborateur->name }}</option>
                                @endforeach
                            </select>
                        </dd>
                    </div>
                </dl>

                {{-- Le corps du message est ECHAPPE : il vient d'un visiteur
                     inconnu, et c'est le seul texte de tout le backoffice qui
                     n'ait ete ecrit par personne de l'agence. --}}
                <div class="whitespace-pre-line border-b border-zinc-200 px-5 py-4 text-sm text-zinc-800 dark:border-zinc-700 dark:text-zinc-200">{{ $ouvertement->message }}</div>

                @if ($peutEcrire)
                    <form wire:submit="repondre" class="space-y-3 px-5 py-4">
                        <label class="block">
                            <span class="text-sm font-medium">{{ __('Réponse') }}</span>
                            <textarea wire:model="reponse" rows="5" class="{{ $classeChamp }} mt-1"
                                      placeholder="{{ __('Votre réponse au visiteur…') }}"></textarea>
                            @error('reponse') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" @disabled(! $ouvertement->email)
                                    class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 disabled:opacity-50 dark:bg-white dark:text-zinc-900">
                                {{ __('Envoyer la réponse') }}
                            </button>

                            <button type="button" wire:click="changerLeStatut({{ $ouvertement->id }}, '{{ \App\Models\MessageDeContact::TRAITE }}')"
                                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">
                                {{ __('Marquer comme traité') }}
                            </button>

                            <button type="button" wire:click="supprimer({{ $ouvertement->id }})"
                                    wire:confirm="{{ __('Supprimer définitivement ce message ?') }}"
                                    class="rounded-lg px-4 py-2 text-sm font-medium text-red-600 hover:underline">
                                {{ __('Supprimer') }}
                            </button>
                        </div>

                        @unless ($ouvertement->email)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __("Ce visiteur n'a pas laissé d'adresse e-mail : répondez-lui par téléphone.") }}
                            </p>
                        @endunless
                    </form>
                @endif
            @else
                <p class="px-5 py-16 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Choisissez un message pour le lire.') }}
                </p>
            @endif
        </div>
    </div>
</div>
