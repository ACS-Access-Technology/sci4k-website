<?php

use App\Livewire\Admin\TableauDeBord;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\MembreEquipe;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Tableau de bord.
 *
 * Il reprend la disposition de backoffice/dashboard.html, mais chaque chiffre
 * est mesure — la maquette annonce des visiteurs et des biens que rien ne
 * compte encore. Ces tests verifient surtout ce point : ce qui s'affiche
 * correspond a l'etat reel.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
});

it('sert le tableau de bord', function () {
    $this->actingAs($this->editeur)->get(route('dashboard'))->assertOk();
});

it('compte les articles publies, pas les brouillons', function () {
    Article::factory()->count(3)->create(['categorie_id' => $this->categorie->id, 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'brouillon']);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSeeInOrder(['3', 'Articles publiés']);
});

it('signale les elements masques', function () {
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'visible' => false]);
    Temoignage::factory()->create(['visible' => false]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('Éléments masqués')
        ->assertSee('2 éléments masqués');
});

it('annonce que rien ne demande d attention quand tout est en ligne', function () {
    Service::factory()->create(['categorie_id' => $this->categorie->id, 'visible' => true]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('Rien ne demande votre attention.');
});

it('signale un membre sans photo', function () {
    MembreEquipe::factory()->create(['photo' => null, 'visible' => true]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('1 membre sans photo');
});

it('signale un texte sans version anglaise', function () {
    Temoignage::factory()->create(['citation_en' => '', 'visible' => true]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('1 texte sans version anglaise');
});

it('montre les derniers contenus touches', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'nom_fr' => 'Foncier récemment modifié',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('Activité récente')
        ->assertSee('Foncier récemment modifié');
});

it('dit ce qu il ne mesure pas encore', function () {
    // La maquette annonce des visiteurs et une repartition des biens. Le taire
    // laisserait croire a un oubli ; l'inventer serait pire.
    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('suivi de fréquentation');
});

it('reste consultable par un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)->get(route('dashboard'))->assertOk();
});
