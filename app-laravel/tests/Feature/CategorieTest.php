<?php

use App\Models\Categorie;
use Database\Seeders\CategorieSeeder;

it('cree les sept categories du site', function () {
    $this->seed(CategorieSeeder::class);

    expect(Categorie::count())->toBe(7);
});

it('donne le nom dans la langue demandee', function () {
    $c = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    expect($c->nom('fr'))->toBe('Foncier');
    expect($c->nom('en'))->toBe('Land & Title');
});

it('refuse deux categories de meme slug', function () {
    Categorie::create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1]);

    expect(fn () => Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Autre', 'nom_en' => 'Other', 'ordre' => 2,
    ]))->toThrow(Illuminate\Database\QueryException::class);
});
