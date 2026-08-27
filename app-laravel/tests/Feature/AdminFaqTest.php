<?php

use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->rubrique = RubriqueFaq::factory()->create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les questions avec leur rubrique', function () {
    QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id, 'question_fr' => 'Une question ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqListe::class)
        ->assertSee('Une question ?')
        ->assertSee('Foncier');
});

it('cree une question', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('rubriqueId', (string) $this->rubrique->id)
        ->set('questionFr', 'Nouvelle question ?')
        ->set('questionEn', 'New question?')
        ->set('reponseFr', 'La réponse.')
        ->set('reponseEn', 'The answer.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::where('question_fr', 'Nouvelle question ?')->exists())->toBeTrue();
});

it('exige une rubrique', function () {
    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class)
        ->set('questionFr', 'Sans rubrique ?')
        ->set('questionEn', 'Without rubric?')
        ->set('reponseFr', 'Réponse.')
        ->set('reponseEn', 'Answer.')
        ->call('enregistrer')
        ->assertHasErrors(['rubriqueId']);
});

it('modifie une question existante sans en creer une seconde', function () {
    $q = QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id, 'question_fr' => 'Ancienne ?']);

    Livewire::actingAs($this->editeur)
        ->test(FaqFormulaire::class, ['question' => $q])
        ->set('questionFr', 'Nouvelle ?')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(QuestionFaq::count())->toBe(1);
    expect($q->fresh()->question_fr)->toBe('Nouvelle ?');
});

it('supprime une question', function () {
    $q = QuestionFaq::factory()->create(['rubrique_id' => $this->rubrique->id]);

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

it('range chaque question creee a la suite de son groupe', function () {
    foreach (['Première ?', 'Deuxième ?'] as $intitule) {
        Livewire::actingAs($this->editeur)
            ->test(FaqFormulaire::class)
            ->set('rubriqueId', (string) $this->rubrique->id)
            ->set('questionFr', $intitule)
            ->set('questionEn', 'Question?')
            ->set('reponseFr', 'Réponse.')
            ->set('reponseEn', 'Answer.')
            ->call('enregistrer')
            ->assertHasNoErrors();
    }

    $rangs = QuestionFaq::where('rubrique_id', $this->rubrique->id)->orderBy('id')->pluck('ordre')->all();

    expect($rangs)->toBe([1, 2]);
});
