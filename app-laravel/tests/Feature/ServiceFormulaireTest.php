<?php

use App\Livewire\Admin\ServiceFormulaire;
use App\Models\Categorie;
use App\Models\Service;
use App\Models\User;
use App\Services\Traduction\Traducteur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'foncier',
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
    ]);
});

it('charge les valeurs existantes', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->assertSet('nomFr', 'Foncier')
        ->assertSet('nomEn', 'Land & Title');
});

it('enregistre les modifications', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('nomFr', 'Foncier et titres')
        ->set('accrocheFr', 'Sécuriser vos terrains')
        ->set('accrocheEn', 'Secure your land')
        ->set('descriptionFr', 'Texte long.')
        ->set('descriptionEn', 'Long text.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->service->fresh()->nom_fr)->toBe('Foncier et titres');
});

it('exige le nom dans les deux langues', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('nomFr', '')
        ->call('enregistrer')
        ->assertHasErrors(['nomFr' => 'required']);
});

it('ouvre l onglet de la langue de l interface', function () {
    app()->setLocale('en');

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->assertSet('langueActive', 'en');
});

it('remplit la langue vide par traduction, sans ecraser', function () {
    app()->bind(Traducteur::class, fn () => new class implements Traducteur
    {
        public function disponible(): bool
        {
            return true;
        }

        public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
        {
            return array_map(fn ($t) => '['.$vers.'] '.$t, $textes);
        }
    });

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('accrocheFr', 'Sécuriser vos terrains')
        ->set('accrocheEn', '')
        ->set('descriptionFr', 'Texte long.')
        ->set('descriptionEn', 'Texte anglais déjà écrit.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $frais = $this->service->fresh();

    expect($frais->accroche_en)->toBe('[en] Sécuriser vos terrains');
    expect($frais->description_en)->toBe('Texte anglais déjà écrit.');
});

it('interdit a un lecteur d ouvrir le formulaire', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)
        ->get(route('admin.services.edition', $this->service))
        ->assertForbidden();
});
