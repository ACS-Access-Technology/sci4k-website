<?php

/*
 * Les photos d'un bien arrivent en base, et de la sur le site.
 *
 * Signale par le client : « quand j'ajoute les photos d'un bien on ne les voit
 * pas, ni en back ni en front ». La table photos_de_bien est restee a zero
 * ligne : ce n'est pas un defaut d'affichage, rien n'etait enregistre.
 *
 * Aucun test ne couvrait ce chemin — ni le televersement, ni le rendu. C'est
 * par la que le defaut est passe.
 */

use App\Livewire\Admin\BienFormulaire;
use App\Models\Bien;
use App\Models\PhotoDeBien;
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

    // Le formulaire valide le type et la zone contre les referentiels : sans
    // eux, l'enregistrement echoue avant d'arriver aux photos.
    Referentiel::create(['famille' => 'types_de_bien', 'valeur' => 'villa', 'libelle_fr' => 'Villa', 'libelle_en' => 'Villa', 'ordre' => 1, 'visible' => true]);
    Referentiel::create(['famille' => 'zones', 'valeur' => 'cocody', 'libelle_fr' => 'Cocody', 'libelle_en' => 'Cocody', 'ordre' => 1, 'visible' => true]);

    $this->bien = Bien::factory()->create([
        'statut' => 'publie', 'slug' => 'villa-essai',
        'type' => 'villa', 'zone' => 'cocody', 'prix_unite' => 'total',
    ]);
});

it('enregistre les photos choisies', function () {
    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('nouvellesPhotos', [
            UploadedFile::fake()->image('salon.jpg', 1200, 800),
            UploadedFile::fake()->image('cuisine.jpg', 1200, 800),
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(PhotoDeBien::where('bien_id', $this->bien->id)->count())->toBe(2);
});

/** Le fichier est reellement depose, et son chemin mene quelque part. */
it('depose le fichier sur le disque public', function () {
    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('nouvellesPhotos', [UploadedFile::fake()->image('salon.jpg', 1200, 800)])
        ->call('enregistrer')
        ->assertHasNoErrors();

    $photo = PhotoDeBien::where('bien_id', $this->bien->id)->first();

    expect($photo)->not->toBeNull()
        ->and($photo->fichier)->toStartWith('storage/biens/');

    Storage::disk('public')->assertExists(substr($photo->fichier, strlen('storage/')));
});

/** L'ecran d'administration les montre apres enregistrement. */
it('affiche les photos dans le formulaire', function () {
    PhotoDeBien::create([
        'bien_id' => $this->bien->id,
        'fichier' => 'storage/biens/deja-la.jpg',
        'ordre' => 1,
    ]);

    Livewire::actingAs($this->admin)
        ->test(BienFormulaire::class, ['bien' => $this->bien])
        ->set('onglet', 'photos')
        ->assertSeeHtml('storage/biens/deja-la.jpg');
});

/** Et le site public aussi, sur la fiche du bien. */
it('sert les photos sur la fiche publique', function () {
    PhotoDeBien::create([
        'bien_id' => $this->bien->id,
        'fichier' => 'storage/biens/deja-la.jpg',
        'ordre' => 1,
    ]);

    $this->get('/biens/villa-essai')
        ->assertOk()
        ->assertSee('storage/biens/deja-la.jpg', false);
});
