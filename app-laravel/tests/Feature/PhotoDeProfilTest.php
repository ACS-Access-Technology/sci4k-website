<?php

use App\Livewire\Admin\UtilisateurListe;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * La photo de profil d'un compte du backoffice.
 *
 * Elle remplace les initiales partout ou une vignette s'affiche. Le repli vit
 * dans un seul composant Blade : les initiales etaient recopiees dans quatre
 * gabarits, et l'ecran des utilisateurs les recalculait meme d'une facon
 * differente de User::initials().
 */
beforeEach(function () {
    Storage::fake('public');
    Role::findOrCreate('administrateur');

    $this->compte = User::factory()->create(['name' => 'Awa Koné']);
    $this->compte->assignRole('administrateur');
});

it('retombe sur les initiales quand le compte n a pas de photo', function () {
    expect($this->compte->urlPhoto())->toBeNull()
        ->and($this->compte->initials())->toBe('AK');
});

it('enregistre la photo choisie', function () {
    Livewire::actingAs($this->compte)
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->set('photo', UploadedFile::fake()->image('awa.jpg'))
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $chemin = $this->compte->fresh()->photo;

    expect($chemin)->toStartWith('storage/comptes/')
        ->and(Storage::disk('public')->exists(mb_substr($chemin, mb_strlen('storage/'))))->toBeTrue();
});

it('refuse un fichier qui n est pas une image', function () {
    Livewire::actingAs($this->compte)
        ->test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'))
        ->assertHasErrors('photo');
});

/** 2 Mo au plus : une vignette s'affiche en 32 pixels de cote. */
it('refuse une image trop lourde', function () {
    Livewire::actingAs($this->compte)
        ->test('pages::settings.profile')
        ->set('photo', UploadedFile::fake()->image('enorme.jpg')->size(3000))
        ->assertHasErrors('photo');
});

/** Remplacer la photo efface l'ancienne : sans quoi le disque enfle a chaque essai. */
it('efface l ancienne photo en la remplacant', function () {
    Livewire::actingAs($this->compte)
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->set('photo', UploadedFile::fake()->image('premiere.jpg'))
        ->call('updateProfileInformation');

    $premiere = $this->compte->fresh()->photo;

    Livewire::actingAs($this->compte->fresh())
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->set('photo', UploadedFile::fake()->image('seconde.jpg'))
        ->call('updateProfileInformation');

    expect(Storage::disk('public')->exists(mb_substr($premiere, mb_strlen('storage/'))))->toBeFalse()
        ->and($this->compte->fresh()->photo)->not->toBe($premiere);
});

it('retire la photo sur demande', function () {
    Livewire::actingAs($this->compte)
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->set('photo', UploadedFile::fake()->image('awa.jpg'))
        ->call('updateProfileInformation');

    expect($this->compte->fresh()->photo)->not->toBeNull();

    Livewire::actingAs($this->compte->fresh())
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->call('retirerLaPhoto')
        ->call('updateProfileInformation');

    expect($this->compte->fresh()->photo)->toBeNull();
});

/**
 * Le chemin est verifie avant tout effacement : un chemin forge designant une
 * couverture d'article ne doit pas pouvoir la detruire au passage.
 */
it('n efface pas un fichier hors du dossier des photos', function () {
    Storage::disk('public')->put('actualites/couverture.jpg', 'contenu');
    $this->compte->update(['photo' => 'storage/actualites/couverture.jpg']);

    Livewire::actingAs($this->compte->fresh())
        ->test('pages::settings.profile')
        ->set('name', 'Awa Koné')
        ->set('email', $this->compte->email)
        ->set('photo', UploadedFile::fake()->image('awa.jpg'))
        ->call('updateProfileInformation');

    expect(Storage::disk('public')->exists('actualites/couverture.jpg'))->toBeTrue();
});

it('montre la photo dans la liste des utilisateurs', function () {
    $this->compte->update(['photo' => 'storage/comptes/awa.jpg']);

    Livewire::actingAs($this->compte->fresh())
        ->test(UtilisateurListe::class)
        ->assertSee(asset('storage/comptes/awa.jpg'), false);
});
