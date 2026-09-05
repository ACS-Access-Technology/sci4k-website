<?php

use App\Models\Categorie;
use App\Models\Service;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('rend ses textes dans la langue demandee', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
        'accroche_fr' => 'Sécuriser vos terrains', 'accroche_en' => 'Secure your land',
    ]);

    expect($service->nom('fr'))->toBe('Foncier');
    expect($service->nom('en'))->toBe('Land & Title');
    expect($service->accroche('en'))->toBe('Secure your land');
});

it('replie sur le francais quand l anglais manque', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'nom_fr' => 'Foncier', 'nom_en' => '',
    ]);

    expect($service->nom('en'))->toBe('Foncier');
});

it('rend ses atouts comme une liste, sans les vides', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'atout1_fr' => 'Vérification ACD',
        'atout2_fr' => 'Bornage',
        'atout3_fr' => '',
    ]);

    expect($service->atouts('fr'))->toBe(['Vérification ACD', 'Bornage']);
});

it('refuse deux services de meme slug', function () {
    Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]);

    expect(fn () => Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]))
        ->toThrow(QueryException::class);
});

it('se retrouve par son slug dans une adresse', function () {
    $service = Service::factory()->create(['slug' => 'foncier', 'categorie_id' => $this->categorie->id]);

    expect($service->getRouteKeyName())->toBe('slug');
});

it('appartient a une categorie', function () {
    $service = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    expect($service->categorie->slug)->toBe('foncier');
});
