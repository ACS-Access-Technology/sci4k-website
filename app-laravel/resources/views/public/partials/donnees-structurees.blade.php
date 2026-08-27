{{--
    Donnees structurees schema.org, communes a toutes les pages publiques.

    Le graphe est construit en PHP puis encode par json_encode, et non ecrit a
    la main comme dans les pages statiques : l'echappement des accents, des
    apostrophes et des esperluettes est alors garanti correct. La page statique
    portait « Actualites &amp; conseils » dans un champ JSON, ou l'entite HTML
    n'a pas de sens et ressort telle quelle chez les moteurs.

    Les adresses sont derivees de url() et non ecrites en dur : le passage de
    www.sci4k.com a sci4k.com, encore en attente cote client, ne demandera
    aucune retouche ici.

    $noeudPage : le noeud propre a la page appelante (CollectionPage, Article…),
    ajoute au graphe partage. Optionnel.
--}}
@php
    $racine = rtrim(url('/'), '/');

    $graphe = [
        [
            '@type' => 'RealEstateAgent',
            '@id' => $racine.'/#organisation',
            'name' => 'SCI4K',
            'legalName' => 'SCI4K — Société Civile Immobilière',
            'url' => $racine.'/',
            'logo' => $racine.'/images/image%20(3).png',
            'image' => $racine.'/images/image%20(3).png',
            'description' => 'Société Civile Immobilière basée à Abidjan : achat, vente, location, construction et gestion de patrimoine immobilier.',
            'telephone' => '+2250706165029',
            'email' => 'contact@sci4k.com',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => 'Cité des Arts, Résidence Paon, 3ème étage',
                'addressLocality' => 'Cocody',
                'addressRegion' => 'Abidjan',
                'addressCountry' => 'CI',
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
                    'opens' => '08:00',
                    'closes' => '18:00',
                ],
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Saturday'],
                    'opens' => '09:00',
                    'closes' => '13:00',
                ],
            ],
            'areaServed' => array_map(
                fn ($lieu) => ['@type' => 'Place', 'name' => $lieu],
                ['Cocody', 'Riviera', 'Plateau', 'Marcory', 'Abidjan']
            ),
            'knowsLanguage' => ['fr', 'en'],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $racine.'/#site',
            'url' => $racine.'/',
            'name' => 'SCI4K',
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => $racine.'/#organisation'],
        ],
    ];

    if (! empty($noeudPage)) {
        $graphe[] = $noeudPage;
    }
@endphp
{{--
    @@context ci-dessous : Blade compile @context (sans double @) comme la
    directive du Context facade et non comme le litteral JSON-LD attendu par
    schema.org. Sans cet echappement, la cle sort sous la forme du fragment
    PHP non evalue de la directive, et les moteurs de recherche, qui exigent
    "@context" pour reconnaitre le bloc, ignorent silencieusement tout le
    graphe — bug decouvert en verifiant le rendu de ce partial sur une page
    servie.
--}}
<script type="application/ld+json">
{!! json_encode(['@@context' => 'https://schema.org', '@graph' => $graphe], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
