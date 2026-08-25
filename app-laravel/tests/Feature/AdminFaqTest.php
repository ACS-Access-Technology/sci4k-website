<?php

use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
    $this->service = Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'foncier', 'nom_fr' => 'Foncier',
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les questions groupees par service', function () {
    QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'Une question ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->assertSee('Une question ?')
        ->assertSee('Foncier');
});

it('cree une question', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('serviceId', (string) $this->service->id)
        ->set('questionFr', 'Nouvelle question ?')
        ->set('questionEn', 'New question?')
        ->set('reponseFr', 'La réponse.')
        ->set('reponseEn', 'The answer.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::where('question_fr', 'Nouvelle question ?')->exists())->toBeTrue();
});

it('exige un service', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('questionFr', 'Sans service ?')
        ->set('questionEn', 'Without service?')
        ->set('reponseFr', 'Réponse.')
        ->set('reponseEn', 'Answer.')
        ->call('enregistrer')
        ->assertHasErrors(['serviceId']);
});

it('modifie une question existante sans en creer une seconde', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id, 'question_fr' => 'Ancienne ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class, ['question' => $q])
        ->set('questionFr', 'Nouvelle ?')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::count())->toBe(1);
    expect($q->fresh()->question_fr)->toBe('Nouvelle ?');
});

it('supprime une question', function () {
    $q = QuestionFaq::factory()->create(['service_id' => $this->service->id]);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->call('supprimer', $q->id);

    expect(QuestionFaq::find($q->id))->toBeNull();
});

it('interdit a un lecteur d ouvrir la creation', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $this->actingAs($lecteur)->get(route('admin.faq.creation'))->assertForbidden();
});
