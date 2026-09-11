<?php

/*
 * L'editeur VOIT ce qu'il vient de choisir, et VOIT pourquoi on lui refuse.
 *
 * Signale par le client : « quand j'ajoute les photos d'un bien on ne les voit
 * pas ». Mesure sur le serveur : ses fichiers etaient bel et bien montes — cinq
 * JPEG dans le dossier temporaire de Livewire — mais l'ecran n'en montrait
 * rien, et storage/app/public/biens n'existait meme pas.
 *
 * Deux manques se combinaient :
 *
 * 1. Choisir des fichiers ne produisait AUCUN retour visible. Les formulaires
 *    d'article et de service previsualisent le fichier choisi ; celui d'un bien
 *    ne montrait que les photos DEJA enregistrees. L'editeur choisissait ses
 *    photos, ne voyait rien changer, et concluait a une panne.
 *
 * 2. Ce formulaire a QUATRE ONGLETS. Un refus de validation portant sur un
 *    champ d'un autre onglet s'affichait dans une zone masquee — le meme piege
 *    que les onglets de langue, au meme endroit du code.
 */

use App\Livewire\Admin\BienFormulaire;
use App\Models\Bien;
use App\Models\Referentiel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    Referentiel::create(['famille' => 'types_de_bien', 'valeur' => 'villa', 'libelle_fr' => 'Villa', 'libelle_en' => 'Villa', 'ordre' => 1, 'visible' => true]);
    Referentiel::create(['famille' => 'zones', 'valeur' => 'cocody', 'libelle_fr' => 'Cocody', 'libelle_en' => 'Cocody', 'ordre' => 1, 'visible' => true]);

    $this->bien = Bien::factory()->create([
        'statut' => 'publie', 'slug' => 'villa-essai',
        'type' => 'villa', 'zone' => 'cocody', 'prix_unite' => 'total',
    ]);
});

it('montre un apercu des photos choisies avant enregistrement', function () {
    $rendu = Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('onglet', 'photos')
        ->set('nouvellesPhotos', [UploadedFile::fake()->image('salon.jpg', 1200, 800)])
        ->html();

    // Une vignette du fichier MONTE, dans le bloc des photos en attente : la
    // preuve qu'il est montre et non seulement retenu. On n'assert pas sur la
    // forme de l'adresse temporaire, qui depend du disque configure.
    preg_match('/pas encore enregistrée.*?<\/div>\s*<\/div>/s', $rendu, $bloc);

    expect($bloc)->not->toBeEmpty()
        ->and($bloc[0])->toContain('<img');
});

it('dit que ces photos ne sont pas encore enregistrees', function () {
    $rendu = Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('onglet', 'photos')
        ->set('nouvellesPhotos', [UploadedFile::fake()->image('salon.jpg', 1200, 800)])
        ->html();

    expect($rendu)->toContain('Enregistrer');
    expect(str_contains($rendu, 'ajoutée') || str_contains($rendu, 'ajoutées'))->toBeTrue();
});

/*
 * Le refus doit ramener l'editeur la ou il est. Sans cela, il reste sur
 * l'onglet « Photos » devant un bouton qui ne fait rien.
 */
it('ouvre l onglet qui porte l erreur', function () {
    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('onglet', 'photos')
        ->set('type', '')
        ->call('enregistrer')
        ->assertHasErrors(['type'])
        ->assertSet('onglet', 'general');
});

it('laisse l onglet en place quand l erreur y figure deja', function () {
    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('onglet', 'caracteristiques')
        ->set('prixUnite', '')
        ->call('enregistrer')
        ->assertHasErrors(['prixUnite'])
        ->assertSet('onglet', 'caracteristiques');
});
