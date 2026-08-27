@php($tons = [
    'primaire' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    'succes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    'info' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    'alerte' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
])
@php($tonsEcheance = [
    'urgent' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    'proche' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    'lointain' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
])
@php($champ = 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950')
{{-- Pose en variable plutôt qu'en ligne dans l'attribut : la chaîne contient
     deux apostrophes, que l'analyseur d'attributs de Blade ne sait pas
     échapper proprement. --}}
@php($resumeDuBord = __("Vue d'ensemble de l'activité du site SCI4K"))

<div class="space-y-4">

    <x-admin.entete-page
        :titre="__('Tableau de bord')"
        :fil="[__('Accueil') => null]"
        :resume="$resumeDuBord">
        <x-slot:actions>
            <a href="{{ route('home') }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('Voir le site') }}
            </a>
            @if ($peutEcrire)
                <a href="{{ route('admin.articles.creation') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-zinc-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                    <x-admin.icone nom="plus" />
                    {{ __('Nouvel article') }}
                </a>
            @endif
        </x-slot:actions>
    </x-admin.entete-page>

    {{-- Quatre tuiles, comme la maquette. La variation compare le nombre
         d'éléments CRÉÉS ce mois à celui du mois précédent — la seule chose
         que les dates en base permettent de mesurer, aucun historique des
         totaux n'étant conservé. Elle disparaît quand la comparaison n'a pas
         de sens : sans création le mois dernier, toute hausse vaudrait
         « +∞ % ». --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tuiles as $tuile)
            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <span class="flex size-10 items-center justify-center rounded-lg {{ $tons[$tuile['ton']] }}">
                    <x-admin.icone :nom="$tuile['icone']" />
                </span>

                <p class="mt-4 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $tuile['valeur'] }}</p>

                <div class="mt-1 flex items-baseline justify-between gap-2">
                    <p class="truncate text-sm text-zinc-600 dark:text-zinc-400">
                        @if ($tuile['route'])
                            <a href="{{ route($tuile['route']) }}" wire:navigate class="hover:underline">{{ $tuile['intitule'] }}</a>
                        @else
                            {{ $tuile['intitule'] }}
                        @endif
                    </p>

                    @if ($tuile['variation'])
                        <span @class([
                            'shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' => $tuile['variation']['sens'] === 'hausse',
                            'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' => $tuile['variation']['sens'] === 'baisse',
                        ]) title="{{ __('Créations ce mois-ci, comparées au mois précédent') }}">
                            {{ $tuile['variation']['sens'] === 'hausse' ? '↑' : '↓' }} {{ $tuile['variation']['pourcentage'] }} %
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        {{-- La maquette montre ici la « répartition des biens ». Le catalogue
             est au lot 3 : l'emplacement sert la répartition du contenu qui
             existe, plutôt qu'un graphique sans données derrière. --}}
        <div class="rounded-xl border border-zinc-200 lg:col-span-2 dark:border-zinc-700">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Répartition du contenu') }}</h2>
            </div>

            <div class="space-y-3 p-5">
                @foreach ($repartition as $ligne)
                    <div>
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            @if ($ligne['route'])
                                <a href="{{ route($ligne['route']) }}" wire:navigate
                                   class="truncate text-zinc-700 hover:underline dark:text-zinc-300">{{ $ligne['intitule'] }}</a>
                            @else
                                <span class="truncate text-zinc-700 dark:text-zinc-300">{{ $ligne['intitule'] }}</span>
                            @endif
                            <span class="shrink-0 font-medium text-zinc-900 dark:text-white">{{ $ligne['total'] }}</span>
                        </div>

                        {{-- Les barres se lisent les unes par rapport aux autres :
                             le maximum donne l'échelle. --}}
                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-zinc-400 dark:bg-zinc-500"
                                 style="width: {{ max($ligne['part'], 2) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tâches SAISIES, avec case et échéance comme la maquette. Elles
             complètent le panneau « À traiter » plus bas, qui lui est déduit :
             « 3 articles en brouillon » se déduit et se périme tout seul,
             « rappeler le notaire jeudi » se saisit et ne se devine pas. --}}
        <div class="flex flex-col rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Tâches prioritaires') }}</h2>
                @if ($taches->where('terminee', false)->count() > 0)
                    <span class="rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $taches->where('terminee', false)->count() }}
                    </span>
                @endif
            </div>

            <ul class="flex-1 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($taches as $tache)
                    @php($echeance = $tache->echeanceLisible())
                    <li class="flex items-start gap-3 px-5 py-3">
                        <input type="checkbox" wire:click="basculerTache({{ $tache->id }})"
                               @checked($tache->terminee) @disabled(! $peutEcrire)
                               class="mt-0.5 rounded border-zinc-300"
                               aria-label="{{ __('Marquer comme faite') }}">

                        <span @class([
                            'min-w-0 flex-1 text-sm',
                            'text-zinc-400 line-through dark:text-zinc-500' => $tache->terminee,
                            'text-zinc-800 dark:text-zinc-100' => ! $tache->terminee,
                        ])>{{ $tache->texte }}</span>

                        @if ($echeance && ! $tache->terminee)
                            <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium {{ $tonsEcheance[$echeance['ton']] }}">
                                {{ $echeance['texte'] }}
                            </span>
                        @endif

                        @if ($peutEcrire)
                            <button type="button" wire:click="supprimerTache({{ $tache->id }})"
                                    title="{{ __('Supprimer') }}"
                                    class="shrink-0 rounded-md p-1 text-zinc-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-400">
                                <x-admin.icone nom="corbeille" />
                            </button>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Aucune tâche.') }}
                    </li>
                @endforelse
            </ul>

            @if ($peutEcrire)
                <div class="border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="flex gap-2">
                        <input type="text" wire:model="nouvelleTache" wire:keydown.enter="ajouterTache"
                               placeholder="{{ __('Une tâche à ne pas oublier…') }}" class="{{ $champ }}">
                        <input type="date" wire:model="nouvelleEcheance" class="{{ $champ }} w-auto"
                               aria-label="{{ __('Échéance') }}">
                        <button type="button" wire:click="ajouterTache"
                                title="{{ __('Ajouter la tâche') }}"
                                class="shrink-0 rounded-lg bg-zinc-900 px-3 text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
                            <x-admin.icone nom="plus" />
                        </button>
                    </div>
                    @error('nouvelleTache') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    @error('nouvelleEcheance') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Activité récente') }}</h2>
            </div>

            {{-- Chaque ligne dit QUI a fait QUOI, et pas seulement ce qui a
                 bouge : la version precedente deduisait l'activite du champ
                 `updated_at` et ne pouvait ni nommer l'action, ni son auteur.
                 Un element supprime reste lisible ici — c'est meme le cas ou
                 le journal sert le plus. --}}
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($recents as $recent)
                    @php($lien = $recent->lienDEdition())

                    <li class="flex items-center gap-3 px-5 py-3">
                        {{-- La vignette porte les initiales du COMPTE qui a agi,
                             et non l'icone de la famille touchee. Le premier jet
                             mettait le contenu en avant et l'auteur en petit :
                             on lisait « Mireille K. » — l'auteur d'un temoignage,
                             donc du contenu — comme si c'etait elle qui avait
                             agi. Un journal d'activites se lit par QUI agit. --}}
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">
                            {{ $recent->initialesDeLAuteur() }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $recent->nomDeLAuteur() }}
                            </span>

                            <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $recent->phrase() }}
                                @if ($lien)
                                    <a href="{{ $lien }}" wire:navigate class="hover:underline">{{ $recent->sujet_intitule }}</a>
                                @else
                                    {{-- Pas de lien vers ce qui n'existe plus : un
                                         clic rendrait une page d'erreur. --}}
                                    <span class="line-through">{{ $recent->sujet_intitule }}</span>
                                @endif
                            </span>
                        </div>

                        <time class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400"
                              datetime="{{ $recent->created_at?->toIso8601String() }}">
                            {{ $recent->created_at?->diffForHumans() }}
                        </time>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Rien n’a encore été modifié.') }}
                    </li>
                @endforelse
            </ul>

            <div class="border-t border-zinc-200 p-3 text-center dark:border-zinc-700">
                {{-- Ce lien menait a la liste des ARTICLES, alors que le panneau
                     couvre seize familles : il montrait moins que ce qu'il
                     resumait. --}}
                <a href="{{ route('admin.journal') }}" wire:navigate
                   class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">{{ __('Tout afficher') }}</a>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('À traiter') }}</h2>
            </div>

            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($aTraiter as $point)
                    <li class="px-5 py-3">
                        @if ($point['route'])
                            <a href="{{ route($point['route']) }}" wire:navigate
                               class="text-sm font-medium text-zinc-900 hover:underline dark:text-white">{{ $point['texte'] }}</a>
                        @else
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $point['texte'] }}</span>
                        @endif
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $point['detail'] }}</p>
                    </li>
                @empty
                    <li class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Rien ne demande votre attention.') }}
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Les trois panneaux de la maquette qui attendent leur lot. Ils gardent
         leur place et disent ce qu'ils porteront : un emplacement vide laisse
         croire à un oubli, un graphique inventé serait pire. --}}
    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($aVenir as $panneau)
            <div class="rounded-xl border border-dashed border-zinc-300 p-5 dark:border-zinc-600">
                <div class="flex items-center gap-2">
                    <span class="text-zinc-400 dark:text-zinc-500"><x-admin.icone nom="horloge" /></span>
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $panneau['titre'] }}</h3>
                </div>
                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $panneau['texte'] }}</p>
            </div>
        @endforeach
    </div>
</div>
