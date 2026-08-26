<?php

use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\EtapeProcessusEnsemble;
use App\Livewire\Admin\ValeurEnsemble;
use App\Models\ChiffreCle;
use App\Models\EtapeProcessus;
use App\Models\User;
use App\Models\Valeur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ecrans des petits ensembles edites d'un bloc.
 *
 * Le cadrage les distingue des collections : tous les elements cote a cote, un
 * seul bouton, ni creation ni suppression. Un tableau pagine avec recherche et
 * filtres pour quatre lignes couterait trois clics pour changer un mot.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');

    $this->premiere = Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Rigueur', 'titre_en' => 'Rigour']);
    $this->seconde = Valeur::factory()->create(['ordre' => 2, 'titre_fr' => 'Écoute', 'titre_en' => 'Listening']);
});

it('charge toutes les lignes a l ouverture', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->assertSet("lignes.{$this->premiere->id}.titre_fr", 'Rigueur')
        ->assertSet("lignes.{$this->seconde->id}.titre_fr", 'Écoute');
});

it('enregistre toutes les lignes d un seul bouton', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Rigueur et sécurité')
        ->set("lignes.{$this->seconde->id}.titre_fr", 'Écoute active')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur et sécurité');
    expect($this->seconde->fresh()->titre_fr)->toBe('Écoute active');
});

it('n ecrit pas une ligne laissee intacte', function () {
    $avant = $this->seconde->updated_at;

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Modifiée')
        ->call('enregistrer');

    expect($this->seconde->fresh()->titre_fr)->toBe('Écoute');
    expect($this->seconde->fresh()->updated_at->eq($avant))->toBeTrue();
});

it('refuse un champ vide et nomme la ligne en cause', function () {
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", '')
        ->set("lignes.{$this->premiere->id}.titre_en", '')
        ->call('enregistrer')
        ->assertHasErrors(["lignes.{$this->premiere->id}.titre_fr"]);

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur');
});

it('ignore un identifiant qui ne designe rien', function () {
    // Les identifiants viennent du navigateur : l'un d'eux pourrait designer
    // une ligne d'une autre table, ou une ligne disparue depuis l'ouverture.
    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set('lignes.999999', ['titre_fr' => 'Injectée', 'titre_en' => 'Injected', 'texte_fr' => 'x', 'texte_en' => 'y'])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Valeur::count())->toBe(2);
    expect(Valeur::where('titre_fr', 'Injectée')->exists())->toBeFalse();
});

it('interdit l enregistrement a un lecteur', function () {
    Livewire::actingAs($this->lecteur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$this->premiere->id}.titre_fr", 'Renommée par un lecteur')
        ->call('enregistrer')
        ->assertForbidden();

    expect($this->premiere->fresh()->titre_fr)->toBe('Rigueur');
});

it('laisse un lecteur consulter l ecran', function () {
    $this->actingAs($this->lecteur)->get(route('admin.valeurs'))->assertOk();
});

it('valide le nombre d un chiffre cle', function () {
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 120]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.valeur", 'beaucoup')
        ->call('enregistrer')
        ->assertHasErrors(["lignes.{$chiffre->id}.valeur"]);

    expect($chiffre->fresh()->valeur)->toBe(120);
});

it('accepte un nombre valide', function () {
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 120]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.valeur", '150')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($chiffre->fresh()->valeur)->toBe(150);
});

it('sert les trois ecrans d ensembles', function () {
    EtapeProcessus::factory()->create(['ordre' => 1]);

    foreach (['admin.valeurs', 'admin.chiffres-cles', 'admin.etapes-processus'] as $route) {
        $this->actingAs($this->editeur)->get(route($route))->assertOk();
    }
});

it('presente les elements dans leur ordre d affichage', function () {
    $this->premiere->update(['ordre' => 2]);
    $this->seconde->update(['ordre' => 1]);

    $corps = Livewire::actingAs($this->editeur)->test(ValeurEnsemble::class)->html();

    expect(strpos($corps, 'Écoute'))->toBeLessThan(strpos($corps, 'Rigueur'));
});

it('couvre aussi les etapes du processus', function () {
    $etape = EtapeProcessus::factory()->create(['ordre' => 1, 'titre_fr' => 'Écoute & Analyse']);

    Livewire::actingAs($this->editeur)
        ->test(EtapeProcessusEnsemble::class)
        ->set("lignes.{$etape->id}.titre_fr", 'Écoute renforcée')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($etape->fresh()->titre_fr)->toBe('Écoute renforcée');
});
