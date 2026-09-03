<?php

use App\Livewire\Admin\ArticleListe;
use App\Livewire\Admin\BienListe;
use App\Livewire\Admin\FaqListe;
use App\Livewire\Admin\PartenaireListe;
use App\Livewire\Admin\RubriqueFaqListe;
use App\Livewire\Admin\ServiceListe;
use App\Livewire\Admin\TemoignageListe;
use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Le formulaire ouvert dans une liste doit rester AU-DESSUS du tableau.
 *
 * Ouvert en dessous, il s'affichait hors de l'ecran des qu'une liste depassait
 * une hauteur de fenetre : on cliquait « Modifier », rien ne semblait se
 * passer, et il fallait penser a faire defiler la page pour decouvrir que le
 * formulaire etait la depuis le debut.
 *
 * Le partiel commun porte aussi le defilement qui amene le bloc sous les yeux
 * — indispensable quand on edite une ligne du bas d'un long tableau.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/**
 * @return array{0: class-string, 1: int}
 */
function listeAvecUneLigne(string $composant): array
{
    return match ($composant) {
        FaqListe::class => [$composant, QuestionFaq::factory()->create([
            'rubrique_id' => RubriqueFaq::factory()->create()->id,
        ])->id],
        RubriqueFaqListe::class => [$composant, RubriqueFaq::factory()->create()->id],
        ServiceListe::class => [$composant, Service::factory()->create()->id],
        ArticleListe::class => [$composant, Article::factory()->create([
            'categorie_id' => Categorie::factory()->create()->id,
        ])->id],
        BienListe::class => [$composant, Bien::factory()->create()->id],
        PartenaireListe::class => [$composant, Partenaire::factory()->create()->id],
        TemoignageListe::class => [$composant, Temoignage::factory()->create()->id],
    };
}

it('ouvre le formulaire au-dessus du tableau', function (string $composant) {
    [$classe, $id] = listeAvecUneLigne($composant);

    $html = Livewire::actingAs($this->admin)
        ->test($classe, ['embarque' => true])
        ->call('ouvrirEdition', $id)
        ->html();

    $formulaire = strpos($html, 'formulaire-'.$id);
    $tableau = strpos($html, '<table');

    expect($formulaire)->not->toBeFalse('le formulaire n’est pas rendu')
        ->and($tableau)->not->toBeFalse('le tableau n’est pas rendu')
        ->and($formulaire)->toBeLessThan($tableau);
})->with([
    [FaqListe::class],
    [RubriqueFaqListe::class],
    [ServiceListe::class],
    [ArticleListe::class],
    [BienListe::class],
    [PartenaireListe::class],
    [TemoignageListe::class],
]);

/**
 * Ouvert depuis une ligne du bas d'un long tableau, le formulaire s'affiche
 * en haut : hors de l'ecran a son tour. Le partiel le fait donc defiler
 * jusqu'au regard.
 */
it('amene le formulaire sous les yeux a l ouverture', function (string $composant) {
    [$classe, $id] = listeAvecUneLigne($composant);

    Livewire::actingAs($this->admin)
        ->test($classe, ['embarque' => true])
        ->call('ouvrirEdition', $id)
        ->assertSee('scrollIntoView', false);
})->with([
    [FaqListe::class],
    [RubriqueFaqListe::class],
    [ServiceListe::class],
    [ArticleListe::class],
    [BienListe::class],
]);

/** Le defilement respecte la preference systeme « reduire les animations ». */
it('n anime pas le defilement pour qui le refuse', function () {
    RubriqueFaq::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(RubriqueFaqListe::class, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertSee('prefers-reduced-motion', false);
});

/** Sur son propre ecran, la liste n'ouvre aucun formulaire sur place. */
it('n ouvre pas de formulaire sur place hors embarquement', function () {
    QuestionFaq::factory()->create(['rubrique_id' => RubriqueFaq::factory()->create()->id]);

    Livewire::actingAs($this->admin)
        ->test(FaqListe::class)
        ->assertDontSee('scrollIntoView', false);
});
