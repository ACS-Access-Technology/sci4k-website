<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">{{ __('Articles') }}</h1>
        <div class="flex items-center gap-3">
            {{-- Le projet n'a que des roles, pas de permissions nommees : un
                 @can('…') serait toujours faux et le bouton ne paraitrait
                 jamais. Le test le verifie sur les deux roles. --}}
            @hasanyrole('administrateur|editeur')
                <a href="{{ route('admin.articles.creation') }}" wire:navigate
                   class="rounded bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                    {{ __('Nouvel article') }}
                </a>
            @endhasanyrole
            <x-bascule-langue />
        </div>
    </div>

    @if (session('message'))
        {{-- Sans ce bloc, l'editeur enregistre, se fait rediriger, et rien ne
             distingue une reussite d'un clic sans effet. --}}
        <div role="status"
             class="rounded border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-100">
            {{ session('message') }}
        </div>
    @endif

    <div class="flex flex-wrap gap-3">
        <label class="sr-only" for="recherche">{{ __('Rechercher') }}</label>
        <input type="search" id="recherche"
               wire:model.live.debounce.300ms="recherche"
               placeholder="{{ __('Rechercher un titre…') }}"
               class="rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">

        <label class="sr-only" for="categorie">{{ __('Catégorie') }}</label>
        <select id="categorie" wire:model.live="categorieId"
                class="rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">{{ __('Toutes les catégories') }}</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}">{{ $c->nom($langue) }}</option>
            @endforeach
        </select>

        <label class="sr-only" for="statut">{{ __('Statut') }}</label>
        <select id="statut" wire:model.live="statut"
                class="rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900">
            <option value="">{{ __('Tous les statuts') }}</option>
            <option value="publie">{{ __('Publié') }}</option>
            <option value="brouillon">{{ __('Brouillon') }}</option>
        </select>
    </div>

    {{-- Le tableau deborde sur mobile : il defile dans son propre conteneur,
         pour que la page elle-meme ne defile jamais horizontalement. --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase text-zinc-500">
                <tr>
                    <th class="py-2">{{ __('Titre') }}</th>
                    <th>{{ __('Catégorie') }}</th>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Statut') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($articles as $article)
                    <tr class="border-t border-zinc-200 dark:border-zinc-700">
                        <td class="py-2 font-medium">
                            @hasanyrole('administrateur|editeur')
                                <a href="{{ route('admin.articles.edition', $article) }}" wire:navigate class="hover:underline">
                                    {{ $article->titre($langue) }}
                                </a>
                            @else
                                {{ $article->titre($langue) }}
                            @endhasanyrole
                        </td>
                        <td>{{ $article->categorie->nom($langue) }}</td>
                        <td>{{ $article->date_publication->format('d/m/Y') }}</td>
                        <td>
                            <span class="rounded px-2 py-1 text-xs {{ $article->statut === 'publie' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100' }}">
                                {{ $article->statut === 'publie' ? __('Publié') : __('Brouillon') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-zinc-500">
                            {{ __('Aucun article pour le moment.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $articles->links() }}
</div>
