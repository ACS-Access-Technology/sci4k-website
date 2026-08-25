<?php

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;

beforeEach(function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier',
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
    ]);
});

it('rend question et reponse dans la langue demandee', function () {
    $q = QuestionFaq::factory()->create([
        'service_id' => $this->service->id,
        'question_fr' => "Qu'est-ce qu'un ACD ?", 'question_en' => 'What is an ACD?',
        'reponse_fr' => 'Un arrêté officiel.', 'reponse_en' => 'An official order.',
    ]);

    expect($q->question('en'))->toBe('What is an ACD?');
    expect($q->reponse('fr'))->toBe('Un arrêté officiel.');
});

it('appartient a un service, qui sert de titre de groupe', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id]);

    expect($q->service->nom('fr'))->toBe('Foncier');
});

it('se groupe par service, dans l ordre des services', function () {
    $autre = Service::factory()->create(['slug' => 'construction', 'nom_fr' => 'Construction', 'ordre' => 2]);
    $this->service->update(['ordre' => 1]);

    QuestionFaq::factory()->create(['service_id' => $autre->id, 'question_fr' => 'B', 'ordre' => 1]);
    QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'A', 'ordre' => 1]);

    $groupes = QuestionFaq::visibles()->with('service')->get()
        ->sortBy(fn ($q) => [$q->service->ordre, $q->ordre])
        ->groupBy(fn ($q) => $q->service->nom('fr'));

    expect($groupes->keys()->all())->toBe(['Foncier', 'Construction']);
});

it('replie sur le francais quand l anglais manque', function () {
    $q = QuestionFaq::factory()->create([
        'service_id' => $this->service->id,
        'reponse_fr' => 'Réponse française.', 'reponse_en' => '',
    ]);

    expect($q->reponse('en'))->toBe('Réponse française.');
});
