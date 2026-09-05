<?php

use App\Livewire\Admin\RechercheGlobale;
use App\Livewire\Admin\TableauDeBord;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\MembreEquipe;
use App\Models\Service;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Taches prioritaires et recherche transverse.
 *
 * Les deux viennent de backoffice/dashboard.html. Les taches sont SAISIES, la
 * ou le panneau « A traiter » est deduit de l'etat du site : « 3 articles en
 * brouillon » se perime tout seul, « rappeler le notaire jeudi » ne se devine
 * pas.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);
});

/* -------------------------------------------------------------- taches */

it('ajoute une tache avec son echeance', function () {
    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->set('nouvelleTache', 'Rappeler le notaire')
        ->set('nouvelleEcheance', now()->addDays(3)->toDateString())
        ->call('ajouterTache')
        ->assertHasNoErrors();

    $tache = Tache::where('texte', 'Rappeler le notaire')->first();

    expect($tache)->not->toBeNull();
    expect($tache->user_id)->toBe($this->editeur->id);
});

it('vide le champ apres l ajout', function () {
    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->set('nouvelleTache', 'Une tâche')
        ->call('ajouterTache')
        ->assertSet('nouvelleTache', '');
});

it('refuse une tache sans texte', function () {
    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->set('nouvelleTache', '')
        ->call('ajouterTache')
        ->assertHasErrors(['nouvelleTache']);

    expect(Tache::count())->toBe(0);
});

it('bascule une tache entre faite et a faire', function () {
    $tache = Tache::factory()->create(['user_id' => $this->editeur->id, 'terminee' => false]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->call('basculerTache', $tache->id);

    expect($tache->fresh()->terminee)->toBeTrue();
});

it('ne laisse pas toucher a la tache d un autre', function () {
    // L'identifiant vient du navigateur : rien n'empeche d'y mettre celui de
    // la tache d'un collegue.
    $autre = User::factory()->create();
    $tache = Tache::factory()->create(['user_id' => $autre->id, 'texte' => 'Privée']);

    // La requete est restreinte a l'utilisateur : la tache d'un autre n'existe
    // tout simplement pas de son point de vue, d'ou l'echec de recherche.
    expect(fn () => Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->call('basculerTache', $tache->id)
    )->toThrow(ModelNotFoundException::class);

    expect($tache->fresh()->terminee)->toBeFalse();
});

it('ne montre que ses propres taches', function () {
    $autre = User::factory()->create();

    Tache::factory()->create(['user_id' => $this->editeur->id, 'texte' => 'Ma tâche à moi']);
    Tache::factory()->create(['user_id' => $autre->id, 'texte' => 'La tâche du collègue']);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->assertSee('Ma tâche à moi')
        ->assertDontSee('La tâche du collègue');
});

it('supprime une tache', function () {
    $tache = Tache::factory()->create(['user_id' => $this->editeur->id]);

    Livewire::actingAs($this->editeur)
        ->test(TableauDeBord::class)
        ->call('supprimerTache', $tache->id);

    expect(Tache::find($tache->id))->toBeNull();
});

it('interdit l ajout de tache a un lecteur', function () {
    Livewire::actingAs($this->lecteur)
        ->test(TableauDeBord::class)
        ->set('nouvelleTache', 'Ajoutée par un lecteur')
        ->call('ajouterTache')
        ->assertForbidden();

    expect(Tache::count())->toBe(0);
});

it('signale une echeance depassee', function () {
    $tache = Tache::factory()->create([
        'user_id' => $this->editeur->id,
        'echeance' => now()->subDays(2)->toDateString(),
    ]);

    expect($tache->echeanceLisible()['ton'])->toBe('urgent');
    expect($tache->echeanceLisible()['texte'])->toBe('en retard');
});

it('nomme les echeances proches en clair', function () {
    // « 12/09 » demande un calcul, « demain » se lit.
    $demain = Tache::factory()->create([
        'user_id' => $this->editeur->id, 'echeance' => now()->addDay()->toDateString(),
    ]);

    expect($demain->echeanceLisible()['texte'])->toBe('demain');
});

/* ------------------------------------------------------------ recherche */

it('trouve un article par son titre', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'titre_fr' => 'Sécuriser un terrain à Bingerville',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'Bingerville')
        ->assertSee('Sécuriser un terrain à Bingerville');
});

it('cherche dans les deux langues', function () {
    // Un editeur anglophone tape le titre qu'il a saisi, pas sa traduction.
    Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'nom_fr' => 'Gestion locative', 'nom_en' => 'Rental management',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'Rental')
        ->assertSee('Gestion locative');
});

it('traverse les familles de contenu', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Terrain et titre']);
    MembreEquipe::factory()->create(['nom' => 'Marc Terrain']);

    Livewire::actingAs($this->editeur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'Terrain')
        ->assertSee('Terrain et titre')
        ->assertSee('Marc Terrain');
});

it('ne cherche pas sur un seul caractere', function () {
    // Un caractere ramenerait la moitie de la base ; deux suffisent a un sigle.
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Article visible']);

    Livewire::actingAs($this->editeur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'A')
        ->assertDontSee('Article visible');
});

it('annonce l absence de resultat', function () {
    Livewire::actingAs($this->editeur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'introuvable-xyz')
        ->assertSee('Aucun résultat');
});
