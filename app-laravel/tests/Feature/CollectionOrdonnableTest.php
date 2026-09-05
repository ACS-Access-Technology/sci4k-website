<?php

use App\Models\Categorie;
use App\Models\Service;

beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('trie par ordre croissant', function () {
    Service::factory()->create(['nom_fr' => 'Troisieme', 'ordre' => 3, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Premier', 'ordre' => 1, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Second', 'ordre' => 2, 'categorie_id' => $this->categorie->id]);

    expect(Service::ordonnees()->pluck('nom_fr')->all())
        ->toBe(['Premier', 'Second', 'Troisieme']);
});

it('ne renvoie que les elements visibles', function () {
    Service::factory()->create(['nom_fr' => 'En ligne', 'visible' => true, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Masque', 'visible' => false, 'categorie_id' => $this->categorie->id]);

    expect(Service::visibles()->pluck('nom_fr')->all())->toBe(['En ligne']);
});

it('reecrit les rangs dans l ordre recu', function () {
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);
    $c = Service::factory()->create(['ordre' => 3, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$c->id, $a->id, $b->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$c->id, $a->id, $b->id]);
});

it('ignore un identifiant etranger sans toucher aux autres', function () {
    // Un identifiant inconnu ne doit ni lever d'exception, ni decaler les rangs
    // des elements legitimes : la requete vient du navigateur.
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$b->id, 999999, $a->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('numerote a partir de un, sans trou', function () {
    $a = Service::factory()->create(['ordre' => 50, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 90, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$b->id, $a->id]);

    expect(Service::find($b->id)->ordre)->toBe(1);
    expect(Service::find($a->id)->ordre)->toBe(2);
});

it('ignore les doublons sans laisser de trou dans la numerotation', function () {
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);

    Service::reordonner([$b->id, $b->id, $a->id]);

    expect(Service::find($b->id)->ordre)->toBe(1);
    expect(Service::find($a->id)->ordre)->toBe(2);
    expect(Service::ordonnees()->pluck('id')->all())->toBe([$b->id, $a->id]);
});
