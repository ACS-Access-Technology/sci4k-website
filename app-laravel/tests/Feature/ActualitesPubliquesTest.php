<?php

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Support\Facades\App;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('affiche la liste des actualites', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'titre_fr' => 'Sécuriser un terrain à Abidjan',
    ]);

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('Sécuriser un terrain à Abidjan');
});

it('ne montre pas les brouillons', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
        'titre_fr' => 'Article en préparation',
    ]);

    $this->get('/actualites')->assertDontSee('Article en préparation');
});

it('affiche la couverture reelle de l article', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('images/actualites/article-1.jpg')
        ->assertDontSee('defaut.jpg');
});

it('n emet aucune couverture quand l image est absente', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'image_source' => 'images/actualites/article-1.jpg',
    ]);
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'image_source' => null,
    ]);

    $reponse = $this->get('/actualites')->assertOk();

    expect(substr_count($reponse->getContent(), 'background-image'))->toBe(1);
});

it('affiche le titre anglais quand la langue est anglaise', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'titre_fr' => 'Sécuriser un terrain à Abidjan',
        'titre_en' => 'Securing a plot in Abidjan',
    ]);

    // La langue est prise par le meme chemin qu'un visiteur, et non forcee par
    // App::setLocale() : le middleware AppliqueLangue la relit a chaque requete
    // et ecraserait une locale posee dans le test. Cette ligne est le seul
    // point a reprendre le jour ou le mecanisme public sera arbitre (ruling O).
    $this->get('/langue/en');

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('Securing a plot in Abidjan')
        ->assertDontSee('Sécuriser un terrain à Abidjan');
});

it('rend la page entiere dans la langue choisie, pas seulement les articles', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
    ]);

    $this->get('/langue/en');

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('<html lang="en"', false)
        ->assertSee('Land &amp; Title', false)   // la categorie, depuis la base
        ->assertSee('Search');                   // un libelle d'interface
});

it('sert la page d accueil a la racine', function () {
    // L'accueil etait servi depuis public/index.html jusqu'au lot 2b ; il est
    // desormais rendu par Laravel, et le fichier statique est exclu de la
    // synchronisation. Le controle porte donc sur la reponse, pas sur un
    // fichier — c'est la leçon du plan de site, vert pour la mauvaise raison
    // parce qu'il validait des fichiers plutot que ce que le serveur rend.
    $this->get('/')->assertOk()->assertSee('page-accueil', false);
});

it('redirige l ancienne adresse de la liste vers la nouvelle', function () {
    $this->get('/actualites.html')->assertRedirect('/actualites');
});
