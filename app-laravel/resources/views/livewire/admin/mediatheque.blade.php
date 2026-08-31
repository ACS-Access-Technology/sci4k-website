<div class="space-y-6">
    <x-admin.entete-page
        :titre="__('Médiathèque')"
        :fil="[__('Accueil') => route('dashboard'), __('Médiathèque') => null]">
    </x-admin.entete-page>

    <div class="flex flex-wrap items-end gap-3">
        <label class="min-w-64 flex-1">
            <span class="text-sm font-medium">{{ __('Rechercher une image') }}</span>
            <input type="search" wire:model.live.debounce.300ms="recherche" placeholder="{{ __('Nom de fichier') }}" class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
        </label>
        <label>
            <span class="text-sm font-medium">{{ __('Format') }}</span>
            <select wire:model.live="type" class="mt-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                <option value="">{{ __('Tous') }}</option>
                @foreach (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'] as $extension)
                    <option value="{{ $extension }}">{{ strtoupper($extension) }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <p class="text-sm text-zinc-500">{{ trans_choice(':nombre image disponible|:nombre images disponibles', $total, ['nombre' => $total]) }}</p>

    @if ($images)
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($images as $image)
                <button type="button" wire:click="ouvrir(@js($image['chemin']))" class="group overflow-hidden rounded-lg border border-zinc-200 text-left dark:border-zinc-700">
                    <div class="flex aspect-square items-center justify-center bg-zinc-100 dark:bg-zinc-800">
                        <img src="{{ asset($image['chemin']) }}" alt="{{ $image['nom'] }}" loading="lazy" class="h-full w-full object-contain transition group-hover:scale-105">
                    </div>
                    <div class="p-2">
                        <p class="truncate text-xs font-medium" title="{{ $image['nom'] }}">{{ $image['nom'] }}</p>
                        <p class="text-xs text-zinc-500">{{ strtoupper($image['extension']) }} · {{ $image['taille'] }}</p>
                    </div>
                </button>
            @endforeach
        </div>
    @else
        <p class="rounded-lg border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">{{ __('Aucune image ne correspond à votre recherche.') }}</p>
    @endif

    @if ($selection)
        <div class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4" wire:click.self="fermer" role="presentation">
            <div class="w-full max-w-2xl rounded-xl bg-white p-4 shadow-2xl dark:bg-zinc-900" role="dialog" aria-modal="true" aria-label="{{ __('Aperçu de l’image') }}">
                <div class="flex items-center justify-between gap-4">
                    <p class="truncate text-sm font-medium">{{ basename($selection) }}</p>
                    <button type="button" wire:click="fermer" class="text-2xl leading-none text-zinc-500" aria-label="{{ __('Fermer') }}">×</button>
                </div>
                <div class="mt-4 flex max-h-[65vh] justify-center bg-zinc-100 p-3 dark:bg-zinc-800">
                    <img src="{{ asset($selection) }}" alt="{{ basename($selection) }}" class="max-h-[60vh] max-w-full object-contain">
                </div>
                <p class="mt-3 break-all font-mono text-xs text-zinc-500">{{ asset($selection) }}</p>
            </div>
        </div>
    @endif
</div>
