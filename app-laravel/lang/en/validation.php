<?php

/*
 * Only the readable field names. The messages themselves come from the
 * framework's own English files; overriding them here would mean maintaining a
 * copy for no gain.
 *
 * Without these, a message would name the component property — "the titreEn
 * field is required" — instead of what the editor sees on screen.
 */

return [
    'attributes' => [
        'slug' => 'URL slug',
        'categorieId' => 'category',
        'datePublication' => 'publication date',
        'statut' => 'status',
        'titreFr' => 'French title',
        'titreEn' => 'English title',
        'resumeFr' => 'French summary',
        'resumeEn' => 'English summary',
        'contenuFr' => 'French content',
        'contenuEn' => 'English content',
        'metaTitreFr' => 'French meta title',
        'metaTitreEn' => 'English meta title',
        'metaDescriptionFr' => 'French meta description',
        'metaDescriptionEn' => 'English meta description',
        'couverture' => 'cover image',
    ],
];
