{{--
    Poignee de glisser-deposer.

    Le rang se regle en deplacant la ligne, pas en saisissant un nombre :
    taper « 3 » puis « 4 » pour permuter deux elements produit des doublons et
    impose de renumeroter a chaque insertion.

    aria-hidden sur l'icone, mais le bouton reste atteignable au clavier et
    porte son intitule : un lecteur d'ecran annonce « Deplacer », pas « point
    point point ».
--}}
<button type="button"
        class="cursor-grab touch-none rounded-md p-2 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 active:cursor-grabbing dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
        aria-label="{{ __('Déplacer cet élément') }}"
        {{ $attributes }}>
    <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
        <path d="M9 6h.01M9 12h.01M9 18h.01M15 6h.01M15 12h.01M15 18h.01"/>
    </svg>
</button>
