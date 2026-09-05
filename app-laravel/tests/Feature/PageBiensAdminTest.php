<?php

use App\Livewire\Admin\BienFormulaire;
use App\Livewire\Admin\BienListe;
use App\Livewire\Admin\PageBiens;
use App\Models\Bien;
use App\Models\Referentiel;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Troisieme ecran de la refonte : la page Biens immobiliers.
 *
 * Trois modules seulement, la page en ayant trois. Le module Filtres embarque
 * l'ecran des referentiels : les cinq familles qu'il edite SONT le vocabulaire
 * des filtres de /biens et des fiches de bien.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('affiche les trois modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->assertOk()
        ->assertSeeInOrder(['Bannière', 'Filtres', 'Catalogue']);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete de la banniere et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'banniere')
        ->set('entete.titre_fr', 'Nos biens à Abidjan')
        ->set('entete.chapo_fr', 'Une accroche revue.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'biens.page')->value('titre_fr'))
        ->toBe('Nos biens à Abidjan');

    $this->get(route('biens.index'))
        ->assertOk()
        ->assertSee('Nos biens à Abidjan', false);
});

/**
 * Le panneau de filtres n'affiche que son titre : proposer une etiquette et
 * une accroche aurait ete offrir deux champs dont rien ne montre le contenu.
 */
it('ne propose que le titre sur le module Filtres', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'filtres')
        ->html();

    expect($rendu)->toContain('wire:model="entete.titre_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.chapo_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.etiquette_fr"');
});

/**
 * Le catalogue n'a pas d'en-tete de section : la page n'en affiche pas
 * au-dessus de la grille. L'ecran ne doit donc montrer aucun formulaire.
 */
it('n affiche pas de formulaire sur le module Catalogue', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'catalogue')
        ->assertDontSee('wire:model="entete.titre_fr"', false);
});

/*
 * Ce test exigeait auparavant que le module « Catalogue » REFUSE
 * l'enregistrement, faute de section. Il n'en avait pas parce qu'il n'affichait
 * aucun en-tete — et c'est precisement ce qui laissait les textes de la grille
 * (« Vendu », « Voir la fiche », « Aucun bien ne correspond… ») figes dans la
 * vue, modifiables nulle part. Le module porte desormais une section, et le
 * test dit ce qui est vrai maintenant.
 */
it('enregistre les textes du catalogue', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'catalogue')
        ->set('textes.libelle_fiche_fr', 'Découvrir ce bien')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'biens.catalog')->first()?->texteBilingue('libelle_fiche', 'fr'))
        ->toBe('Découvrir ce bien');
});

/* ------------------------------------------------------------------ */
/* Les ecrans embarques */
/* ------------------------------------------------------------------ */

it('embarque l ecran des referentiels dans le module Filtres', function () {
    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'filtres')
        ->assertSee('wire:name="admin.referentiels"', false);
});

it('embarque la liste des biens dans le module Catalogue', function () {
    Bien::factory()->create();

    Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', 'catalogue')
        ->assertSee('wire:name="admin.bien-liste"', false);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageBiens::class)
        ->call('ouvrir', $module)
        ->html();

    foreach (['/admin/biens', route('admin.referentiels')] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse);
    }
})->with(['banniere', 'filtres', 'catalogue']);

/* ------------------------------------------------------------------ */
/* L'edition d'un bien, sur place */
/* ------------------------------------------------------------------ */

it('ouvre la fiche d un bien sur place', function () {
    $bien = Bien::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(BienListe::class, ['embarque' => true])
        ->assertSet('formulaireOuvert', null)
        ->call('ouvrirEdition', $bien->id)
        ->assertSet('formulaireOuvert', $bien->id)
        ->assertSee('wire:name="admin.bien-formulaire"', false);
});

it('ouvre la creation d un bien sur place', function () {
    Livewire::actingAs($this->admin)
        ->test(BienListe::class, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertSet('formulaireOuvert', 'creation');
});

it('refuse un identifiant de bien inexistant', function () {
    Livewire::actingAs($this->admin)
        ->test(BienListe::class, ['embarque' => true])
        ->call('ouvrirEdition', 99999)
        ->assertNotFound();
});

it('interdit l ouverture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');
    $bien = Bien::factory()->create();

    Livewire::actingAs($lecteur)
        ->test(BienListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $bien->id)
        ->assertForbidden();
});

/**
 * Enregistre depuis la liste, le formulaire ne redirige pas : il previent la
 * liste, qui se referme. Une redirection ferait quitter l'ecran de page au
 * milieu d'une modification.
 *
 * Le type et la zone sont valides CONTRE LE REFERENTIEL : sans ces deux
 * lignes, le formulaire refuse un bien pourtant valide.
 */
it('ne redirige pas quand le formulaire de bien est embarque', function () {
    $bien = Bien::factory()->create();

    Referentiel::create(['famille' => 'types_de_bien', 'valeur' => $bien->type, 'libelle_fr' => 'Villa', 'libelle_en' => 'Villa']);
    Referentiel::create(['famille' => 'zones', 'valeur' => $bien->zone, 'libelle_fr' => 'Cocody', 'libelle_en' => 'Cocody']);

    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $bien, 'embarque' => true])
        ->set('prixUnite', 'total')
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('bloc-enregistre');
});

/* ------------------------------------------------------------------ */
/* Droits et acces */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur sur l ecran de page', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageBiens::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PageBiens::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.biens'), false);
});
