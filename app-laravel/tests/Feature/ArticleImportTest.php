<?php

use App\Models\Article;
use Database\Seeders\ArticleImportSeeder;
use Database\Seeders\CategorieSeeder;

beforeEach(function () {
    $this->seed(CategorieSeeder::class);
});

it('reprend les douze articles du site', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::count())->toBe(12);
});

it('reprend les deux langues de chaque article', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::whereNull('titre_en')->orWhere('titre_en', '')->count())->toBe(0);
    expect(Article::whereNull('contenu_en')->orWhere('contenu_en', '')->count())->toBe(0);
});

it('conserve les slugs existants', function () {
    $this->seed(ArticleImportSeeder::class);

    expect(Article::where('slug', 'acd-securiser-terrain')->exists())->toBeTrue();
});

it('ne duplique pas quand on le rejoue', function () {
    $this->seed(ArticleImportSeeder::class);
    $this->seed(ArticleImportSeeder::class);

    expect(Article::count())->toBe(12);
});
