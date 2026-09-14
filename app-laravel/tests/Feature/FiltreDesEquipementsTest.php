<?php

use App\Livewire\Public\CatalogueDesBiens;
use App\Models\Bien;
use App\Models\Referentiel;
use Database\Seeders\ReferentielsSeeder;
use Livewire\Livewire;

/*
 * Le panneau lateral du catalogue.
 *
 * SECOND filtre, multicritere, qui se cumule aux cinq listes du haut de page
 * au lieu de les remplacer. Les tests portent sur les trois decisions qui le
 * distinguent : cocher deux cases exige les DEUX equipements, le vocabulaire
 * est partage entre les biens, et un identifiant venu du navigateur ne peut
 * pas restreindre la recherche si aucune case ne l'explique.
 */
beforeEach(function () {
    $this->seed(ReferentielsSeeder::class);

    $this->piscine = Referentiel::create([
        'famille' => 'equipements', 'valeur' => 'piscine',
        'libelle_fr' => 'Piscine', 'libelle_en' => 'Pool', 'ordre' => 1, 'visible' => true,
    ]);

    $this->garage = Referentiel::create([
        'famille' => 'equipements', 'valeur' => 'garage',
        'libelle_fr' => 'Garage', 'libelle_en' => 'Garage', 'ordre' => 2, 'visible' => true,
    ]);

    $this->lesDeux = Bien::factory()->create(['titre_fr' => 'Villa complète', 'slug' => 'villa-complete', 'statut' => Bien::PUBLIE]);
    $this->piscineSeule = Bien::factory()->create(['titre_fr' => 'Villa baignée', 'slug' => 'villa-baignee', 'statut' => Bien::PUBLIE]);
    $this->rien = Bien::factory()->create(['titre_fr' => 'Villa nue', 'slug' => 'villa-nue', 'statut' => Bien::PUBLIE]);

    $this->lesDeux->equipements()->sync([$this->piscine->id, $this->garage->id]);
    $this->piscineSeule->equipements()->sync([$this->piscine->id]);
});

it('ne garde que les biens portant TOUS les equipements coches', function () {
    // Cocher « Piscine » puis « Garage » doit RESTREINDRE. Un filtre « au
    // moins un critere » aurait fait grandir la liste a mesure que le
    // visiteur croit preciser sa recherche.
    Livewire::test(CatalogueDesBiens::class)
        ->set('equipements', [$this->piscine->id])
        ->assertSee('Villa complète')
        ->assertSee('Villa baignée')
        ->assertDontSee('Villa nue')
        ->set('equipements', [$this->piscine->id, $this->garage->id])
        ->assertSee('Villa complète')
        ->assertDontSee('Villa baignée')
        ->assertDontSee('Villa nue');
});

it('se cumule aux filtres du haut de page au lieu de les remplacer', function () {
    $this->lesDeux->update(['zone' => 'cocody']);
    $this->piscineSeule->update(['zone' => 'cocody']);

    $ailleurs = Bien::factory()->create([
        'titre_fr' => 'Villa lointaine', 'slug' => 'villa-lointaine',
        'statut' => Bien::PUBLIE, 'zone' => 'marcory',
    ]);
    $ailleurs->equipements()->sync([$this->piscine->id, $this->garage->id]);

    Livewire::test(CatalogueDesBiens::class)
        ->set('zone', 'cocody')
        ->set('equipements', [$this->piscine->id, $this->garage->id])
        ->assertSee('Villa complète')
        ->assertDontSee('Villa lointaine')
        ->assertDontSee('Villa baignée');
});

it('ignore un equipement rendu invisible depuis le backoffice', function () {
    // Sans cette precaution, une case retiree du panneau continuerait de
    // restreindre la recherche sans que rien ne l'explique a l'ecran.
    $this->garage->update(['visible' => false]);

    Livewire::test(CatalogueDesBiens::class)
        ->set('equipements', [$this->garage->id])
        ->assertSee('Villa complète')
        ->assertSee('Villa baignée')
        ->assertSee('Villa nue');
});

it('ignore un identifiant qui ne designe pas un equipement', function () {
    // Il vient du navigateur : une zone empruntee ne doit pas vider la page
    // sans explication.
    $zone = Referentiel::deLaFamille('zones')->first();

    Livewire::test(CatalogueDesBiens::class)
        ->set('equipements', [$zone->id])
        ->assertSee('Villa nue');
});

it('decoche tout le panneau sans toucher aux listes du haut', function () {
    Livewire::test(CatalogueDesBiens::class)
        ->set('zone', 'cocody')
        ->set('equipements', [$this->piscine->id])
        ->call('viderLesEquipements')
        ->assertSet('equipements', [])
        ->assertSet('zone', 'cocody');
});

/* ------------------------------------------------ repli sur telephone */

it('ouvre le panneau replie sans toucher aux criteres deja coches', function () {
    // L'etat du repli est porte par le serveur et non par le navigateur : ce
    // composant se re-rend a chaque case cochee, et une classe posee cote
    // client serait effacee par la reconciliation du DOM — le panneau se
    // refermerait au premier clic, en plein choix de criteres.
    Livewire::test(CatalogueDesBiens::class)
        ->assertSet('panneauDeploye', false)
        ->set('equipements', [$this->piscine->id])
        ->call('basculerLePanneau')
        ->assertSet('panneauDeploye', true)
        ->assertSet('equipements', [$this->piscine->id])
        ->call('basculerLePanneau')
        ->assertSet('panneauDeploye', false)
        ->assertSet('equipements', [$this->piscine->id]);
});

it('garde les criteres coches quand le panneau se referme', function () {
    // Referme, le panneau ne montre plus ses cases : le filtre doit rester
    // actif, sinon le catalogue changerait sous les yeux du visiteur au
    // moment ou il replie le panneau.
    Livewire::test(CatalogueDesBiens::class)
        ->set('equipements', [$this->piscine->id, $this->garage->id])
        ->call('basculerLePanneau')
        ->assertSee('Villa complète')
        ->assertDontSee('Villa nue');
});

it('annonce l etat du repli aux technologies d assistance', function () {
    // aria-expanded est la seule chose qui dise a un lecteur d'ecran si la
    // liste est deroulee : sans lui, le bouton est muet.
    $this->get('/biens')
        ->assertOk()
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('aria-controls="equip-list"', false);
});

it('affiche les cases du panneau dans la langue du site', function () {
    $this->get('/biens')
        ->assertOk()
        ->assertSee('Piscine')
        ->assertSee('Garage');
});

it('montre les equipements du bien sur sa fiche', function () {
    $this->get('/biens/villa-complete')
        ->assertOk()
        ->assertSee('Piscine')
        ->assertSee('Garage');
});
