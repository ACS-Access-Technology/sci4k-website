<div class="space-y-6">
    <x-admin.entete-page :titre="__('Fréquentation')" :fil="[__('Accueil') => route('dashboard'), __('Fréquentation') => null]">
        <x-slot:actions>
            <select wire:model.live="periode" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950" aria-label="{{ __('Période') }}">
                <option value="7">{{ __('7 jours') }}</option>
                <option value="30">{{ __('30 jours') }}</option>
                <option value="90">{{ __('90 jours') }}</option>
                <option value="365">{{ __('12 mois') }}</option>
            </select>
        </x-slot:actions>
    </x-admin.entete-page>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"><p class="text-sm text-zinc-500">{{ __('Pages vues') }}</p><p class="mt-2 text-3xl font-semibold">{{ $total }}</p></div>
        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700"><p class="text-sm text-zinc-500">{{ __('Visiteurs uniques estimés') }}</p><p class="mt-2 text-3xl font-semibold">{{ $visiteurs }}</p></div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <h2 class="border-b border-zinc-200 px-5 py-4 text-sm font-semibold dark:border-zinc-700">{{ __('Pages les plus consultées') }}</h2>
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($pages as $page)
                    <li class="flex justify-between gap-4 px-5 py-3 text-sm"><span class="truncate">{{ $page->chemin }}</span><strong>{{ $page->total }}</strong></li>
                @empty
                    <li class="px-5 py-6 text-sm text-zinc-500">{{ __('Aucune visite enregistrée sur cette période.') }}</li>
                @endforelse
            </ul>
        </section>
        <section class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <h2 class="border-b border-zinc-200 px-5 py-4 text-sm font-semibold dark:border-zinc-700">{{ __('Visites par jour') }}</h2>
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($parJour as $jour)
                    <li class="flex justify-between gap-4 px-5 py-3 text-sm"><span>{{ \Illuminate\Support\Carbon::parse($jour->jour)->format('d/m/Y') }}</span><strong>{{ $jour->total }}</strong></li>
                @empty
                    <li class="px-5 py-6 text-sm text-zinc-500">{{ __('Les visites apparaîtront ici.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
