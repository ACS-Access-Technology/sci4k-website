<?php

/*
 * La fiche d'un bien se ferme au clavier autant qu'a la souris.
 *
 * Constate en recette, a la vraie frappe : la touche Echap ne fermait rien. La
 * fenetre ne portait que deux sorties a la souris — la croix, et le clic sur le
 * fond. Un visiteur au clavier n'avait aucun moyen d'en sortir.
 *
 * Le modificateur « .window » n'est pas un detail : sans lui, l'ecouteur ne
 * repond que si le focus se trouve DANS la fenetre. Or rien ne l'y place a
 * l'ouverture — le focus reste sur le bouton « Voir la fiche », derriere. La
 * touche serait donc restee sans effet malgre l'ecouteur.
 */

use App\Livewire\Public\CatalogueDesBiens;
use App\Models\Bien;
use Livewire\Livewire;

it('ferme la fiche a la touche Echap', function () {
    $bien = Bien::factory()->create(['statut' => 'publie']);

    Livewire::test(CatalogueDesBiens::class)
        ->call('ouvrirBien', $bien->id)
        ->assertSet('bienOuvert.id', $bien->id)
        ->assertSeeHtml('wire:keydown.escape.window="fermerBien"')
        ->call('fermerBien')
        ->assertSet('bienOuvert', null);
});
