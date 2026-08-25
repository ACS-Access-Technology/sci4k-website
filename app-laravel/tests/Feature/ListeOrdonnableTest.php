<?php

use App\Livewire\Admin\ServiceListe;
use App\Models\Categorie;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les elements dans leur ordre', function () {
    Service::factory()->create(['nom_fr' => 'Second', 'ordre' => 2, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Premier', 'ordre' => 1, 'categorie_id' => $this->categorie->id]);

    $rendu = Livewire::actingAs($this->editeur)->test(ServiceListe::class)->html();

    expect(strpos($rendu, 'Premier'))->toBeLessThan(strpos($rendu, 'Second'));
});

it('cherche dans les deux langues', function () {
    Service::factory()->create(['nom_fr' => 'Foncier', 'nom_en' => 'Land title', 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Construction', 'nom_en' => 'Building', 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('recherche', 'Land')
        ->assertSee('Foncier')
        ->assertDontSee('Construction');
});

it('filtre sur la visibilite', function () {
    Service::factory()->create(['nom_fr' => 'En ligne', 'visible' => true, 'categorie_id' => $this->categorie->id]);
    Service::factory()->create(['nom_fr' => 'Masque', 'visible' => false, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('visibilite', 'masques')
        ->assertSee('Masque')
        ->assertDontSee('En ligne');
});

it('bascule la visibilite d un element', function () {
    $s = Service::factory()->create(['visible' => true, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('basculerVisibilite', $s->id);

    expect($s->fresh()->visible)->toBeFalse();
});

it('reordonne depuis le navigateur', function () {
    $a = Service::factory()->create(['ordre' => 1, 'categorie_id' => $this->categorie->id]);
    $b = Service::factory()->create(['ordre' => 2, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$b->id, $a->id]);

    expect(Service::ordonnees()->pluck('id')->all())->toBe([$b->id, $a->id]);
});

it('refuse la suppression d un service, meme a un editeur', function () {
    $s = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('supprimer', $s->id)
        ->assertForbidden();

    expect(Service::find($s->id))->not->toBeNull();
});

it('laisse le reordonnancement et la bascule de visibilite ouverts sur les services', function () {
    // La suppression est fermee, pas l'ecran : verifier qu'on n'a pas verrouille
    // plus que voulu.
    $s = Service::factory()->create(['visible' => true, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('basculerVisibilite', $s->id);

    expect($s->fresh()->visible)->toBeFalse();
});

it('interdit a un lecteur d ecrire', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $s = Service::factory()->create(['categorie_id' => $this->categorie->id]);

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('supprimer', $s->id)
        ->assertForbidden();

    expect(Service::find($s->id))->not->toBeNull();

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$s->id])
        ->assertForbidden();

    Livewire::actingAs($lecteur)
        ->test(ServiceListe::class)
        ->call('basculerVisibilite', $s->id)
        ->assertForbidden();
});
