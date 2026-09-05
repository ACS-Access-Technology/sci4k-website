<?php

use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->rubrique = RubriqueFaq::factory()->create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('rend question et reponse dans la langue demandee', function () {
    $q = QuestionFaq::factory()->create([
        'rubrique_id' => $this->rubrique->id,
        'question_fr' => "Qu'est-ce qu'un ACD ?", 'question_en' => 'What is an ACD?',
        'reponse_fr' => 'Un arrêté officiel.', 'reponse_en' => 'An official order.',
    ]);

    expect($q->question('en'))->toBe('What is an ACD?');
    expect($q->reponse('fr'))->toBe('Un arrêté officiel.');
});

it('appartient a une rubrique, qui sert de titre de groupe', function () {
    $q = QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id]);

    expect($q->rubrique->nom('fr'))->toBe('Foncier');
});

it('se groupe par rubrique sur la page publique, dans l ordre des rubriques', function () {
    // Le test interroge la PAGE, et non une requete recopiee du controleur.
    // La version precedente reproduisait le tri ligne pour ligne : elle ne
    // pouvait donc pas detecter une divergence du controleur, ce qui est
    // precisement ce qu'un test de groupement doit surveiller.
    $seconde = RubriqueFaq::factory()->create([
        'slug' => 'construction', 'nom_fr' => 'Construction', 'ordre' => 2,
    ]);

    QuestionFaq::factory()->create(['rubrique_id' => $seconde->id, 'question_fr' => 'Question B ?', 'ordre' => 1]);
    QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id, 'question_fr' => 'Question A ?', 'ordre' => 1]);

    $corps = $this->get('/faq')->assertOk()->getContent();

    expect(strpos($corps, 'Foncier'))->toBeLessThan(strpos($corps, 'Construction'));
    expect(strpos($corps, 'Question A ?'))->toBeLessThan(strpos($corps, 'Question B ?'));
});

it('replie sur le francais quand l anglais manque', function () {
    $q = QuestionFaq::factory()->create([
        'rubrique_id' => $this->rubrique->id,
        'reponse_fr' => 'Réponse française.', 'reponse_en' => '',
    ]);

    expect($q->reponse('en'))->toBe('Réponse française.');
});

it('refuse de supprimer une rubrique qui porte des questions', function () {
    QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id]);

    // RESTRICT plutot que CASCADE : perdre du contenu redige parce qu'on a
    // supprime son classement doit etre un refus explicite, pas un effet de
    // bord. L'ecran d'administration attrape ce refus et l'explique.
    expect(fn () => $this->rubrique->delete())
        ->toThrow(QueryException::class);

    expect(QuestionFaq::count())->toBe(1);
});
