{{-- Une carte de l'aperçu des chiffres clés. Un compteur masqué reste affiché
     mais estompé : le retirer de l'aperçu aurait fait croire à une perte. --}}
<div @class([
    'rounded-lg border border-zinc-200 p-4 text-center dark:border-zinc-700',
    'opacity-40' => ! $visible,
])>
    <p class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $valeur }}</p>
    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $intitule }}</p>

    @unless ($visible)
        <p class="mt-2 text-xs uppercase tracking-wide text-amber-600 dark:text-amber-400">{{ __('Masqué') }}</p>
    @endunless
</div>
