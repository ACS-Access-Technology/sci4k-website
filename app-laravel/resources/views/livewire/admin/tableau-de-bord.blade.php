<div class="space-y-6">

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

    {{-- Le compte des elements masques est le plus utile des trois : c'est le
         seul qui signale un oubli, un bloc retire « le temps de » et jamais
         remis. Il n'apparait donc que lorsqu'il n'est pas nul, pour se voir. --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($familles as $famille)
            <a href="{{ route($famille['route']) }}" wire:navigate
               class="block rounded-xl border border-zinc-200 p-5 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-600 dark:hover:bg-zinc-800/50">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $famille['intitule'] }}</p>
                <p class="mt-1 text-3xl font-semibold text-zinc-900 dark:text-white">{{ $famille['total'] }}</p>

                @if ($famille['masques'] > 0)
                    <p class="mt-2 inline-flex items-center rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-950 dark:text-amber-200">
                        {{ $famille['masques'] }} {{ $famille['motMasque'] }}
                    </p>
                @else
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Tout est en ligne') }}</p>
                @endif
            </a>
        @endforeach
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Dernières modifications') }}</h2>
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
</div>
