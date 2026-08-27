<?php

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\Service;

beforeEach(function () {
    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier', 'ordre' => 1, 'visible' => true,
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
        'accroche_fr' => 'Sécuriser vos terrains', 'accroche_en' => 'Secure your land',
        'atout1_fr' => 'Vérification ACD', 'atout1_en' => 'ACD check',
    ]);
});

it('affiche les services publies', function () {
    $this->get('/services')->assertOk()->assertSee('Foncier')->assertSee('Sécuriser vos terrains');
});

it('ne montre pas un service masque', function () {
    Service::factory()->create(['nom_fr' => 'Service caché', 'visible' => false, 'categorie_id' => $this->service->categorie_id]);

    $this->get('/services')->assertOk()->assertDontSee('Service caché');
});

it('respecte l ordre d affichage', function () {
    Service::factory()->create(['nom_fr' => 'Deuxieme', 'ordre' => 2, 'categorie_id' => $this->service->categorie_id]);

    $corps = $this->get('/services')->assertOk()->getContent();

    expect(strpos($corps, 'Foncier'))->toBeLessThan(strpos($corps, 'Deuxieme'));
});

it('sert les services en anglais', function () {
    $this->get('/langue/en');

    $this->get('/services')->assertOk()->assertSee('Land &amp; Title', false)->assertSee('Secure your land');
});

it('affiche la FAQ groupee par rubrique', function () {
    $rubrique = RubriqueFaq::factory()->create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'ordre' => 1]);

    QuestionFaq::factory()->create([
        'rubrique_id' => $rubrique->id, 'ordre' => 1, 'visible' => true,
        'question_fr' => "Qu'est-ce qu'un ACD ?", 'reponse_fr' => 'Un arrêté officiel.',
    ]);

    $this->get('/faq')->assertOk()
        ->assertSee('Foncier')
        ->assertSee("Qu'est-ce qu'un ACD ?", false)
        ->assertSee('Un arrêté officiel.');
});

it('ne montre pas une question masquee', function () {
    QuestionFaq::factory()->create([
        'visible' => false, 'question_fr' => 'Question cachée ?',
    ]);

    $this->get('/faq')->assertOk()->assertDontSee('Question cachée ?', false);
});

it('retire de la page les questions d une rubrique masquee', function () {
    // Masquer une rubrique doit emporter ses questions : sans ce filtre, la
    // page gardait un groupe entier, titre compris, que l'editeur croyait
    // avoir retire du site.
    $rubrique = RubriqueFaq::factory()->create(['nom_fr' => 'Rubrique masquée', 'visible' => false]);

    QuestionFaq::factory()->create([
        'rubrique_id' => $rubrique->id, 'visible' => true,
        'question_fr' => 'Question dans une rubrique masquée ?',
    ]);

    $reponse = $this->get('/faq')->assertOk();

    $reponse->assertDontSee('Rubrique masquée', false);
    $reponse->assertDontSee('Question dans une rubrique masquée ?', false);
});

it('redirige les anciennes adresses', function () {
    $this->get('/services.html')->assertRedirect('/services');
    $this->get('/faq.html')->assertRedirect('/faq');
});
