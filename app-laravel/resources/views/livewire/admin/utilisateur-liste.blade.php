{{--
  Comptes du backoffice.

  Les descriptions de roles viennent du MODELE et non d'une liste ecrite ici :
  une description qui vit loin de la regle qu'elle decrit finit par la
  contredire.
--}}
@php($classeChamp = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    <x-admin.entete-page
        :titre="__('Utilisateurs')"
        :fil="[__('Accueil') => route('dashboard'), __('Réglages') => null, __('Utilisateurs') => null]">
        <x-slot:actions>
            <button type="button" wire:click="ouvrirInvitation"
                    class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                <x-admin.icone nom="plus" />
                {{ __('Inviter un utilisateur') }}
            </button>
        </x-slot:actions>
    </x-admin.entete-page>

    <p class="text-sm text-zinc-500 dark:text-zinc-400">
        {{ trans_choice(':nombre compte|:nombre comptes', $total, ['nombre' => $total]) }},
        {{ trans_choice(':nombre actif|:nombre actifs', $actifs, ['nombre' => $actifs]) }}
    </p>

    @if ($message)
        <p class="rounded-lg border border-zinc-300 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            {{ $message }}
        </p>
    @endif

    {{-- Panneau d'invitation. Aucun champ de mot de passe : la personne invitee
         choisit le sien en suivant le lien recu. --}}
    @if ($panneauInvitation)
        <form wire:submit="inviter" class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Inviter un utilisateur') }}</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('La personne recevra un lien pour choisir elle-même son mot de passe. Vous ne le verrez jamais.') }}
            </p>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <label class="block">
                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Nom') }}</span>
                    <input type="text" wire:model="nomInvite" class="{{ $classeChamp }} mt-1">
                    @error('nomInvite') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Adresse e-mail') }}</span>
                    <input type="email" wire:model="emailInvite" class="{{ $classeChamp }} mt-1">
                    @error('emailInvite') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block">
                    <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Rôle') }}</span>
                    <select wire:model="roleInvite" class="{{ $classeChamp }} mt-1">
                        @foreach ($roles as $nom => $description)
                            <option value="{{ $nom }}">{{ ucfirst($nom) }}</option>
                        @endforeach
                    </select>
                    @error('roleInvite') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900">
                    {{ __("Envoyer l'invitation") }}
                </button>
                <button type="button" wire:click="$set('panneauInvitation', false)"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-600">
                    {{ __('Annuler') }}
                </button>
            </div>
        </form>
    @endif

    <div class="flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Un nom, une adresse…') }}" class="{{ $classeChamp }} sm:max-w-xs">

        <select wire:model.live="roleFiltre" class="{{ $classeChamp }} sm:max-w-48">
            <option value="">{{ __('Tous les rôles') }}</option>
            @foreach ($roles as $nom => $description)
                <option value="{{ $nom }}">{{ ucfirst($nom) }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-zinc-200 text-xs uppercase tracking-wide text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                <tr>
                    <th class="px-4 py-3">{{ __('Utilisateur') }}</th>
                    <th class="px-4 py-3">{{ __('Rôle') }}</th>
                    <th class="px-4 py-3">{{ __('Statut') }}</th>
                    <th class="px-4 py-3">{{ __('Dernière connexion') }}</th>
                    <th class="px-4 py-3 text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($comptes as $compte)
                    <tr wire:key="compte-{{ $compte->id }}" class="border-b border-zinc-100 last:border-0 dark:border-zinc-800">
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-3">
                                {{-- Les initiales etaient recalculees ici, d'une
                                     facon differente de User::initials(). Deux
                                     regles pour la meme vignette : le composant
                                     n'en porte plus qu'une, et il montre la
                                     photo quand le compte en a une. --}}
                                <x-admin.vignette-compte :compte="$compte" taille="size-9" />
                                <span>
                                    <span class="block font-medium text-zinc-900 dark:text-white">
                                        {{ $compte->name }}
                                        @if ($compte->id === $moiMeme)
                                            <span class="text-xs font-normal text-zinc-500">{{ __('(vous)') }}</span>
                                        @endif
                                    </span>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $compte->email }}</span>
                                </span>
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{-- L'option « aucun » n'est pas decorative : sans
                                 elle, un compte SANS role affichait le premier
                                 de la liste — « Administrateur ». Le navigateur
                                 selectionne la premiere option quand aucune ne
                                 correspond, et l'ecran des droits annoncait
                                 alors le droit le plus large a qui n'en a
                                 aucun. --}}
                            @php($roleActuel = $compte->roles->pluck('name')->first())

                            <select wire:change="changerLeRole({{ $compte->id }}, $event.target.value)"
                                    @disabled($compte->id === $moiMeme)
                                    class="rounded-lg border border-zinc-300 px-2 py-1 text-xs disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-950">
                                @if (! $roleActuel)
                                    <option value="" selected>{{ __('Aucun rôle') }}</option>
                                @endif
                                @foreach ($roles as $nom => $description)
                                    <option value="{{ $nom }}" @selected($roleActuel === $nom)>{{ ucfirst($nom) }}</option>
                                @endforeach
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            {{-- L'etat se lit a la forme autant qu'a la couleur : une
                                 pastille seule laisse un lecteur daltonien deviner. --}}
                            <span @class([
                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $compte->statut === \App\Models\User::ACTIF,
                                'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' => $compte->statut === \App\Models\User::INACTIF,
                                'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $compte->statut === \App\Models\User::INVITE,
                            ])>
                                {{ match ($compte->statut) {
                                    \App\Models\User::ACTIF => __('Actif'),
                                    \App\Models\User::INACTIF => __('Inactif'),
                                    default => __('Invitation envoyée'),
                                } }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">
                            {{ $compte->derniere_connexion_a?->diffForHumans() ?? __('Jamais') }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3 text-xs">
                                @if ($compte->statut === \App\Models\User::INVITE)
                                    <button type="button" wire:click="renvoyerLInvitation({{ $compte->id }})"
                                            class="text-zinc-600 hover:underline dark:text-zinc-300">{{ __("Renvoyer l'invitation") }}</button>
                                @endif

                                @if ($compte->id !== $moiMeme)
                                    <button type="button" wire:click="basculerLActivation({{ $compte->id }})"
                                            class="text-zinc-600 hover:underline dark:text-zinc-300">
                                        {{ $compte->statut === \App\Models\User::INACTIF ? __('Réactiver') : __('Désactiver') }}
                                    </button>

                                    <button type="button" wire:click="supprimer({{ $compte->id }})"
                                            wire:confirm="{{ __('Supprimer le compte de :nom ? Ses articles restent en ligne.', ['nom' => $compte->name]) }}"
                                            class="text-red-600 hover:underline">{{ __('Supprimer') }}</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                            {{ __('Aucun compte ne correspond à votre recherche.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Ce que chaque role autorise. Le texte vient du modele, a cote de la
         regle qu'il decrit. --}}
    <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Rôles et permissions') }}</h2>
        </div>
        <dl class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($roles as $nom => $description)
                <div class="grid gap-1 px-4 py-3 sm:grid-cols-[10rem_1fr]">
                    <dt class="text-sm font-medium text-zinc-900 dark:text-white">{{ ucfirst($nom) }}</dt>
                    <dd class="text-sm text-zinc-600 dark:text-zinc-300">{{ $description }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
</div>
