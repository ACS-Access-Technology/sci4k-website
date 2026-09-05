<?php

use App\Livewire\Admin\PageServices;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Quatrieme ecran de la refonte : la page Services.
 *
 * Point d'attention propre a cette page : la mise en page du processus vivait
 * dans le panneau « Reglages du bloc » de l'editeur des etapes, que
 * l'embarquement masque. Le module la reprend, sans quoi elle serait devenue
 * inatteignable.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('affiche les quatre modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->assertOk()
        ->assertSeeInOrder(['Bannière', 'Services', 'Processus', 'Annonce']);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete de la banniere et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'banniere')
        ->set('entete.titre_fr', 'Ce que nous faisons')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('Ce que nous faisons', false);
});

/* ------------------------------------------------------------------ */
/* La mise en page du processus */
/* ------------------------------------------------------------------ */

it('enregistre la mise en page du processus et l applique sur le site', function () {
    EtapeProcessus::factory()->create();

    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'processus')
        ->set('options.mise_en_page', 'liste')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'services.process')->first()->option('mise_en_page'))
        ->toBe('liste');

    $this->get(route('services.index'))->assertSee('process-liste', false);
});

/**
 * Une option n'accepte que les valeurs qu'elle propose : une valeur forgee
 * depuis le navigateur ferait retomber la page sur son defaut sans que
 * personne ne comprenne pourquoi.
 */
it('refuse une mise en page inconnue', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'processus')
        ->set('options.mise_en_page', 'carrousel')
        ->call('enregistrer')
        ->assertHasErrors('options.mise_en_page');
});

it('n affiche le choix de mise en page que sur le processus', function () {
    $avecOption = Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'processus')->html();

    $sansOption = Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'banniere')->html();

    expect($avecOption)->toContain('wire:model="options.mise_en_page"')
        ->and($sansOption)->not->toContain('wire:model="options.mise_en_page"');
});

/* ------------------------------------------------------------------ */
/* Modules sans en-tete */
/* ------------------------------------------------------------------ */

it('n affiche pas de formulaire sur les modules sans en-tete', function (string $module) {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', $module)
        ->assertDontSee('wire:model="entete.titre_fr"', false);
})->with(['services', 'annonce']);

it('refuse d enregistrer un module sans section', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'services')
        ->call('enregistrer')
        ->assertNotFound();
});

/* ------------------------------------------------------------------ */
/* Les ecrans embarques */
/* ------------------------------------------------------------------ */

it('embarque la liste des services', function () {
    Service::factory()->create();

    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'services')
        ->assertSee('wire:name="admin.service-liste"', false);
});

it('embarque l editeur des etapes', function () {
    EtapeProcessus::factory()->create();

    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'processus')
        ->assertSee('wire:name="admin.etape-processus-ensemble"', false);
});

/** L'annonce embarque son propre formulaire, image comprise. */
it('embarque le formulaire de l encart', function () {
    Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', 'annonce')
        ->assertSee('wire:name="admin.encart-formulaire"', false);

    expect(Encart::where('slug', 'services.annonce')->exists())->toBeTrue();
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageServices::class)
        ->call('ouvrir', $module)
        ->html();

    foreach ([
        '/admin/services',
        '/admin/etapes-processus',
        '/admin/encarts',
    ] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse);
    }
})->with(['banniere', 'services', 'processus', 'annonce']);

/* ------------------------------------------------------------------ */
/* Droits et acces */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageServices::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PageServices::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.services'), false);
});
