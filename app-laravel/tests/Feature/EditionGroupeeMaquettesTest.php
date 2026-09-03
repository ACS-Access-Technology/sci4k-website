<?php

use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\EtapeProcessusEnsemble;
use App\Livewire\Admin\ValeurEnsemble;
use App\Models\ChiffreCle;
use App\Models\EtapeProcessus;
use App\Models\ReglageDeSection;
use App\Models\User;
use App\Models\Valeur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ce que les maquettes du backoffice ajoutent aux trois ensembles.
 *
 * Le premier jet ne faisait que modifier ce qui existait. Les maquettes
 * montrent l'ajout, le retrait, une visibilite par element, un suffixe pour
 * les chiffres, une note interne, et un panneau de reglages qui edite
 * l'en-tete de section.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');
});

/* ----------------------------------------------------- ajout et retrait */

it('ajoute une valeur et l enregistre', function () {
    Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Rigueur', 'titre_en' => 'Rigour']);

    $composant = Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->call('ajouter');

    $composant->set('lignes.neuf-1.titre_fr', 'Proximité')
        ->set('lignes.neuf-1.titre_en', 'Proximity')
        ->set('lignes.neuf-1.texte_fr', 'Sur le terrain.')
        ->set('lignes.neuf-1.texte_en', 'On the ground.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Valeur::count())->toBe(2);
    expect(Valeur::where('titre_fr', 'Proximité')->exists())->toBeTrue();
});

it('ne cree pas deux fois la meme ligne en enregistrant deux fois', function () {
    // La cle « neuf-N » doit ceder la place a l'identifiant reel apres le
    // premier enregistrement, sans quoi le second recreerait la ligne.
    $composant = Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->call('ajouter')
        ->set('lignes.neuf-1.titre_fr', 'Unique')
        ->set('lignes.neuf-1.titre_en', 'Unique')
        ->set('lignes.neuf-1.texte_fr', 'a')
        ->set('lignes.neuf-1.texte_en', 'b')
        ->call('enregistrer');

    $composant->call('enregistrer');

    expect(Valeur::where('titre_fr', 'Unique')->count())->toBe(1);
});

it('retire une valeur a l enregistrement', function () {
    $gardee = Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Gardée']);
    $retiree = Valeur::factory()->create(['ordre' => 2, 'titre_fr' => 'Retirée']);

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->call('retirer', $retiree->id)
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Valeur::find($retiree->id))->toBeNull();
    expect(Valeur::find($gardee->id))->not->toBeNull();
});

it('n efface rien tant qu on n a pas enregistre', function () {
    // Le retrait est reversible jusqu'a l'enregistrement : l'editeur peut
    // encore quitter l'ecran sans rien perdre.
    $valeur = Valeur::factory()->create(['ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->call('retirer', $valeur->id);

    expect(Valeur::find($valeur->id))->not->toBeNull();
});

it('renumerote les rangs selon l ordre affiche', function () {
    Valeur::factory()->create(['ordre' => 5, 'titre_fr' => 'A']);
    Valeur::factory()->create(['ordre' => 9, 'titre_fr' => 'B']);

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->call('enregistrer');

    expect(Valeur::orderBy('ordre')->pluck('ordre')->all())->toBe([1, 2]);
});

it('interdit l ajout a un lecteur', function () {
    Livewire::actingAs($this->lecteur)
        ->test(ValeurEnsemble::class)
        ->call('ajouter')
        ->assertForbidden();
});

/* -------------------------------------------------------- visibilite */

it('masque une valeur sans la supprimer', function () {
    $valeur = Valeur::factory()->create(['ordre' => 1, 'visible' => true]);

    Livewire::actingAs($this->editeur)
        ->test(ValeurEnsemble::class)
        ->set("lignes.{$valeur->id}.visible", '')
        ->call('enregistrer');

    expect($valeur->fresh()->visible)->toBeFalse();
    expect(Valeur::count())->toBe(1);
});

/* ------------------------------------------------------ chiffres cles */

it('enregistre le suffixe et la note interne d un chiffre', function () {
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 98]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.suffixe", '%')
        ->set("lignes.{$chiffre->id}.note_interne", 'Basé sur les enquêtes de satisfaction')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $apres = $chiffre->fresh();

    expect($apres->suffixe)->toBe('%');
    expect($apres->note_interne)->toBe('Basé sur les enquêtes de satisfaction');
    expect($apres->affichage())->toBe('98%');
});

it('montre l apercu avec les valeurs en cours de saisie', function () {
    // L'apercu doit suivre la saisie et non la base : c'est le seul endroit ou
    // l'on voit qu'un suffixe manque avant d'enregistrer.
    $chiffre = ChiffreCle::factory()->create(['ordre' => 1, 'valeur' => 98, 'suffixe' => '']);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set("lignes.{$chiffre->id}.suffixe", '%')
        ->assertSee('98%');
});

it('enregistre les options d animation sur l en-tete de section', function () {
    ChiffreCle::factory()->create(['ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(ChiffreCleEnsemble::class)
        ->set('reglages.animer', '')
        ->set('reglages.duree_animation', '1200')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', 'home.hero')->first();

    expect($section)->not->toBeNull();
    expect($section->option('animer'))->toBeFalse();
    expect($section->option('duree_animation'))->toBe('1200');
});

/* ---------------------------------------------------------- processus */

/**
 * L'en-tete n'est plus ecrite par cet editeur : « Pages du site → Services »
 * l'edite sous le meme slug, et deux formulaires pour une meme donnee
 * s'ecraseraient. La mise en page, elle, n'est editee nulle part ailleurs
 * depuis cet ecran — elle reste donc ecrite ici.
 */
it('edite la mise en page depuis l ecran du processus', function () {
    EtapeProcessus::factory()->create(['ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(EtapeProcessusEnsemble::class)
        ->set('reglages.mise_en_page', 'liste')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'services.process')->first()->option('mise_en_page'))
        ->toBe('liste');
});

it('ne perd pas les options qu on ne touche pas', function () {
    $section = ReglageDeSection::factory()->create(['slug' => 'services.process']);
    $section->poserOptions(['reglage_ancien' => 'garde']);
    $section->save();

    EtapeProcessus::factory()->create(['ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(EtapeProcessusEnsemble::class)
        ->set('reglages.mise_en_page', 'liste')
        ->call('enregistrer');

    expect($section->fresh()->option('reglage_ancien'))->toBe('garde');
});
