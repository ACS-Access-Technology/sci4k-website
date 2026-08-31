@props(['nom' => 'document'])

{{--
    Icones de l'administration, dessinees a la main plutot que tirees d'une
    bibliotheque : vingt-quatre traces suffisent, et une dependance de plus pour
    cela ne se justifie pas. Trait unique, 1.8, pour rester lisible a 18 pixels.
--}}
@php
    $traces = [
        'document' => '<path d="M14 3v5h5"/><path d="M19 8v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7z"/>',
        'crayon' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'oeil' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'archive' => '<rect x="3" y="4" width="18" height="4" rx="1"/><path d="M5 8v11a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8"/><path d="M10 12h4"/>',
        'corbeille' => '<path d="M3 6h18"/><path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2"/><path d="M19 6v14a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6"/><path d="M10 11v6M14 11v6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'loupe' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'grille' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'filtre' => '<path d="M3 5h18l-7 8v5l-4 2v-7z"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.7"/><path d="M12 17h.01"/>',
        'guillemets' => '<path d="M7 15c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3c0 3-2 5-4 6"/><path d="M17 15c-1.7 0-3-1.3-3-3s1.3-3 3-3 3 1.3 3 3c0 3-2 5-4 6"/>',
        'personne' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'oeil-barre' => '<path d="M3 3l18 18"/><path d="M10.6 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3 3.9"/><path d="M6.1 6.6A17 17 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 4.2-.9"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/>',
        'horloge' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'maison' => '<path d="M3 12l9-9 9 9"/><path d="M5 10v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V10"/><path d="M9 21v-6h6v6"/>',
        'article' => '<path d="M14 3v5h5"/><path d="M19 8v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7z"/><path d="M7 13h10"/><path d="M7 17h6"/>',
        'service' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.8-3.8a6 6 0 0 1-7.9 7.9l-7 7a2 2 0 0 1-2.8 0l2.8-2.8a2 2 0 0 1 0-2.8l7-7a6 6 0 0 1 7.9-7.9z"/>',
        'bulle' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'etoile' => '<polygon points="12 2 15.1 8.3 22 9.2 17 14 18.2 21 12 17.8 5.8 21 7 14 2 9.2 8.9 8.3"/>',
        'cartable' => '<path d="M4 4h16a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><path d="M3 9h18"/><path d="M9 3v6"/>',
        'coeur' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.3l7.8-7.8 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'graphique' => '<path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/>',
        'escalier' => '<path d="M4 20h4v-4h4v-4h4v-4h4"/>',
        'bandeau' => '<path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h10"/>',
        'encart' => '<rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
        'parametres' => '<circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'utilisateur' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
        'base' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.7-4 3-9 3s-9-1.3-9-3"/><path d="M3 5v14c0 1.7 4 3 9 3s9-1.3 9-3V5"/>',
        'page' => '<path d="M14 3v5h5"/><path d="M19 8v11a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7z"/><path d="M7 13h10"/>',
        'courrier' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 7l-10 6L2 7"/>',
        'calendrier' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M8 14h.01"/>',
        'abonne' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h6"/><path d="M16 16l2 2 4-4"/>',
        'journal' => '<path d="M4 19V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"/><path d="M4 19h16"/><path d="M8 7h8M8 11h5"/>',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-[18px] w-[18px]', 'aria-hidden' => 'true']) }}
     viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    {!! $traces[$nom] ?? $traces['document'] !!}
</svg>
