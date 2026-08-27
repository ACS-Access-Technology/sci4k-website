<?php

use App\Livewire\Admin\EncartListe;
use App\Livewire\Admin\ImageDeFondFormulaire;
use App\Livewire\Admin\ImageDeFondListe;
use App\Livewire\Admin\PartenaireFormulaire;
use App\Livewire\Admin\ReglageDeSectionFormulaire;
use App\Livewire\Admin\TemoignageFormulaire;
use App\Livewire\Admin\TemoignageListe;
use App\Models\Encart;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Temoignage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ecrans des six collections de blocs.
 *
 * Ils partagent FormulaireDeBloc et ListeOrdonnable : ces tests eprouvent le
 * contrat commun sur une collection ouverte (temoignages), puis les trois
 * ecarts voulus — collections a slug fige, fichier televerse, en-tete de
 * section sans visibilite.
 */
beforeEach(function () {
    Storage::fake('public');

    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');
});

/* ------------------------------------------------------- contrat commun */

it('sert les six ecrans de liste', function () {
    Temoignage::factory()->create();
    Partenaire::factory()->create();
    MembreEquipe::factory()->create();
    Encart::factory()->create();
    ImageDeFond::factory()->create();
    ReglageDeSection::factory()->create();

    foreach ([
        'admin.temoignages.liste', 'admin.partenaires.liste', 'admin.equipe.liste',
        'admin.encarts.liste', 'admin.images-de-fond.liste', 'admin.reglages-de-section.liste',
    ] as $route) {
        $this->actingAs($this->editeur)->get(route($route))->assertOk();
    }
});

it('cree un temoignage avec ses deux langues', function () {
    Livewire::actingAs($this->editeur)
        ->test(TemoignageFormulaire::class)
        ->set('valeurs.auteur', 'Awa Koné')
        ->set('valeurs.initiales', 'AK')
        ->set('valeurs.note', '5')
        ->set('valeurs.citation_fr', 'Un accompagnement sérieux.')
        ->set('valeurs.citation_en', 'Serious support.')
        ->set('valeurs.role_fr', 'Propriétaire, Cocody')
        ->set('valeurs.role_en', 'Owner, Cocody')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $temoignage = Temoignage::where('auteur', 'Awa Koné')->first();

    expect($temoignage)->not->toBeNull();
    expect($temoignage->citation('en'))->toBe('Serious support.');
});

it('place le bloc cree en fin de liste', function () {
    Temoignage::factory()->create(['ordre' => 1, 'auteur' => 'Premier']);
    Temoignage::factory()->create(['ordre' => 2, 'auteur' => 'Second']);

    Livewire::actingAs($this->editeur)
        ->test(TemoignageFormulaire::class)
        ->set('valeurs.auteur', 'Troisième')
        ->set('valeurs.note', '5')
        ->set('valeurs.citation_fr', 'a')->set('valeurs.citation_en', 'b')
        ->call('enregistrer');

    expect(Temoignage::ordonnees()->pluck('auteur')->all())
        ->toBe(['Premier', 'Second', 'Troisième']);
});

it('refuse une note hors de l echelle', function () {
    Livewire::actingAs($this->editeur)
        ->test(TemoignageFormulaire::class)
        ->set('valeurs.auteur', 'Awa')
        ->set('valeurs.note', '9')
        ->set('valeurs.citation_fr', 'a')->set('valeurs.citation_en', 'b')
        ->call('enregistrer')
        ->assertHasErrors(['valeurs.note']);
});

it('supprime un temoignage', function () {
    $temoignage = Temoignage::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(TemoignageListe::class)
        ->call('supprimer', $temoignage->id);

    expect(Temoignage::find($temoignage->id))->toBeNull();
});

it('refuse l enregistrement a un lecteur', function () {
    $temoignage = Temoignage::factory()->create(['auteur' => 'Intact']);

    Livewire::actingAs($this->lecteur)
        ->test(TemoignageFormulaire::class, ['element' => $temoignage])
        ->set('valeurs.auteur', 'Modifié par un lecteur')
        ->call('enregistrer')
        ->assertForbidden();

    expect($temoignage->fresh()->auteur)->toBe('Intact');
});

/* ------------------------------------------ collections a slug fige (3) */

it('refuse de creer un encart, une image de fond ou un en-tete', function () {
    // Leur slug designe un emplacement du site : un element cree ne
    // s'afficherait nulle part. Le refus est dans le COMPOSANT, et pas
    // seulement dans l'absence de route — Livewire monte le composant sur
    // simple demande du navigateur.
    foreach ([
        \App\Livewire\Admin\EncartFormulaire::class,
        ImageDeFondFormulaire::class,
        ReglageDeSectionFormulaire::class,
    ] as $composant) {
        Livewire::actingAs($this->editeur)->test($composant)->assertNotFound();
    }
});

it('laisse creer un temoignage, un partenaire et un membre', function () {
    // Le controle inverse : le refus ne doit pas s'etre etendu aux trois
    // collections qui, elles, acceptent la creation.
    foreach ([
        TemoignageFormulaire::class,
        PartenaireFormulaire::class,
        \App\Livewire\Admin\MembreEquipeFormulaire::class,
    ] as $composant) {
        Livewire::actingAs($this->editeur)->test($composant)->assertOk();
    }
});

it('refuse de supprimer un encart ou une image de fond', function () {
    $encart = Encart::factory()->create();
    $image = ImageDeFond::factory()->create();

    Livewire::actingAs($this->editeur)->test(EncartListe::class)
        ->call('supprimer', $encart->id)->assertForbidden();

    Livewire::actingAs($this->editeur)->test(ImageDeFondListe::class)
        ->call('supprimer', $image->id)->assertForbidden();

    expect(Encart::find($encart->id))->not->toBeNull();
    expect(ImageDeFond::find($image->id))->not->toBeNull();
});

it('ne laisse pas modifier le slug d un encart', function () {
    $encart = Encart::factory()->create(['slug' => 'accueil-banderole']);

    Livewire::actingAs($this->editeur)
        ->test(\App\Livewire\Admin\EncartFormulaire::class, ['element' => $encart])
        ->set('valeurs.slug', 'autre-emplacement')
        ->set('valeurs.titre_fr', 'Titre')->set('valeurs.titre_en', 'Title')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($encart->fresh()->slug)->toBe('accueil-banderole');
});

/* --------------------------------------------------- fichier televerse */

it('televerse le logo d un partenaire', function () {
    $partenaire = Partenaire::factory()->create(['logo' => null]);

    Livewire::actingAs($this->editeur)
        ->test(PartenaireFormulaire::class, ['element' => $partenaire])
        ->set('fichier', UploadedFile::fake()->image('logo.png'))
        ->call('enregistrer')
        ->assertHasNoErrors();

    $logo = $partenaire->fresh()->logo;

    expect($logo)->toStartWith('storage/partenaires/');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $logo));
});

it('efface l ancien logo en le remplaçant', function () {
    $partenaire = Partenaire::factory()->create(['logo' => null]);

    Livewire::actingAs($this->editeur)
        ->test(PartenaireFormulaire::class, ['element' => $partenaire])
        ->set('fichier', UploadedFile::fake()->image('premier.png'))
        ->call('enregistrer');

    $premier = $partenaire->fresh()->logo;

    Livewire::actingAs($this->editeur)
        ->test(PartenaireFormulaire::class, ['element' => $partenaire->fresh()])
        ->set('fichier', UploadedFile::fake()->image('second.png'))
        ->call('enregistrer');

    Storage::disk('public')->assertMissing(str_replace('storage/', '', $premier));
});

it('ne remonte pas hors du dossier en effaçant un fichier', function () {
    // Meme garde qu'au lot 2a : « storage/partenaires/../fonds/x.jpg »
    // commence bien par le prefixe attendu et se resout pourtant ailleurs.
    Storage::disk('public')->put('fonds/banniere.jpg', 'fond du site');

    $partenaire = Partenaire::factory()->create(['logo' => 'storage/partenaires/../fonds/banniere.jpg']);

    Livewire::actingAs($this->editeur)
        ->test(PartenaireFormulaire::class, ['element' => $partenaire])
        ->call('retirerFichier')
        ->call('enregistrer');

    Storage::disk('public')->assertExists('fonds/banniere.jpg');
});

it('ne touche jamais a un fichier du site statique', function () {
    Storage::disk('public')->put('images/partners/cnps.png', 'logo du site');

    $partenaire = Partenaire::factory()->create(['logo' => 'images/partners/cnps.png']);

    Livewire::actingAs($this->editeur)
        ->test(PartenaireFormulaire::class, ['element' => $partenaire])
        ->call('retirerFichier')
        ->call('enregistrer');

    Storage::disk('public')->assertExists('images/partners/cnps.png');
});

/* ------------------------------------------------- en-tete de section */

it('n offre pas de visibilite sur un en-tete de section', function () {
    // La table n'a pas de colonne `visible` : proposer la case aurait produit
    // une commande sans effet, et une ecriture sur une colonne absente.
    $reglage = ReglageDeSection::factory()->create(['slug' => 'home.hero']);

    $composant = Livewire::actingAs($this->editeur)
        ->test(ReglageDeSectionFormulaire::class, ['element' => $reglage]);

    $composant->assertDontSee('Visible sur le site');

    $composant->set('valeurs.titre_fr', 'Nouveau titre')
        ->set('valeurs.titre_en', 'New title')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($reglage->fresh()->titre_fr)->toBe('Nouveau titre');
});

it('ne laisse pas modifier la section d un en-tete', function () {
    $reglage = ReglageDeSection::factory()->create(['slug' => 'home.hero']);

    Livewire::actingAs($this->editeur)
        ->test(ReglageDeSectionFormulaire::class, ['element' => $reglage])
        ->set('valeurs.slug', 'about.page')
        ->set('valeurs.titre_fr', 'Titre')->set('valeurs.titre_en', 'Title')
        ->call('enregistrer');

    expect($reglage->fresh()->slug)->toBe('home.hero');
});
