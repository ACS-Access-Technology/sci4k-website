<?php

use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\RubriqueFaqFormulaire;
use App\Livewire\Admin\RubriqueFaqListe;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Rubriques de la FAQ.
 *
 * Le client demande de pouvoir ouvrir une rubrique au moment ou l'on ecrit la
 * question, sans etre oblige de choisir un service existant. Le cadrage du lot
 * disait deja « Question FAQ : groupe, question, reponse » : c'est le plan qui
 * avait replie le groupe sur le service, les six coincidant.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');

    $this->rubrique = RubriqueFaq::factory()->create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

/* ------------------------------------ creation depuis le formulaire de FAQ */

it('cree une rubrique depuis le formulaire d une question', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', FaqFormulaire::NOUVELLE_RUBRIQUE)
        ->set('nouvelleRubriqueFr', 'Paiements')
        ->set('nouvelleRubriqueEn', 'Payments')
        ->set('questionFr', 'Quels moyens de paiement ?')
        ->set('questionEn', 'Which payment methods?')
        ->set('reponseFr', 'Virement ou espèces.')
        ->set('reponseEn', 'Transfer or cash.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $rubrique = RubriqueFaq::where('nom_fr', 'Paiements')->first();

    expect($rubrique)->not->toBeNull();
    expect($rubrique->slug)->toBe('paiements');
    expect($rubrique->nom_en)->toBe('Payments');
    expect($rubrique->questions()->count())->toBe(1);
});

it('exige les deux langues de la rubrique creee', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', FaqFormulaire::NOUVELLE_RUBRIQUE)
        ->set('questionFr', 'Une question ?')
        ->set('questionEn', 'A question?')
        ->set('reponseFr', 'Réponse.')
        ->set('reponseEn', 'Answer.')
        ->call('enregistrer')
        ->assertHasErrors(['nouvelleRubriqueFr', 'nouvelleRubriqueEn']);

    expect(RubriqueFaq::count())->toBe(1);
});

it('n exige pas les champs de creation quand on choisit une rubrique existante', function () {
    // Le piege inverse : des regles « required » posees sans condition
    // auraient refuse toute question rangee dans une rubrique deja la.
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', (string) $this->rubrique->id)
        ->set('questionFr', 'Une question ?')
        ->set('questionEn', 'A question?')
        ->set('reponseFr', 'Réponse.')
        ->set('reponseEn', 'Answer.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(RubriqueFaq::count())->toBe(1);
    expect($this->rubrique->questions()->count())->toBe(1);
});

it('place la rubrique creee en fin de liste', function () {
    RubriqueFaq::factory()->create(['slug' => 'deuxieme', 'ordre' => 2]);

    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', FaqFormulaire::NOUVELLE_RUBRIQUE)
        ->set('nouvelleRubriqueFr', 'Paiements')
        ->set('nouvelleRubriqueEn', 'Payments')
        ->set('questionFr', 'Q ?')->set('questionEn', 'Q?')
        ->set('reponseFr', 'R.')->set('reponseEn', 'A.')
        ->call('enregistrer');

    expect(RubriqueFaq::ordonnees()->pluck('slug')->all())
        ->toBe(['foncier', 'deuxieme', 'paiements']);
});

it('suffixe le slug quand une rubrique porte deja le meme nom', function () {
    // La colonne est unique : un editeur qui saisit deux fois « Paiements »
    // n'attend pas une erreur de base de donnees mais deux rubriques.
    foreach (['Paiements', 'Paiements'] as $nom) {
        Livewire::actingAs($this->editeur)
            ->test(FaqFormulaire::class)
            ->set('rubriqueId', FaqFormulaire::NOUVELLE_RUBRIQUE)
            ->set('nouvelleRubriqueFr', $nom)
            ->set('nouvelleRubriqueEn', 'Payments')
            ->set('questionFr', 'Q '.uniqid().' ?')->set('questionEn', 'Q?')
            ->set('reponseFr', 'R.')->set('reponseEn', 'A.')
            ->call('enregistrer')
            ->assertHasNoErrors();
    }

    expect(RubriqueFaq::where('nom_fr', 'Paiements')->pluck('slug')->all())
        ->toBe(['paiements', 'paiements-2']);
});

it('range la question a la suite de SA rubrique, et non de la table entiere', function () {
    // Deux rubriques differentes portent couramment le rang 1 : un rang global
    // ferait se croiser leurs questions sur la page publique.
    $autre = RubriqueFaq::factory()->create(['slug' => 'autre', 'ordre' => 2]);

    QuestionFaq::factory()->create(['rubrique_id' => $autre->id, 'ordre' => 1]);
    QuestionFaq::factory()->create(['rubrique_id' => $autre->id, 'ordre' => 2]);

    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', (string) $this->rubrique->id)
        ->set('questionFr', 'Première du groupe ?')
        ->set('questionEn', 'First of the group?')
        ->set('reponseFr', 'R.')->set('reponseEn', 'A.')
        ->call('enregistrer');

    expect(QuestionFaq::where('question_fr', 'Première du groupe ?')->value('ordre'))->toBe(1);
});

/* ------------------------------------------------- ecran des rubriques */

it('liste les rubriques avec le nombre de questions', function () {
    QuestionFaq::factory()->count(2)->create(['rubrique_id' => $this->rubrique->id]);

    Livewire::actingAs($this->editeur)
        ->test(RubriqueFaqListe::class)
        ->assertSee('Foncier')
        ->assertSee('2 questions');
});

it('renomme une rubrique sans toucher a son slug', function () {
    // Le slug relie les rubriques aux six services d'origine, et c'est ce lien
    // que la migration emprunte pour revenir en arriere.
    Livewire::actingAs($this->editeur)
        ->test(RubriqueFaqFormulaire::class, ['rubrique' => $this->rubrique])
        ->set('nomFr', 'Foncier et titres')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $rubrique = $this->rubrique->fresh();

    expect($rubrique->nom_fr)->toBe('Foncier et titres');
    expect($rubrique->slug)->toBe('foncier');
});

it('refuse de supprimer une rubrique qui porte des questions', function () {
    QuestionFaq::factory()->count(2)->create(['rubrique_id' => $this->rubrique->id]);

    Livewire::actingAs($this->editeur)
        ->test(RubriqueFaqListe::class)
        ->call('supprimer', $this->rubrique->id)
        ->assertSee('2 questions');

    expect(RubriqueFaq::find($this->rubrique->id))->not->toBeNull();
});

it('supprime une rubrique vide', function () {
    Livewire::actingAs($this->editeur)
        ->test(RubriqueFaqListe::class)
        ->call('supprimer', $this->rubrique->id);

    expect(RubriqueFaq::find($this->rubrique->id))->toBeNull();
});

it('interdit a un lecteur de creer ou de supprimer une rubrique', function () {
    $this->actingAs($this->lecteur)
        ->get(route('admin.rubriques-faq.creation'))
        ->assertForbidden();

    Livewire::actingAs($this->lecteur)
        ->test(RubriqueFaqListe::class)
        ->call('supprimer', $this->rubrique->id)
        ->assertForbidden();

    expect(RubriqueFaq::find($this->rubrique->id))->not->toBeNull();
});

it('refuse l enregistrement a un lecteur, meme sur une page laissee ouverte', function () {
    // Livewire ne rejoue pas le middleware de role sur /livewire/update : la
    // route protege l'ecran, pas l'action. Un editeur retrograde en lecteur
    // continuerait sinon d'enregistrer depuis son onglet.
    Livewire::actingAs($this->lecteur)
        ->test(RubriqueFaqFormulaire::class, ['rubrique' => $this->rubrique])
        ->set('nomFr', 'Renommée par un lecteur')
        ->call('enregistrer')
        ->assertForbidden();

    expect($this->rubrique->fresh()->nom_fr)->toBe('Foncier');
});

it('masque du site les questions d une rubrique masquee', function () {
    QuestionFaq::factory()->create([
        'rubrique_id' => $this->rubrique->id, 'visible' => true,
        'question_fr' => 'Question visible ?',
    ]);

    $this->get('/faq')->assertOk()->assertSee('Question visible ?', false);

    Livewire::actingAs($this->editeur)
        ->test(RubriqueFaqListe::class)
        ->call('basculerVisibilite', $this->rubrique->id);

    $this->get('/faq')->assertOk()->assertDontSee('Question visible ?', false);
});
