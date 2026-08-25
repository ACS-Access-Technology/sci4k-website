<?php

/*
 * Page publique d'un article, a son adresse propre.
 *
 * Le plan prevoyait d'allonger ActualitesPubliquesTest ; ces controles portent
 * sur une autre page et sont regroupes a part, pour que l'echec designe la page
 * fautive sans avoir a lire le nom du test.
 */

use App\Models\Article;
use App\Models\Categorie;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('affiche un article a son adresse propre', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'acd-securiser-terrain',
        'titre_fr' => 'ACD, titre foncier',
        'contenu_fr' => "Premier paragraphe.\n\nDeuxième paragraphe.",
    ]);

    $this->get('/actualites/acd-securiser-terrain')
        ->assertOk()
        ->assertSee('ACD, titre foncier')
        ->assertSee('Premier paragraphe.')
        ->assertSee('Deuxième paragraphe.');
});

it('decoupe le contenu en paragraphes distincts', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'quatre-paragraphes',
        'contenu_fr' => "Un.\n\nDeux.\n\nTrois.\n\nQuatre.",
    ]);

    $page = $this->get('/actualites/quatre-paragraphes')->assertOk()->getContent();

    // Compter dans le seul corps de l'article : le reste de la page porte
    // d'autres <p> — l'appel a l'action, le pied de page.
    $debut = (int) strpos($page, 'article-body');
    $corps = substr($page, $debut, (int) strpos($page, '</article>') - $debut);

    expect(substr_count($corps, '<p>'))->toBe(4);
});

it('porte le titre de l article en h1, et non en h2', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'titre-en-h1',
        'titre_fr' => 'Un titre bien à lui',
    ]);

    $contenu = $this->get('/actualites/titre-en-h1')->assertOk()->getContent();

    expect($contenu)->toContain('<h1>Un titre bien à lui</h1>');
    expect(substr_count($contenu, '<h1'))->toBe(1);
});

it('affiche la couverture de l article', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'avec-couverture',
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    $this->get('/actualites/avec-couverture')
        ->assertOk()
        ->assertSee('images/actualites/article-1.jpg');
});

it('decrit l article aux moteurs dans la langue courante', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'meta-bilingue',
        'titre_en' => 'A title of its own',
        'meta_description_fr' => 'Description française.',
        'meta_description_en' => 'English description.',
    ]);

    $this->get('/langue/en');
    $reponse = $this->get('/actualites/meta-bilingue')->assertOk();

    $reponse->assertSee('English description.', false)
        ->assertDontSee('Description française.', false);

    preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $reponse->getContent(), $bloc);
    $graphe = json_decode($bloc[1], true);
    $types = array_column($graphe['@graph'], '@type');

    expect($types)->toContain('NewsArticle');

    $noeud = $graphe['@graph'][array_search('NewsArticle', $types, true)];
    expect($noeud['headline'])->toBe('A title of its own');
    expect($noeud['inLanguage'])->toBe('en');
});

it('replie la description sur le resume quand la meta est vide', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'sans-meta',
        'resume_fr' => 'Le résumé sert de description.',
        'meta_description_fr' => '',
        'meta_description_en' => null,
    ]);

    $this->get('/actualites/sans-meta')
        ->assertOk()
        ->assertSee('Le résumé sert de description.', false);
});

it('renvoie 404 sur un article inconnu', function () {
    $this->get('/actualites/article-inexistant')->assertNotFound();
});

it('renvoie 404 sur un brouillon', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
        'slug' => 'en-preparation',
    ]);

    $this->get('/actualites/en-preparation')->assertNotFound();
});

it('redirige les anciennes adresses vers la nouvelle', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'acd-securiser-terrain',
    ]);

    $this->get('/actualite-detail.html?id=acd-securiser-terrain')
        ->assertRedirect('/actualites/acd-securiser-terrain');
});

it('renvoie l ancienne adresse sans parametre vers la liste', function () {
    $this->get('/actualite-detail.html')->assertRedirect('/actualites');
});

it('depuis la liste, chaque carte mene a son article', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'article-atteignable',
    ]);

    $this->get('/actualites')
        ->assertOk()
        ->assertSee(url('/actualites/article-atteignable'), false);
});
