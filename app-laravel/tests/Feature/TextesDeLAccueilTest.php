<?php

use App\Livewire\Admin\Menus;
use App\Livewire\Admin\PageAccueil;
use App\Livewire\Admin\PageBiens;
use App\Livewire\Public\CatalogueDesBiens;
use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les textes de la page d'accueil.
 *
 * Sept chaines y etaient ecrites en dur et traduites par __() : aucun ecran ne
 * les exposait. Chacune vient desormais du module de SON bloc — le mot sous la
 * fleche de defilement au hero, le lien des cartes d'article au bloc articles,
 * la note d'un avis au bloc temoignages.
 *
 * Deux font exception : « Fermer » et « Annonce » reviennent sur trois pages,
 * et sont dits une seule fois avec l'habillage du site.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Saisit un texte depuis « Pages du site → Accueil ». */
function saisirAccueil(User $admin, string $module, string $cle, string $valeur): void
{
    Livewire::actingAs($admin)
        ->test(PageAccueil::class)
        ->call('ouvrir', $module)
        ->set('textes.'.$cle.'_fr', $valeur)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

it('rend l accueil inchange tant que rien n est saisi', function () {
    $this->get('/')->assertOk()
        ->assertSee('Défilez', false)
        ->assertSee('Faire défiler vers le contenu', false);
});

it('applique les textes du hero', function () {
    saisirAccueil($this->admin, 'hero', 'libelle_defilement', 'Vers le bas');
    saisirAccueil($this->admin, 'hero', 'aria_defilement', 'Aller au contenu');

    $this->get('/')->assertOk()
        ->assertSee('Vers le bas', false)
        ->assertSee('Aller au contenu', false)
        ->assertDontSee('>Défilez<', false);
});

it('applique le lien des cartes d article', function () {
    Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'publie',
        'date_publication' => now()->subDay(),
    ]);

    saisirAccueil($this->admin, 'articles', 'libelle_lien', 'Lire la suite');

    $this->get('/')->assertOk()->assertSee('Lire la suite', false);
});

it('applique la note d un avis, ou :note est remplace', function () {
    Temoignage::factory()->create(['visible' => true, 'note' => 4]);

    saisirAccueil($this->admin, 'temoignages', 'aria_note', 'Note de :note étoiles sur 5');

    // Le :note est remplace a l'affichage : un texte modifiable qui perdrait
    // son marqueur afficherait « Note de :note étoiles ».
    $this->get('/')->assertOk()->assertSee('Note de 4 étoiles sur 5', false);
});

it('applique la bulle d aide d un logo de partenaire', function () {
    Partenaire::factory()->create(['visible' => true, 'nom' => 'NSIA Banque', 'site' => 'https://exemple.test']);

    saisirAccueil($this->admin, 'partenaires', 'titre_lien', 'Visiter :nom');

    $this->get('/')->assertOk()->assertSee('Visiter NSIA Banque', false);
});

/*
 * « Fermer » et « Annonce » ne sont declares NULLE PART dans les ecrans de
 * page : ils reviennent sur l'accueil, sur les services et dans le catalogue.
 * Trois champs pour un meme mot auraient ete corriges un par un — ou pas.
 */
it('applique les mots communs a toutes les pages qui les emploient', function () {
    // La fenetre d'un service n'est rendue que s'il existe des services : sans
    // elle, « Fermer » n'apparait nulle part et le test mesurerait le vide.
    Service::factory()->create(['visible' => true]);

    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->set('textes.libelle_fermer_fr', 'Refermer')
        ->call('enregistrer')
        ->assertHasNoErrors();

    foreach (['/', route('services.index')] as $adresse) {
        $this->get($adresse)->assertOk()->assertSee('Refermer', false);
    }

    // Dans le catalogue, la fenetre n'existe qu'un bien ouvert : la demander
    // sur la page nue mesurerait le vide.
    Livewire::test(CatalogueDesBiens::class)
        ->call('ouvrirBien', Bien::factory()->create(['statut' => Bien::PUBLIE])->id)
        ->assertSee('Refermer');
});

it('ne declare pas « Fermer » deux fois', function () {
    // Le catalogue le portait avant que la page Services ne rappelle qu'il y
    // sert aussi. Un seul endroit, sans quoi les deux divergent.
    expect(array_keys(PageBiensTextesDuCatalogue()))->not->toContain('libelle_fermer')
        ->and(array_keys(Menus::TEXTES_DU_SITE))->toContain('libelle_fermer');
});

it('garde chaque langue de son cote', function () {
    Livewire::actingAs($this->admin)
        ->test(PageAccueil::class)
        ->call('ouvrir', 'hero')
        ->set('textes.libelle_defilement_fr', 'Vers le bas')
        ->set('textes.libelle_defilement_en', 'Scroll down')
        ->call('enregistrer');

    $section = ReglageDeSection::where('slug', 'home.hero')->first();

    expect($section->texteBilingue('libelle_defilement', 'fr'))->toBe('Vers le bas')
        ->and($section->texteBilingue('libelle_defilement', 'en'))->toBe('Scroll down');
});

it("n'ecrit que les cles que le module declare", function () {
    Livewire::actingAs($this->admin)
        ->test(PageAccueil::class)
        ->call('ouvrir', 'hero')
        ->set('textes.mise_en_page_fr', 'valeur injectée')
        ->call('enregistrer');

    expect(ReglageDeSection::where('slug', 'home.hero')->first()?->option('mise_en_page_fr'))
        ->toBeNull();
});

function PageBiensTextesDuCatalogue(): array
{
    return PageBiens::TEXTES_DU_CATALOGUE;
}
