{{-- Options d'affichage des compteurs. --}}
<label class="flex items-start gap-2">
    <input type="checkbox" wire:model="reglages.animer" @disabled(! $peutEcrire)
           class="mt-0.5 rounded border-zinc-300">
    <span class="text-sm">{{ __('Animer les compteurs au défilement') }}</span>
</label>

<label class="block">
    <span class="text-sm font-medium">{{ __("Durée de l'animation") }}</span>
    <div class="flex items-center gap-2">
        <input type="number" wire:model="reglages.duree_animation" min="0" max="10000" step="100"
               @disabled(! $peutEcrire) class="{{ $champ }}">
        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ms') }}</span>
    </div>
</label>

{{-- La maquette propose de calculer « Biens commercialisés » depuis le
     catalogue. Les biens immobiliers sont au lot 3 : la case est posée mais
     désactivée, avec la raison écrite. Un interrupteur qui ne fait rien est
     pire qu'un interrupteur absent — on croit l'avoir activé. --}}
<div class="rounded-lg border border-dashed border-zinc-300 p-3 dark:border-zinc-600">
    <label class="flex items-start gap-2 opacity-60">
        <input type="checkbox" disabled class="mt-0.5 rounded border-zinc-300">
        <span class="text-sm">{{ __('Calculer « Biens commercialisés » automatiquement') }}</span>
    </label>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
        {{ __("Disponible quand le catalogue des biens immobiliers existera : le compteur reprendra alors le nombre de biens au statut « Vendu ».") }}
    </p>
</div>
