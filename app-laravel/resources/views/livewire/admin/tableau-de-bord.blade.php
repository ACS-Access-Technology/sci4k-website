@php($tons = [
    'primaire' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    'succes' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    'info' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    'alerte' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
])

<div class="space-y-4">

    <x-admin.entete-page
        :titre="__('Tableau de bord')"
        :fil="[__('Accueil') => null]"
        :resume="__('État du contenu du site')">
        <x-slot:actions>
            <a href="{{ route('home') }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 px-4 py-2.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ __('Voir le site') }}
            </a>
        </x-slot:actions>
    </x-admin.entete-page>

    {{-- Quatre tuiles, comme la maquette. La variation en pourcentage qu'elle
         affiche demanderait un historique que personne ne conserve : chaque
         tuile annonce a la place ce qui a ete ajouté ce mois-ci, qui se mesure
         vraiment. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($tuiles as $tuile)
            @php($contenu = '
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-3xl font-semibold text-zinc-900 dark:text-white">'.e($tuile['valeur']).'</p>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">'.e($tuile['intitule']).'</p>
                    </div>
                </div>')

            <div @class([
                'rounded-xl border border-zinc-200 p-5 dark:border-zinc-700',
                'hover:border-zinc-300 dark:hover:border-zinc-600' => $tuile['route'],
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $tuile['valeur'] }}</p>
                        <p class="mt-1 truncate text-sm text-zinc-600 dark:text-zinc-400">
                            @if ($tuile['route'])
                                <a href="{{ route($tuile['route']) }}" wire:navigate class="hover:underline">{{ $tuile['intitule'] }}</a>
                            @else
                                {{ $tuile['intitule'] }}
                            @endif
                        </p>
                    </div>

                    <span class="flex size-10 shrink-0 items-center justify-center rounded-lg {{ $tons[$tuile['ton']] }}">
                        <x-admin.icone nom="plus" />
                    </span>
                </div>

                @if ($tuile['ajoutes'] > 0)
                    <p class="mt-3 text-xs text-emerald-700 dark:text-emerald-400">
                        {{ trans_choice('+:nombre ce mois-ci|+:nombre ce mois-ci', $tuile['ajoutes'], ['nombre' => $tuile['ajoutes']]) }}
                    </p>
                @elseif ($tuile['ton'] === 'alerte' && $tuile['valeur'] === 0)
                    <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Tout est en ligne') }}</p>
                @else
                    <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-500">&nbsp;</p>
                @endif
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

        {{-- Les « tâches prioritaires » de la maquette, mais DÉDUITES de l'état
             réel plutôt que saisies : une liste qu'il faut penser à cocher se
             désynchronise du site en une semaine. --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('À traiter') }}</h2>
            </div>

            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($aTraiter as $tache)
                    <li class="px-5 py-3">
                        @if ($tache['route'])
                            <a href="{{ route($tache['route']) }}" wire:navigate
                               class="text-sm font-medium text-zinc-900 hover:underline dark:text-white">{{ $tache['texte'] }}</a>
                        @else
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $tache['texte'] }}</span>
                        @endif
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $tache['detail'] }}</p>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-zinc-600 dark:text-zinc-400">
                        {{ __('Rien ne demande votre attention.') }}
                    </li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Activité récente') }}</h2>
        </div>

        <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($recents as $recent)
                <li class="flex items-center justify-between gap-4 px-5 py-3">
                    <div class="min-w-0">
                        <a href="{{ route($recent['route'], $recent['element']) }}" wire:navigate
                           class="block truncate text-sm font-medium text-zinc-900 hover:underline dark:text-white">
                            {{ $recent['intitule'] }}
                        </a>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $recent['famille'] }}</span>
                    </div>

                    <time class="shrink-0 text-xs text-zinc-500 dark:text-zinc-400"
                          datetime="{{ $recent['quand']?->toIso8601String() }}">
                        {{ $recent['quand']?->diffForHumans() }}
                    </time>
                </li>
            @empty
                <li class="px-5 py-12 text-center text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Rien n’a encore été modifié.') }}
                </li>
            @endforelse
        </ul>
    </div>

    {{-- Ce que la maquette annonce et que le site ne mesure pas encore. Le dire
         vaut mieux que de laisser croire à un oubli — ou pire, d'afficher un
         graphique sans données derrière. --}}
    <p class="text-xs text-zinc-500 dark:text-zinc-400">
        {{ __("Les compteurs de visiteurs et la répartition des biens immobiliers arriveront avec le suivi de fréquentation et le catalogue des biens.") }}
    </p>
</div>
