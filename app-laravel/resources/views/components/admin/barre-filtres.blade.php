{{--
    Bandeau de filtres, commun a tous les tableaux d'administration.

    Les champs sont passes en contenu : chaque ecran declare les siens, la
    disposition et l'habillage restent ici. Sur mobile les champs s'empilent,
    la maquette les mettant sur une rangee qui ne tient pas sous 640 pixels.
--}}
<div {{ $attributes->merge(['class' => 'rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900']) }}>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{ $slot }}
    </div>
</div>
