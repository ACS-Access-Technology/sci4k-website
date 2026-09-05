<?php

use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\PageStatique;

/*
 * Plan du site.
 *
 * Constat de la relecture adversariale : le fichier fige de frontoffice/
 * annonçait /services.html, /faq.html et /actualites.html, qui ne repondent
 * plus que par une redirection 301 — et il ne connaissait aucun article, si
 * bien que les douze articles repris du site etaient invisibles pour les
 * moteurs. Le controle de tools/verifier-site.py etait vert pour la mauvaise
 * raison : il validait les adresses contre l'existence de FICHIERS sources,
 * pas contre ce que le serveur rend.
 */
beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
});

it('sert un document XML valide', function () {
    $reponse = $this->get('/sitemap.xml')->assertOk();

    expect($reponse->headers->get('Content-Type'))->toContain('xml');

    $xml = simplexml_load_string($reponse->getContent());

    expect($xml)->not->toBeFalse();
    expect($xml->getName())->toBe('urlset');
});

it('n annonce aucune adresse qui redirige', function () {
    // Ce controle ne nommait que trois pages : services.html, faq.html et
    // actualites.html. Il est reste vert pendant que biens.html,
    // presentation.html et contact.html, portes depuis, s'installaient dans le
    // plan — un garde-fou qui verifie les symptomes connus au lieu de la regle
    // ne rattrape jamais le cas suivant.
    //
    // La regle, elle, tient en une phrase : chaque adresse annoncee doit
    // repondre 200. Elle est donc appliquee en APPELANT chaque adresse.
    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    preg_match_all('/<loc>([^<]+)<\/loc>/', $contenu, $trouvees);

    expect($trouvees[1])->not->toBeEmpty();

    foreach ($trouvees[1] as $adresse) {
        // La racine n'a pas de composante « path » : parse_url y rend null,
        // que toContain refuserait.
        $chemin = (string) parse_url($adresse, PHP_URL_PATH);

        expect($this->get($chemin === '' ? '/' : $chemin)->getStatusCode())
            ->toBe(200, "Le plan du site annonce $adresse, qui ne repond pas 200.");
    }
});

it('annonce les trois pages rendues par le serveur', function () {
    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (['/services', '/faq', '/actualites'] as $chemin) {
        expect($contenu)->toContain(url($chemin).'</loc>');
    }
});

it('annonce chaque article publie, avec sa propre adresse', function () {
    $publie = Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'acd-securiser', 'statut' => 'publie',
    ]);

    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($contenu)->toContain(route('actualites.detail', $publie));
});

it('n annonce pas un brouillon', function () {
    $brouillon = Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'pas-encore', 'statut' => 'brouillon',
    ]);

    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($contenu)->not->toContain(route('actualites.detail', $brouillon));
});

it('annonce chaque fiche de bien', function () {
    // Les fiches manquaient entierement : le plan annonçait le catalogue, mais
    // pas les biens. Un catalogue immobilier se trouve par ses biens.
    $publie = Bien::factory()->create(['slug' => 'villa-cocody', 'statut' => Bien::PUBLIE]);
    $vendu = Bien::factory()->create(['slug' => 'duplex-riviera', 'statut' => Bien::VENDU]);
    $brouillon = Bien::factory()->brouillon()->create(['slug' => 'pas-encore-en-ligne']);

    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($contenu)->toContain(route('biens.detail', $publie->slug))
        // Un bien vendu garde sa fiche, marquee comme telle : la retirer du
        // plan ferait disparaitre une adresse deja indexee.
        ->and($contenu)->toContain(route('biens.detail', $vendu->slug))
        ->and($contenu)->not->toContain($brouillon->slug);
});

it('n annonce une page legale que si elle est publiee', function () {
    $politique = PageStatique::where('slug', 'politique-confidentialite')->first();
    $mentions = PageStatique::where('slug', 'mentions-legales')->first();

    expect($politique?->publie)->toBeTrue()
        // Les mentions legales attendent des informations que seul le client
        // detient. Les annoncer aux moteurs criblees de « [a completer] »
        // serait pire que de ne pas les annoncer.
        ->and($mentions?->publie)->toBeFalse();

    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($contenu)->toContain(route('politique-confidentialite.index'))
        ->and($contenu)->not->toContain(route('mentions-legales.index').'</loc>');
});

it('suit les publications sans qu on touche a un fichier', function () {
    // Ce qu'un plan de site fige ne pouvait pas faire : un article publie
    // apres coup doit y figurer immediatement.
    $avant = $this->get('/sitemap.xml')->getContent();

    $nouveau = Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'publie-apres', 'statut' => 'publie',
    ]);

    $apres = $this->get('/sitemap.xml')->getContent();

    expect($avant)->not->toContain('publie-apres');
    expect($apres)->toContain(route('actualites.detail', $nouveau));
});
