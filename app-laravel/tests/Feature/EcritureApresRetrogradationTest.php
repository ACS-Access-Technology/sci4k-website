<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\ServiceFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\Service;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Le controle de role doit etre refait dans chaque methode d'ecriture.
 *
 * Constat de la relecture adversariale. ListeOrdonnable documente la regle en
 * tete et l'applique ; les trois formulaires ne le faisaient pas, leur seule
 * protection etant le middleware `role:` de la route. Or Livewire ne rejoue
 * sur /livewire/update que les middlewares d'authentification du framework :
 * RoleMiddleware n'y figure pas.
 *
 * Le cas reel n'est pas un lecteur qui forge une requete — il ne peut pas
 * obtenir d'instantane valide — mais un EDITEUR RETROGRADE dont la page reste
 * ouverte : sans ce controle, son onglet continue d'enregistrer.
 */
beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    $this->utilisateur = User::factory()->create();
    $this->utilisateur->assignRole('editeur');
});

/** Retrograde l'utilisateur, page deja ouverte. */
function retrograder(User $utilisateur): void
{
    $utilisateur->syncRoles(['lecteur']);
}

it('refuse d enregistrer un service apres retrogradation', function () {
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier', 'nom_fr' => 'Foncier',
    ]);

    $composant = Livewire::actingAs($this->utilisateur)
        ->test(ServiceFormulaire::class, ['service' => $service]);

    retrograder($this->utilisateur);

    $composant->set('nomFr', 'Renommé après retrogradation')
        ->call('enregistrer')
        ->assertForbidden();

    expect($service->fresh()->nom_fr)->toBe('Foncier');
});

it('refuse d enregistrer une question apres retrogradation', function () {
    $rubrique = RubriqueFaq::factory()->create(['slug' => 'foncier']);
    $question = QuestionFaq::factory()->create([
        'rubrique_id' => $rubrique->id, 'question_fr' => 'Question initiale ?',
    ]);

    $composant = Livewire::actingAs($this->utilisateur)
        ->test(FaqFormulaire::class, ['question' => $question]);

    retrograder($this->utilisateur);

    $composant->set('questionFr', 'Modifiée après retrogradation ?')
        ->call('enregistrer')
        ->assertForbidden();

    expect($question->fresh()->question_fr)->toBe('Question initiale ?');
});

it('refuse d enregistrer un article apres retrogradation', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'titre_fr' => 'Titre initial',
    ]);

    $composant = Livewire::actingAs($this->utilisateur)
        ->test(ArticleFormulaire::class, ['article' => $article]);

    retrograder($this->utilisateur);

    $composant->set('titreFr', 'Modifié après retrogradation')
        ->call('enregistrer')
        ->assertForbidden();

    expect($article->fresh()->titre_fr)->toBe('Titre initial');
});

it('laisse un editeur enregistrer normalement', function () {
    // Le controle ne doit pas fermer la porte a ceux qui doivent passer.
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier',
    ]);

    Livewire::actingAs($this->utilisateur)
        ->test(ServiceFormulaire::class, ['service' => $service])
        ->set('nomFr', 'Foncier et titres')
        ->set('nomEn', 'Land and titles')
        ->set('accrocheFr', 'a')->set('accrocheEn', 'b')
        ->set('descriptionFr', 'c')->set('descriptionEn', 'd')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($service->fresh()->nom_fr)->toBe('Foncier et titres');
});

it('ne laisse pas le navigateur reecrire le chemin de l image', function () {
    // imageActuelle sert de valeur enregistree ET de chemin d'effacement :
    // une propriete publique non verrouillee aurait permis de designer le
    // fichier d'un autre ecran.
    $service = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier',
        'image_source' => 'images/services/foncier.jpg',
    ]);

    expect(fn () => Livewire::actingAs($this->utilisateur)
        ->test(ServiceFormulaire::class, ['service' => $service])
        ->set('imageActuelle', 'storage/services/../couvertures/article.jpg')
    )->toThrow(Exception::class);
});
