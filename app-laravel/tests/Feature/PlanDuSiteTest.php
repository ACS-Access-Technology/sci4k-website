<?php

use App\Models\Article;
use App\Models\Categorie;

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
    // Le coeur du defaut : un plan de site ne doit lister que des adresses
    // finales. Chacune est reellement appelee, et non deduite.
    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    preg_match_all('/<loc>([^<]+)<\/loc>/', $contenu, $trouvees);

    expect($trouvees[1])->not->toBeEmpty();

    foreach ($trouvees[1] as $adresse) {
        // La racine n'a pas de composante « path » : parse_url y rend null,
        // que toBeContain refuserait.
        $chemin = (string) parse_url($adresse, PHP_URL_PATH);

        expect($chemin)->not->toContain('services.html');
        expect($chemin)->not->toContain('faq.html');
        expect($chemin)->not->toContain('actualites.html');
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
