<?php

use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\CommuneBandeauEnsemble;
use App\Models\ChiffreCle;
use App\Models\CommuneDuBandeau;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Les OPTIONS d'apparence d'un bloc survivent a l'embarquement.
 *
 * Le panneau « Réglages du bloc » portait deux choses : l'en-tete de la
 * section, que l'ecran de page edite desormais lui-meme, et l'apparence du
 * bloc — casse et separateur de la banderole, animation des chiffres cles —
 * que rien d'autre n'edite. Masquer le panneau entier a l'embarquement aurait
 * rendu ces cinq reglages inatteignables des le retrait des ecrans par type de
 * contenu.
 *
 * C'est le meme piege que la mise en page du processus, que « Pages du site →
 * Services » a du reprendre a son compte.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('propose encore les reglages d apparence de la banderole', function () {
    CommuneDuBandeau::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CommuneBandeauEnsemble::class, ['embarque' => true])
        ->assertSee('wire:model="reglages.casse"', false)
        ->assertSee('wire:model="reglages.separateur"', false)
        ->assertSee('wire:model="reglages.fond"', false);
});

it('propose encore les reglages d animation des chiffres cles', function () {
    ChiffreCle::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ChiffreCleEnsemble::class, ['embarque' => true])
        ->assertSee('wire:model="reglages.animer"', false)
        ->assertSee('wire:model="reglages.duree_animation"', false);
});

it('enregistre une option d apparence depuis l editeur embarque', function () {
    CommuneDuBandeau::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(CommuneBandeauEnsemble::class, ['embarque' => true])
        ->set('reglages.separateur', '—')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', CommuneDuBandeau::SECTION)->first()->option('separateur'))
        ->toBe('—');
});

/**
 * L'en-tete, elle, reste masquee : le module de la page l'edite sous le meme
 * slug, et deux formulaires pour une meme donnee se seraient ecrases.
 */
it('masque l en-tete de section dans l editeur embarque', function () {
    ChiffreCle::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ChiffreCleEnsemble::class, ['embarque' => true])
        ->assertDontSee('wire:model="reglages.titre_fr"', false)
        ->assertDontSee('wire:model="reglages.chapo_fr"', false);
});
