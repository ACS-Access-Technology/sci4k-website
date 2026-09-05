<?php

use App\Livewire\Admin\PageBiens;
use App\Models\Bien;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les textes du catalogue et de la fiche de bien.
 *
 * Cinquante et une chaines etaient ecrites en dur dans ces deux vues et
 * traduites par __() : aucun ecran ne les exposait. Le vocabulaire des filtres
 * — types, zones, tranches — etait bien modifiable depuis les referentiels,
 * mais pas les INTITULES au-dessus de chaque liste, ni les mots de la grille,
 * ni ceux du formulaire de rendez-vous.
 *
 * Ces tests mesurent au bout de la chaine : ce que le VISITEUR lit apres que
 * l'editeur a saisi, et non ce que la table des sections contient.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->bien = Bien::factory()->create([
        'slug' => 'villa-cocody',
        'statut' => Bien::PUBLIE,
        'zone' => 'cocody',
        'nombre_pieces' => 5,
    ]);
});

/** Saisit un texte depuis l'ecran, comme le ferait l'editeur. */
function saisir(User $admin, string $module, string $cle, string $valeur): void
{
    Livewire::actingAs($admin)
        ->test(PageBiens::class)
        ->call('ouvrir', $module)
        ->set('textes.'.$cle.'_fr', $valeur)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

it('rend le catalogue inchange tant que rien n est saisi', function () {
    // Une base vierge doit rendre EXACTEMENT ce que la page rendait avant : le
    // texte d'origine reste le repli de chaque champ.
    $corps = $this->get(route('biens.index'))->assertOk()->getContent();

    expect($corps)->toContain('Type de bien')
        ->and($corps)->toContain('Rechercher le bien idéal');
});

it('applique les libelles des filtres saisis en backoffice', function () {
    saisir($this->admin, 'filtres', 'libelle_type', 'Nature du bien');
    saisir($this->admin, 'filtres', 'libelle_bouton', 'Lancer la recherche');

    $corps = $this->get(route('biens.index'))->assertOk()->getContent();

    expect($corps)->toContain('Nature du bien')
        ->and($corps)->toContain('Lancer la recherche')
        ->and($corps)->not->toContain('Type de bien');
});

it('applique les textes de la grille', function () {
    saisir($this->admin, 'catalogue', 'libelle_fiche', 'Découvrir ce bien');

    expect($this->get(route('biens.index'))->assertOk()->getContent())
        ->toContain('Découvrir ce bien');
});

/*
 * Le point qui justifie tout le decoupage : les caracteristiques et le
 * formulaire de rendez-vous s'affichent DEUX FOIS — dans la fenetre du
 * catalogue et sur la fiche complete. Une seule saisie doit valoir pour les
 * deux, sans quoi l'editeur corrigerait l'une en croyant avoir corrige les
 * deux.
 */
it('applique une meme saisie a la fenetre du catalogue et a la fiche', function () {
    saisir($this->admin, 'fiche', 'libelle_pieces', 'Nombre de pièces habitables');
    saisir($this->admin, 'visite', 'libelle_bouton', 'Je veux visiter');

    // Ces textes n'apparaissent dans le catalogue que la FENETRE OUVERTE : la
    // grille ne montre ni caracteristiques ni formulaire. On ouvre donc un
    // bien, comme le visiteur qui clique sur une carte.
    Livewire::test(App\Livewire\Public\CatalogueDesBiens::class)
        ->call('ouvrirBien', $this->bien->id)
        ->assertSee('Nombre de pièces habitables')
        ->assertSee('Je veux visiter');

    $fiche = $this->get(route('biens.detail', $this->bien->slug))->assertOk()->getContent();

    expect($fiche)->toContain('Nombre de pièces habitables')
        ->and($fiche)->toContain('Je veux visiter');
});

it('applique les libelles propres a la fiche', function () {
    // « Dans la même zone » ne s'affiche qu'avec des voisins a montrer.
    Bien::factory()->create(['slug' => 'duplex-cocody', 'statut' => Bien::PUBLIE, 'zone' => 'cocody']);

    saisir($this->admin, 'fiche', 'lien_retour', 'Revenir à la liste');
    saisir($this->admin, 'fiche', 'titre_meme_zone', 'À proximité');

    $corps = $this->get(route('biens.detail', $this->bien->slug))->assertOk()->getContent();

    expect($corps)->toContain('Revenir à la liste')
        ->and($corps)->toContain('À proximité');
});

it('garde chaque langue de son cote', function () {
    Livewire::actingAs($this->admin)
        ->test(PageBiens::class)
        ->call('ouvrir', 'filtres')
        ->set('textes.libelle_type_fr', 'Nature du bien')
        ->set('textes.libelle_type_en', 'Property kind')
        ->call('enregistrer');

    $section = ReglageDeSection::where('slug', 'biens.filters')->first();

    expect($section->texteBilingue('libelle_type', 'fr'))->toBe('Nature du bien')
        ->and($section->texteBilingue('libelle_type', 'en'))->toBe('Property kind');
});

it("n'ecrit que les cles que le module declare", function () {
    // `$textes` est une propriete publique : le navigateur en fixe le contenu,
    // CLES COMPRISES. Sans le filtre du trait, n'importe quelle option de la
    // section serait ecrivable sans passer par aucune regle.
    Livewire::actingAs($this->admin)
        ->test(PageBiens::class)
        ->call('ouvrir', 'filtres')
        ->set('textes.mise_en_page_fr', 'valeur injectée')
        ->call('enregistrer');

    $section = ReglageDeSection::where('slug', 'biens.filters')->first();

    expect($section?->option('mise_en_page_fr'))->toBeNull();
});
