<?php

use App\Livewire\Admin\PageAccueil;
use App\Livewire\Admin\PageContact;
use App\Livewire\Admin\PageFaq;
use App\Livewire\Admin\PagePresentation;
use App\Livewire\Admin\PageServices;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les derniers textes figes des pages, et le referencement de chacune.
 *
 * Contact gardait les INTITULES au-dessus de ses coordonnees — « Siège
 * Social », « Horaires d'ouverture » — alors que les VALEURS venaient deja de
 * la configuration. Presentation gardait la signature du directeur general :
 * une agence qui en change devait rouvrir le code.
 *
 * Et CHAQUE page annonçait aux moteurs un titre et une description ecrits en
 * dur en tete de sa vue. L'ecran « Configuration » proposait bien deux valeurs
 * par DEFAUT, mais aucune page n'etant sans titre, elles ne servaient jamais.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Saisit un texte sur le module d'un ecran de page. */
function saisirTexte(User $admin, string $ecran, string $module, string $cle, string $valeur): void
{
    Livewire::actingAs($admin)
        ->test($ecran)
        ->call('ouvrir', $module)
        ->set('textes.'.$cle.'_fr', $valeur)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

/* ------------------------------------------------ contact */

it('applique les intitules des coordonnees', function () {
    saisirTexte($this->admin, PageContact::class, 'coordonnees', 'titre_adresse', 'Nos bureaux');
    saisirTexte($this->admin, PageContact::class, 'coordonnees', 'titre_horaires', 'Quand nous venir voir');

    $this->get(route('contact.index'))->assertOk()
        ->assertSee('Nos bureaux', false)
        ->assertSee('Quand nous venir voir', false)
        ->assertDontSee('<h4>Siège Social</h4>', false);
});

it('applique les textes de la carte', function () {
    saisirTexte($this->admin, PageContact::class, 'carte', 'libelle_lien', 'Nous situer');

    $this->get(route('contact.index'))->assertOk()->assertSee('Nous situer', false);
});

/* ------------------------------------------------ presentation */

it('applique la signature du directeur', function () {
    saisirTexte($this->admin, PagePresentation::class, 'directeur', 'signature_nom', 'La Direction Générale');

    $this->get(route('presentation.index'))->assertOk()
        ->assertSee('La Direction Générale', false);
});

/* ------------------------------------------------ referencement */

/*
 * Le titre et la description de CHAQUE page, module « Bannière ». Le test
 * parcourt les six ecrans plutot que d'en nommer un : un septieme ecran qui
 * oublierait de les declarer le ferait tomber.
 */
it('rend chaque page modifiable dans les resultats de recherche', function (string $ecran, string $module, string $adresse) {
    saisirTexte($this->admin, $ecran, $module, 'meta_titre', 'Titre choisi par l’agence');
    saisirTexte($this->admin, $ecran, $module, 'meta_description', 'Description choisie par l’agence.');

    $this->get($adresse)->assertOk()
        ->assertSee('<title>Titre choisi par l’agence — SCI4K</title>', false)
        ->assertSee('content="Description choisie par l’agence."', false);
})->with([
    [PageAccueil::class, 'hero', '/'],
    [PageContact::class, 'banniere', '/contact'],
    [PageFaq::class, 'banniere', '/faq'],
    [PagePresentation::class, 'banniere', '/presentation'],
    [PageServices::class, 'banniere', '/services'],
]);

it('garde le titre d origine tant que rien n est saisi', function () {
    // Une base vierge annonce exactement ce qu'elle annonçait avant.
    $this->get(route('contact.index'))->assertOk()
        ->assertSee('<title>Contact — SCI4K</title>', false);
});

it('declare le referencement sur les sept ecrans de page', function () {
    // Un ecran qui ne le declarerait pas laisserait sa page mentir aux moteurs
    // sans que personne ne s'en apercoive : la page s'affiche parfaitement.
    $ecrans = [
        PageAccueil::class => 'hero',
        PageContact::class => 'banniere',
        PageFaq::class => 'banniere',
        PagePresentation::class => 'banniere',
        PageServices::class => 'banniere',
        App\Livewire\Admin\PageActualites::class => 'banniere',
        App\Livewire\Admin\PageBiens::class => 'banniere',
    ];

    $manquants = [];

    foreach ($ecrans as $ecran => $module) {
        $textes = (new $ecran)->modules()[$module]['textes'] ?? [];

        if (! isset($textes['meta_titre'], $textes['meta_description'])) {
            $manquants[] = $ecran;
        }
    }

    expect($manquants)->toBe([]);
});

it("n'ecrit que les cles que le module declare", function () {
    Livewire::actingAs($this->admin)
        ->test(PageContact::class)
        ->call('ouvrir', 'coordonnees')
        ->set('textes.mise_en_page_fr', 'valeur injectée')
        ->call('enregistrer');

    expect(ReglageDeSection::where('slug', 'contact.info')->first()?->option('mise_en_page_fr'))
        ->toBeNull();
});
