<?php

use App\Livewire\Admin\ServiceFormulaire;
use App\Livewire\Admin\ServiceListe;
use App\Livewire\Admin\TemoignageFormulaire;
use App\Livewire\Admin\TemoignageListe;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Embarquee dans un ecran de page, une liste ouvre ses formulaires SUR PLACE.
 *
 * La refonte rassemble l'administration par page publique : envoyer l'editeur
 * sur une autre adresse pour modifier un avis annulerait le benefice, et
 * rendrait le nouvel ecran dependant d'ecrans voues a disparaitre.
 *
 * Sur son propre ecran, la liste continue de renvoyer vers ses adresses
 * d'edition : un lien se partage, se met en favori et s'ouvre dans un onglet.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('ouvre le formulaire sur place quand la liste est embarquee', function () {
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->assertSet('formulaireOuvert', null)
        ->call('ouvrirEdition', $temoignage->id)
        ->assertSet('formulaireOuvert', $temoignage->id)
        ->assertSee('wire:name="admin.temoignage-formulaire"', false);
});

it('ouvre la creation sur place', function () {
    Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertSet('formulaireOuvert', 'creation')
        ->assertSee('wire:name="admin.temoignage-formulaire"', false);
});

/** La liste ne doit proposer AUCUN lien de sortie. */
it('ne propose plus de lien vers les ecrans d edition', function () {
    Temoignage::factory()->create();

    $rendu = Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->html();

    expect($rendu)->not->toContain('/admin/temoignages');
});

/**
 * Le bouton de suppression vivait dans la branche NON embarquee du gabarit
 * commun : il disparaissait des que la liste etait ouverte depuis un ecran de
 * page — c'est-a-dire partout, depuis le retrait des ecrans par type.
 */
it('propose la suppression sur une collection qui accepte le retrait', function () {
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->assertSee('supprimer('.$temoignage->id.')', false);
});

it('refuse un identifiant qui n appartient pas au bloc', function () {
    Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->call('ouvrirEdition', 99999)
        ->assertNotFound();
});

it('interdit l ouverture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($lecteur)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $temoignage->id)
        ->assertForbidden();

    Livewire::actingAs($lecteur)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertForbidden();
});

/**
 * Enregistre depuis une liste, le formulaire ne redirige pas : il previent la
 * liste, qui se referme. Une redirection ferait quitter la page d'accueil au
 * milieu d'une modification.
 */
it('ne redirige pas quand il est embarque', function () {
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TemoignageFormulaire::class, ['element' => $temoignage, 'embarque' => true])
        ->set('valeurs.auteur', 'Awa K.')
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('bloc-enregistre');

    expect($temoignage->fresh()->auteur)->toBe('Awa K.');
});

it('se referme sur l evenement d enregistrement', function () {
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(TemoignageListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $temoignage->id)
        ->assertSet('formulaireOuvert', $temoignage->id)
        ->call('fermerFormulaire')
        ->assertSet('formulaireOuvert', null);
});

/** Embarque, le formulaire n'affiche pas son propre fil d'Ariane. */
it('n affiche pas le fil d Ariane quand il est embarque', function () {
    $temoignage = Temoignage::factory()->create();

    $rendu = Livewire::actingAs($this->admin)
        ->test(TemoignageFormulaire::class, ['element' => $temoignage, 'embarque' => true])
        ->html();

    expect($rendu)->not->toContain('aria-label="Fil d’Ariane"')
        ->and($rendu)->toContain('Annuler');
});

/**
 * ServiceFormulaire n'herite pas de FormulaireDeBloc : son modele s'appelle
 * « service » et non « element ».
 *
 * Lui passer « element » revenait a ne rien lui passer du tout, et il ouvrait
 * son formulaire de CREATION a chaque demande de modification. Le nom du
 * parametre est desormais declare par chaque liste, et ce controle le fixe.
 */
it('ouvre le service demande, et non un formulaire de creation', function () {
    $service = Service::factory()->create(['nom_fr' => 'Gestion locative']);

    $composant = Livewire::actingAs($this->admin)
        ->test(ServiceFormulaire::class, ['service' => $service, 'embarque' => true]);

    $composant->assertSet('estCreation', false);
    expect($composant->html())->toContain('Modifier le service');
});

it('declare le bon nom de parametre pour chaque liste', function (string $liste, string $attendu) {
    $methode = new ReflectionMethod($liste, 'parametreDuFormulaire');
    $methode->setAccessible(true);

    expect($methode->invoke(new $liste))->toBe($attendu);
})->with([
    [ServiceListe::class, 'service'],
    [TemoignageListe::class, 'element'],
]);

/** Embarque, le formulaire de service ne propose aucune sortie non plus. */
it('n offre aucune sortie depuis le formulaire de service embarque', function () {
    $service = Service::factory()->create();

    $rendu = Livewire::actingAs($this->admin)
        ->test(ServiceFormulaire::class, ['service' => $service, 'embarque' => true])
        ->html();

    expect($rendu)->not->toContain('/admin/services');
});
