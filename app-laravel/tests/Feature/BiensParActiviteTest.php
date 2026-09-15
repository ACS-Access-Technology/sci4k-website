<?php

use App\Livewire\Admin\BienFormulaire;
use App\Livewire\Public\CatalogueDesBiens;
use App\Models\Bien;
use App\Models\DemandeDeVisite;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les biens rattaches aux activites de l'agence.
 *
 * LE DEFAUT CORRIGE : le site annoncait six metiers et n'en montrait qu'un. Le
 * catalogue est range par type de bien et par offre — des categories qui disent
 * « quel genre de batiment », jamais « laquelle de nos six activites ». Un
 * visiteur lisant « Administration de biens » n'avait donc rien a regarder.
 *
 * Les tests portent sur la decision qui structure la fonctionnalite : une
 * REALISATION — un immeuble bati ou administre par l'agence — est publiee sans
 * etre au catalogue. Elle garde sa fiche et ses photos, mais n'a ni prix ni
 * visite a proposer. C'est la seule distinction qui pouvait casser l'existant,
 * puisque sept requetes du site supposaient jusqu'ici que « publie » et
 * « au catalogue » etaient une seule et meme chose.
 */
beforeEach(function () {
    $this->foncier = Service::factory()->create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'ordre' => 1]);
    $this->administration = Service::factory()->create(['slug' => 'administration', 'nom_fr' => 'Administration de biens', 'ordre' => 2]);

    $this->terrain = Bien::factory()->create([
        'titre_fr' => 'Lot Bonoua', 'slug' => 'lot-bonoua',
        'statut' => Bien::PUBLIE, 'type' => 'terrain', 'offre' => Bien::VENTE, 'zone' => 'bingerville',
    ]);

    // L'immeuble que l'agence administre : une reference, pas une offre.
    $this->reference = Bien::factory()->create([
        'titre_fr' => 'Résidence confiée', 'slug' => 'residence-confiee',
        'statut' => Bien::PUBLIE, 'type' => 'immeuble', 'offre' => Bien::LOCATION, 'zone' => 'bingerville',
        'est_une_realisation' => true,
    ]);

    $this->terrain->services()->sync([$this->foncier->id]);
    $this->reference->services()->sync([$this->administration->id]);
});

it('montre sous chaque activite les biens qu\'on lui a rattaches', function () {
    $reponse = $this->get('/services');

    $reponse->assertOk()
        ->assertSee('Lot Bonoua')
        ->assertSee('Résidence confiée');
});

it('ecarte une realisation du catalogue, qui ne vend ni ne loue', function () {
    // Le coeur de la distinction : publiee, donc visible sur le site, mais
    // absente de la grille des biens a vendre ou a louer.
    expect(Bien::publies()->pluck('slug'))->toContain('residence-confiee')
        ->and(Bien::duCatalogue()->pluck('slug'))->not->toContain('residence-confiee');

    Livewire::test(CatalogueDesBiens::class)
        ->assertSee('Lot Bonoua')
        ->assertDontSee('Résidence confiée');
});

it('garde la fiche d\'une realisation accessible', function () {
    // Elle quitte le catalogue, pas le site : c'est meme sa seule vitrine,
    // avec la fenetre de son activite.
    $this->get('/biens/residence-confiee')->assertOk()->assertSee('Résidence confiée');
});

it('ne propose pas une realisation parmi les biens de la meme zone', function () {
    // Les deux biens partagent la zone. Presenter une realisation sous
    // « dans la meme zone » reviendrait a l'annoncer comme une offre.
    $this->get('/biens/lot-bonoua')
        ->assertOk()
        ->assertDontSee('Résidence confiée');
});

it('refuse de rattacher une demande de visite a une realisation', function () {
    // Une realisation ne se visite pas, elle se montre. La demande reste
    // acceptee — mieux vaut un rendez-vous sans bien qu'un prospect perdu —
    // mais elle ne se rattache pas a ce bien.
    $this->postJson('/visites', [
        'nom' => 'Awa Koné',
        'telephone' => '+225 07 00 00 00 00',
        'email' => 'awa@example.test',
        'bien' => 'residence-confiee',
    ])->assertSuccessful();

    expect(DemandeDeVisite::latest('id')->first()?->bien_id)->toBeNull();

    // Contre-epreuve : un bien du catalogue, lui, se rattache bien.
    $this->postJson('/visites', [
        'nom' => 'Awa Koné',
        'telephone' => '+225 07 00 00 00 00',
        'bien' => 'lot-bonoua',
    ])->assertSuccessful();

    expect(DemandeDeVisite::latest('id')->first()?->bien_id)->toBe($this->terrain->id);
});

it('enregistre depuis le backoffice les activites cochees et le caractere de reference', function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $this->seed(ReferentielsSeeder::class);

    $editeur = User::factory()->create(['statut' => User::ACTIF]);
    $editeur->assignRole('editeur');

    Livewire::actingAs($editeur)
        ->test(BienFormulaire::class)
        // Le bloc doit etre RENDU, et pas seulement accepte par le composant :
        // il vit dans l'onglet « general », derriere une condition.
        ->assertSee('Activités illustrées')
        ->assertSee('Administration de biens')
        ->assertSee('Réalisation de référence')
        ->set('titreFr', 'Immeuble confié')
        ->set('slug', 'immeuble-confie')
        ->set('type', 'immeuble')
        ->set('zone', 'cocody')
        ->set('offre', Bien::LOCATION)
        ->set('services', [$this->administration->id])
        ->set('estUneRealisation', true)
        ->call('enregistrer')
        ->assertHasNoErrors();

    $bien = Bien::where('slug', 'immeuble-confie')->firstOrFail();

    expect($bien->est_une_realisation)->toBeTrue()
        ->and($bien->services()->pluck('services.id')->all())->toBe([$this->administration->id]);
});

it('refuse une activite qui n\'existe pas', function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $this->seed(ReferentielsSeeder::class);

    $editeur = User::factory()->create(['statut' => User::ACTIF]);
    $editeur->assignRole('editeur');

    // L'identifiant vient d'une case a cocher, donc du navigateur : rien
    // n'empeche de le remplacer avant l'envoi.
    Livewire::actingAs($editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Villa forgée')
        ->set('slug', 'villa-forgee')
        ->set('type', 'villa')
        ->set('zone', 'cocody')
        ->set('offre', Bien::VENTE)
        ->set('services', [999999])
        ->call('enregistrer')
        ->assertHasErrors('services.0');
});

it('laisse vide une activite a laquelle rien n\'est rattache', function () {
    // Une activite sans bien ne doit pas emprunter ceux d'une autre : c'est
    // exactement ce qu'aurait produit une regle deduite des colonnes.
    $vide = Service::factory()->create(['slug' => 'construction', 'nom_fr' => 'Construction', 'ordre' => 3]);

    expect($vide->illustrations()->count())->toBe(0);
});
