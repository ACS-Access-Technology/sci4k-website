@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')

<div class="space-y-6">

    {{-- L'ecran ouvre sur les commentaires MIS DE COTE : ce sont les seuls qui
         reclament une decision, les autres etant deja en ligne. --}}
    @if ($enAttente > 0)
        <p class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200" role="status">
            {{ trans_choice(
                ':nombre commentaire attend une vérification.|:nombre commentaires attendent une vérification.',
                $enAttente,
                ['nombre' => $enAttente],
            ) }}
        </p>
    @endif

    @if ($message)
        <p class="rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-900 dark:border-green-700 dark:bg-green-950 dark:text-green-200" role="status">
            {{ $message }}
        </p>
    @endif

    <x-admin.barre-filtres>
        <x-admin.champ-filtre :intitule="__('Rechercher')" pour="recherche-commentaire">
            <input type="search" id="recherche-commentaire" wire:model.live.debounce.300ms="recherche"
                   placeholder="{{ __('Un nom, une adresse, un mot du message…') }}" class="{{ $champ }}">
        </x-admin.champ-filtre>

        <x-admin.champ-filtre :intitule="__('Statut')" pour="statut-commentaire">
            <select id="statut-commentaire" wire:model.live="statut" class="{{ $champ }}">
                <option value="">{{ __('Tous') }}</option>
                @foreach ($statuts as $cle => $intitule)
                    <option value="{{ $cle }}">{{ $intitule }}</option>
                @endforeach
            </select>
        </x-admin.champ-filtre>
    </x-admin.barre-filtres>

    <div class="space-y-3">
        @forelse ($commentaires as $commentaire)
            <article wire:key="commentaire-{{ $commentaire->id }}"
                     class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">
                            {{ $commentaire->auteur }}
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">— {{ $commentaire->email }}</span>
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $commentaire->depuis() }}
                            @if ($commentaire->article)
                                · {{ $commentaire->article->titre($langue) }}
                            @endif
                            @if ($commentaire->parent)
                                · {{ __('En réponse à :nom', ['nom' => $commentaire->parent->auteur]) }}
                            @endif
                        </p>
                    </div>

                    <span @class([
                        'shrink-0 rounded-md px-2.5 py-1 text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200' => $commentaire->statut === \App\Models\Commentaire::PUBLIE,
                        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' => $commentaire->statut === \App\Models\Commentaire::EN_ATTENTE,
                        'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' => $commentaire->statut === \App\Models\Commentaire::REJETE,
                    ])>{{ $commentaire->statutLisible() }}</span>
                </div>

                {{-- Le motif dit POURQUOI le filtre a mis ce message de cote :
                     « en attente » sans raison ne dit pas a l'editeur s'il doit
                     s'inquieter ou approuver d'un clic. --}}
                @if ($commentaire->motif_de_mise_en_attente)
                    <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-400">
                        {{ __('Mis de côté :') }} {{ $commentaire->motif_de_mise_en_attente }}
                    </p>
                @endif

                <p class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $commentaire->message }}</p>

                @if ($peutModerer)
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        @if ($commentaire->statut !== \App\Models\Commentaire::PUBLIE)
                            <button type="button" wire:click="changerLeStatut({{ $commentaire->id }}, 'publie')"
                                    class="font-medium text-green-700 hover:underline dark:text-green-400">
                                {{ __('Publier') }}
                            </button>
                        @endif

                        @if ($commentaire->statut !== \App\Models\Commentaire::REJETE)
                            <button type="button" wire:click="changerLeStatut({{ $commentaire->id }}, 'rejete')"
                                    class="font-medium text-amber-700 hover:underline dark:text-amber-400">
                                {{ __('Retirer du site') }}
                            </button>
                        @endif

                        <button type="button"
                                wire:click="supprimer({{ $commentaire->id }})"
                                wire:confirm="{{ __('Supprimer définitivement ce commentaire et ses réponses ? Cette action est irréversible.') }}"
                                class="font-medium text-red-600 hover:underline">
                            {{ __('Supprimer') }}
                        </button>

                        @if ($commentaire->article)
                            <a href="{{ route('actualites.detail', $commentaire->article) }}#commentaires"
                               target="_blank" rel="noopener"
                               class="ms-auto text-zinc-500 hover:underline dark:text-zinc-400">
                                {{ __('Voir sur le site') }} →
                            </a>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-600 dark:border-zinc-600 dark:text-zinc-400">
                {{ __('Aucun commentaire ne correspond à ce filtre.') }}
            </p>
        @endforelse
    </div>

    {{ $commentaires->links() }}
</div>
