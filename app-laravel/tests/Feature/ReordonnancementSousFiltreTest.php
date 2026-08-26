<?php

use App\Livewire\Admin\FaqListe;
use App\Livewire\Admin\ServiceListe;
use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Reordonner pendant qu'un filtre est actif.
 *
 * Defaut trouve par la relecture adversariale. elements() applique la recherche
 * et le filtre de visibilite ; le glisser-deposer n'envoie que les identifiants
 * des lignes AFFICHEES ; et reordonner() les renumerote « en repartant de 1 ».
 *
 * Filtrer sur « Masques », deplacer une ligne, et les deux services filtres
 * recevaient les rangs 1 et 2 — deja pris par deux services visibles. L'ordre
 * public devenait celui que orderBy('ordre')->orderBy('id') produit sur des
 * rangs en doublon, jamais celui que l'editeur avait choisi, et sans le moindre
 * signal. L'invariant « a partir de 1, sans trou » ne tient que si reordonner()
 * reçoit la collection entiere.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
});

it('ne propose pas la poignee quand un filtre de visibilite est actif', function () {
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'a', 'visible' => true, 'ordre' => 1]);
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'b', 'visible' => false, 'ordre' => 2]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->assertSee('data-ordonnable', false)
        ->set('visibilite', 'masques')
        ->assertDontSee('data-ordonnable', false);
});

it('ne propose pas la poignee pendant une recherche', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'a',
        'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('recherche', 'Foncier')
        ->assertDontSee('data-ordonnable', false);
});

it('explique pourquoi le reordonnancement est indisponible', function () {
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'a', 'ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->set('visibilite', 'visibles')
        ->assertSee('Retirez les filtres', false);
});

it('refuse un reordonnancement partiel plutot que d ecraser des rangs', function () {
    // La garde de derniere ligne : la vue retire la poignee, mais Livewire
    // expose la methode au navigateur quoi qu'il arrive.
    $a = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'a', 'visible' => true, 'ordre' => 1]);
    $b = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'b', 'visible' => true, 'ordre' => 2]);
    $c = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'c', 'visible' => false, 'ordre' => 3]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$c->id]);

    // Aucun rang n'a bouge : le sous-ensemble a ete refuse en bloc.
    expect([$a->fresh()->ordre, $b->fresh()->ordre, $c->fresh()->ordre])->toBe([1, 2, 3]);
});

it('accepte un reordonnancement portant sur la collection entiere', function () {
    $a = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'a', 'ordre' => 1]);
    $b = Service::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'b', 'ordre' => 2]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceListe::class)
        ->call('reordonner', [$b->id, $a->id]);

    expect(Service::ordonnees()->pluck('slug')->all())->toBe(['b', 'a']);
});

it('protege la FAQ de la meme facon', function () {
    $rubrique = RubriqueFaq::factory()->create(['slug' => 'foncier', 'ordre' => 1]);

    $x = QuestionFaq::factory()->create(['rubrique_id' => $rubrique->id, 'ordre' => 1, 'visible' => true]);
    $y = QuestionFaq::factory()->create(['rubrique_id' => $rubrique->id, 'ordre' => 2, 'visible' => false]);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->call('reordonner', [$y->id]);

    expect([$x->fresh()->ordre, $y->fresh()->ordre])->toBe([1, 2]);
});
