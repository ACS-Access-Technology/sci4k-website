<?php

use App\Livewire\Admin\Menus;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les textes de l'en-tete, du pied et des boutons flottants.
 *
 * Vingt et une chaines etaient ecrites en dur dans ces trois partiels et
 * traduites par __() : aucun ecran ne les exposait. Elles apparaissent sur
 * TOUTES les pages du site.
 *
 * Beaucoup ne se lisent qu'a la souris ou au lecteur d'ecran — un intitule de
 * bouton, une bulle d'aide. Un texte qu'un visiteur peut entendre est un texte
 * que l'agence doit pouvoir changer.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Saisit un texte depuis l'ecran Menus, comme le ferait l'administrateur. */
function saisirHabillage(User $admin, string $cle, string $valeur): void
{
    Livewire::actingAs($admin)
        ->test(Menus::class)
        ->set('textes.'.$cle.'_fr', $valeur)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

it('rend le site inchange tant que rien n est saisi', function () {
    $this->get('/')->assertOk()
        ->assertSee('Navigation', false)
        ->assertSee('Nous contacter', false)
        ->assertSee('Votre adresse email', false);
});

it('applique les titres des colonnes du pied de page', function () {
    saisirHabillage($this->admin, 'titre_navigation', 'Le site');
    saisirHabillage($this->admin, 'titre_services', 'Ce que nous faisons');

    $this->get('/')->assertOk()
        ->assertSee('Le site', false)
        ->assertSee('Ce que nous faisons', false)
        ->assertDontSee('<h5>Navigation</h5>', false);
});

it('applique les mentions devant les coordonnees', function () {
    saisirHabillage($this->admin, 'libelle_telephone', 'Appelez-nous :');
    saisirHabillage($this->admin, 'libelle_email', 'Écrivez-nous :');

    $this->get('/')->assertOk()
        ->assertSee('Appelez-nous :', false)
        ->assertSee('Écrivez-nous :', false);
});

it('applique les descriptions des boutons de l en-tete', function () {
    // Elles ne se lisent qu'au lecteur d'ecran, ce qui ne les rend pas moins
    // publiques.
    saisirHabillage($this->admin, 'aria_langue', 'Passer en anglais');

    $this->get('/')->assertOk()->assertSee('Passer en anglais', false);
});

it('applique les descriptions des boutons flottants', function () {
    saisirHabillage($this->admin, 'aria_whatsapp', 'Nous écrire sur WhatsApp');

    $this->get('/')->assertOk()->assertSee('Nous écrire sur WhatsApp', false);
});

/*
 * L'habillage est le seul bloc present sur TOUTES les pages. Une saisie unique
 * doit valoir partout, sans quoi il faudrait la refaire page par page.
 */
it('applique une saisie unique a toutes les pages du site', function () {
    saisirHabillage($this->admin, 'titre_contact', 'Nous joindre');

    foreach (['/', route('services.index'), route('faq.index'), route('actualites.index')] as $adresse) {
        $this->get($adresse)->assertOk()->assertSee('Nous joindre', false);
    }
});

it('garde chaque langue de son cote', function () {
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->set('textes.titre_navigation_fr', 'Le site')
        ->set('textes.titre_navigation_en', 'The site')
        ->call('enregistrer');

    $section = ReglageDeSection::where('slug', Menus::SECTION)->first();

    expect($section->texteBilingue('titre_navigation', 'fr'))->toBe('Le site')
        ->and($section->texteBilingue('titre_navigation', 'en'))->toBe('The site');
});

it("n'ecrit que les cles que l ecran declare", function () {
    // `$textes` est une propriete publique : le navigateur en fixe le contenu,
    // CLES COMPRISES.
    Livewire::actingAs($this->admin)
        ->test(Menus::class)
        ->set('textes.mise_en_page_fr', 'valeur injectée')
        ->call('enregistrer');

    expect(ReglageDeSection::where('slug', Menus::SECTION)->first()?->option('mise_en_page_fr'))
        ->toBeNull();
});

it('reserve ces textes aux administrateurs', function () {
    // L'ecran Menus l'etait deja : ces textes n'elargissent pas son acces.
    Role::findOrCreate('editeur');
    $editeur = User::factory()->create();
    $editeur->assignRole('editeur');

    Livewire::actingAs($editeur)->test(Menus::class)->assertForbidden();
});
