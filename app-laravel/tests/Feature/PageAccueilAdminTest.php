<?php

use App\Livewire\Admin\PageAccueil;
use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Models\ReglageDeSection;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Premier ecran de la refonte : une page d'administration par page publique.
 *
 * Les anciens ecrans restent en place et ecrivent LES MEMES tables. Ces
 * controles verifient donc surtout que le nouvel ecran ecrit bien la ou il
 * faut, et qu'il n'annonce rien qu'il ne fasse.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('affiche les huit modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->assertOk()
        ->assertSeeInOrder([
            'Bannière principale',
            'Bande déroulante',
            'Services',
            'Annonce',
            "Bandeau d'appel à l'action",
            'Articles',
            'Avis clients',
            'Partenaires',
        ]);
});

it('ouvre le module demande', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'partenaires')
        ->assertSet('module', 'partenaires')
        ->assertSee('Partenaires affichés');
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete d une section', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'hero')
        ->set('entete.titre_fr', 'Un tout nouveau titre')
        ->set('entete.chapo_fr', 'Une accroche revue.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', 'home.hero')->first();

    expect($section->titre_fr)->toBe('Un tout nouveau titre')
        ->and($section->chapo_fr)->toBe('Une accroche revue.');
});

it('enregistre un encart avec ses dates de diffusion', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'cta')
        ->set('encart.titre_fr', 'Prêt à concrétiser ?')
        ->set('encart.cible_bouton', '/biens')
        ->set('encart.visible', true)
        ->set('encart.diffusion_de', '2026-09-01')
        ->set('encart.diffusion_a', '2026-09-30')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $encart = Encart::where('slug', 'accueil')->first();

    expect($encart->titre_fr)->toBe('Prêt à concrétiser ?')
        ->and($encart->cible_bouton)->toBe('/biens')
        ->and($encart->visible)->toBeTrue();
});

it('refuse une fin de diffusion anterieure au debut', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'annonce')
        ->set('encart.diffusion_de', '2026-09-30')
        ->set('encart.diffusion_a', '2026-09-01')
        ->call('enregistrer')
        ->assertHasErrors('encart.diffusion_a');
});

it('enregistre l apparence de la bande deroulante', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'bandeau')
        ->set('bandeau.fond', 'clair')
        ->set('bandeau.separateur', '—')
        ->set('bandeau.casse', 'normale')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', CommuneDuBandeau::SECTION)->first();

    expect($section->option('fond'))->toBe('clair')
        ->and($section->option('separateur'))->toBe('—')
        ->and($section->option('casse'))->toBe('normale');
});

it('bascule l affichage d un element de collection', function () {
    $temoignage = Temoignage::factory()->create(['visible' => true]);

    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'temoignages')
        ->call('basculer', 'temoignages', $temoignage->id);

    expect($temoignage->fresh()->visible)->toBeFalse();
});

it('refuse une famille de collection inconnue', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('basculer', 'utilisateurs', 1)
        ->assertNotFound();
});

/**
 * Un lecteur consulte, il ne modifie pas. Le controle est sur l'ACTION et non
 * seulement sur l'affichage du bouton : Livewire expose toute methode publique
 * au navigateur, masquer le bouton ne protegerait rien.
 */
it('interdit toute ecriture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageAccueil::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();

    Livewire::actingAs($lecteur)->test(PageAccueil::class)
        ->call('basculer', 'temoignages', 1)
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    $sansRole = User::factory()->create();

    Livewire::actingAs($sansRole)->test(PageAccueil::class)->assertForbidden();
});

/**
 * L'ordre des modules est fixe dans le gabarit public : l'ecran ne doit pas
 * proposer de poignee de deplacement, qui ne deplacerait rien.
 */
it('ne propose pas de reordonner les modules', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->assertDontSee('wire:sortable', false)
        ->assertDontSee('⋮⋮', false);
});

/** Le bloc des articles doit dire qu'il ne se choisit pas. */
it('annonce que les articles ne se choisissent pas ici', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'articles')
        ->assertSee('ne se choisissent pas ici', false);
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.accueil'), false);
});
