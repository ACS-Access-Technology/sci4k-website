<?php

use App\Livewire\Admin\Menus;
use App\Models\EntreeDeMenu;
use App\Models\Parametre;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['administrateur', 'editeur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('refuse l ecran a un editeur', function () {
    $this->actingAs($this->editeur)->get('/admin/menus')->assertForbidden();

    Livewire::actingAs($this->editeur)->test(Menus::class)->assertForbidden();
});

it('ouvre l ecran a un administrateur', function () {
    $this->actingAs($this->admin)->get('/admin/menus')->assertOk();
});

it('enregistre une entree ajoutee', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->call('ajouter', 'principal')
        ->set('entrees.principal.neuf-1.libelle_fr', 'Nos biens')
        ->set('entrees.principal.neuf-1.cible', '/biens.html')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(EntreeDeMenu::where('menu', 'principal')->where('cible', '/biens.html')->exists())->toBeTrue();
});

/*
 * La cible est le point sensible : elle finit dans un href, sur toutes les
 * pages du site.
 */
it('refuse une cible qui executerait du code au clic', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->call('ajouter', 'principal')
        ->set('entrees.principal.neuf-1.libelle_fr', 'Piège')
        ->set('entrees.principal.neuf-1.cible', 'javascript:alert(1)')
        ->call('enregistrer')
        ->assertHasErrors('entrees.principal.neuf-1.cible');

    expect(EntreeDeMenu::count())->toBe(0);
});

it('refuse un menu invente', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->call('ajouter', 'menu-fantome')
        ->assertNotFound();
});

it('ignore un menu forge glisse dans l enregistrement', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->set('entrees.menu-fantome.neuf-9', [
            'libelle_fr' => 'Intrus',
            'libelle_en' => '',
            'cible' => '/intrus',
            'visible' => '1',
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(EntreeDeMenu::where('cible', '/intrus')->exists())->toBeFalse();
});

it('numerote les rangs menu par menu', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->call('ajouter', 'principal')
        ->set('entrees.principal.neuf-1.libelle_fr', 'Accueil')
        ->set('entrees.principal.neuf-1.cible', '/')
        ->call('ajouter', 'pied_legal')
        ->set('entrees.pied_legal.neuf-1.libelle_fr', 'Mentions')
        ->set('entrees.pied_legal.neuf-1.cible', '/mentions-legales.html')
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Le premier lien legal porte le rang 1, et non celui qui suit la derniere
    // entree du menu principal.
    expect(EntreeDeMenu::where('menu', 'pied_legal')->value('ordre'))->toBe(1);
});

it('ne deplace pas une entree empruntee a un autre menu', function () {
    $entree = EntreeDeMenu::factory()->create(['menu' => 'principal', 'cible' => '/', 'ordre' => 1]);

    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->set("entrees.pied_legal.{$entree->id}", [
            'libelle_fr' => 'Détourné',
            'libelle_en' => '',
            'cible' => '/detourne',
            'visible' => '1',
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($entree->fresh()->menu)->toBe('principal')
        ->and($entree->fresh()->cible)->toBe('/');
});

it('rappelle les colonnes automatiques sans champ de saisie', function () {
    Parametre::poser('telephone', '+225 07 00 00 00', 'contact');

    $corps = Livewire::actingAs($this->admin)->test(Menus::class)->html();

    expect($corps)->toContain(__('Colonnes remplies automatiquement'))
        ->and($corps)->toContain('+225 07 00 00 00')
        ->and($corps)->not->toContain('wire:model="coordonnees');
});

it('n enregistre pas deux fois les entrees ajoutees', function () {
    $composant = Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->call('ajouter', 'principal')
        ->set('entrees.principal.neuf-1.libelle_fr', 'Accueil')
        ->set('entrees.principal.neuf-1.cible', '/')
        ->call('enregistrer');

    $composant->call('enregistrer')->assertHasNoErrors();

    expect(EntreeDeMenu::where('menu', 'principal')->count())->toBe(1);
});
