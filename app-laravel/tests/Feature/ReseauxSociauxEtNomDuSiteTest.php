<?php

/*
 * Un lien de reseau social ne touche pas au nom du site.
 *
 * Signale deux fois par le client : « lorsque je mets le lien d'un reseau il
 * prend la place du nom du site sur tout le site ». Ce test cherche le defaut
 * la ou il serait cote serveur — enregistrement, lecture, rendu.
 */

use App\Livewire\Admin\Configuration;
use App\Models\Parametre;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('garde le nom du site quand on enregistre un lien de reseau', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.nom_du_site', 'SCI4K')
        ->set('valeurs.youtube', 'https://www.youtube.com/@sci4k')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('nom_du_site'))->toBe('SCI4K')
        ->and(Parametre::lire('youtube'))->toBe('https://www.youtube.com/@sci4k');
});

it('n affiche pas le lien du reseau a la place du nom sur le site', function () {
    Parametre::poser('nom_du_site', 'SCI4K', 'general');
    Parametre::poser('youtube', 'https://www.youtube.com/@sci4k', 'social');

    foreach (['/', '/biens', '/services', '/contact'] as $adresse) {
        $corps = $this->get($adresse)->assertOk()->getContent();

        // Le nom doit paraitre dans le titre de la page ; l'adresse YouTube ne
        // doit jamais s'y substituer.
        expect($corps)->toContain('SCI4K');
        expect(preg_match('/<title>[^<]*youtube[^<]*<\/title>/i', $corps))->toBe(0, "L'adresse YouTube s'affiche dans le titre de {$adresse}.");
    }
});

it('rouvre l ecran avec le nom du site intact', function () {
    Parametre::poser('nom_du_site', 'SCI4K', 'general');
    Parametre::poser('youtube', 'https://www.youtube.com/@sci4k', 'social');

    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->assertSet('valeurs.nom_du_site', 'SCI4K')
        ->assertSet('valeurs.youtube', 'https://www.youtube.com/@sci4k');
});
