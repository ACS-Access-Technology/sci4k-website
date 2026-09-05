<?php

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('rend le titre dans la langue demandee', function () {
    $a = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Sécuriser un terrain',
        'titre_en' => 'Securing a plot',
    ]);

    expect($a->titre('fr'))->toBe('Sécuriser un terrain');
    expect($a->titre('en'))->toBe('Securing a plot');
});

it('refuse un article sans titre francais', function () {
    expect(fn () => Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => null,
    ]))->toThrow(QueryException::class);
});

it('refuse deux articles de meme slug', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'acd-securiser-terrain']);

    expect(fn () => Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'acd-securiser-terrain',
    ]))->toThrow(QueryException::class);
});

it('ne compte pas les brouillons parmi les articles publies', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'brouillon']);

    expect(Article::publies()->count())->toBe(1);
});

it('classe les articles publies du plus recent au plus ancien', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'statut' => 'publie',
        'slug' => 'ancien', 'date_publication' => '2026-01-01',
    ]);
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'statut' => 'publie',
        'slug' => 'recent', 'date_publication' => '2026-08-12',
    ]);

    expect(Article::publies()->first()->slug)->toBe('recent');
});

it('replie sur le francais quand la langue est inconnue', function () {
    $a = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Titre FR', 'titre_en' => 'Title EN',
        'resume_fr' => 'Resume FR', 'resume_en' => 'Resume EN',
        'contenu_fr' => 'Contenu FR', 'contenu_en' => 'Content EN',
    ]);

    expect($a->titre('de'))->toBe('Titre FR');
    expect($a->resume('de'))->toBe('Resume FR');
    expect($a->contenu('de'))->toBe('Contenu FR');
});

it('replie sur le francais quand la traduction est vide', function () {
    $a = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Titre FR', 'titre_en' => '',
        'resume_fr' => 'Resume FR', 'resume_en' => '',
        'contenu_fr' => 'Contenu FR', 'contenu_en' => '',
    ]);

    expect($a->titre('en'))->toBe('Titre FR');
    expect($a->resume('en'))->toBe('Resume FR');
    expect($a->contenu('en'))->toBe('Contenu FR');
});

it('renvoie le francais par defaut quand aucune langue n\'est passee', function () {
    $a = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Titre FR', 'titre_en' => 'Title EN',
        'resume_fr' => 'Resume FR', 'resume_en' => 'Resume EN',
        'contenu_fr' => 'Contenu FR', 'contenu_en' => 'Content EN',
    ]);

    expect($a->titre())->toBe('Titre FR');
    expect($a->resume())->toBe('Resume FR');
    expect($a->contenu())->toBe('Contenu FR');
});
