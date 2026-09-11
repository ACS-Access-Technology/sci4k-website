<?php

/*
 * Archiver un article depuis le backoffice.
 *
 * Constate en recette : la liste des articles propose « Archivé » dans son
 * filtre et compte les archives dans ses indicateurs, mais AUCUN ecran ne
 * permettait d'atteindre cet etat — le formulaire n'offrait que « Brouillon »
 * et « Publié », et la validation refusait le reste.
 *
 * Un filtre qui ne trouve jamais rien est pire qu'un filtre absent : il laisse
 * chercher une manoeuvre qui n'existe pas.
 *
 * L'archive se distingue du brouillon par l'intention, non par l'effet public
 * — les deux retirent l'article du site. Un brouillon est un texte qui n'est
 * pas encore pret ; une archive est un texte qui a vecu et qu'on retire.
 */

use App\Livewire\Admin\ArticleFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('redacteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'a-archiver',
        'titre_fr' => 'Article à archiver',
        'statut' => 'publie',
    ]);
});

it('archive un article depuis le formulaire', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $this->article])
        ->set('statut', 'archive')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->article->fresh()->statut)->toBe('archive');
});

/** L'archive quitte le site sans quitter la base. */
it('retire du site public un article archive', function () {
    $this->get('/actualites')->assertSee('Article à archiver', false);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $this->article])
        ->set('statut', 'archive')
        ->call('enregistrer');

    $this->get('/actualites')->assertDontSee('Article à archiver', false);
    $this->get('/actualites/a-archiver')->assertNotFound();

    expect(Article::where('slug', 'a-archiver')->exists())->toBeTrue();
});

/** Le choix doit se VOIR : une regle acceptee sans champ pour la servir ne sert personne. */
it('propose l archive dans le formulaire', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $this->article])
        ->assertSeeHtml('value="archive"');
});

/*
 * Un redacteur ne publie pas — il n'archive pas davantage : retirer du site un
 * texte deja en ligne est la meme decision editoriale que l'y mettre.
 */
it('interdit a un redacteur d archiver', function () {
    $redacteur = User::factory()->create();
    $redacteur->assignRole('redacteur');

    // Son propre article : sans cela il ne peut meme pas ouvrir l'ecran, et le
    // test mesurerait le controle d'acces au lieu du controle de statut.
    $sien = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'son-article',
        'statut' => 'brouillon',
        'auteur_id' => $redacteur->id,
    ]);

    Livewire::actingAs($redacteur)
        ->test(ArticleFormulaire::class, ['article' => $sien])
        ->set('statut', 'archive')
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Ramene a « brouillon » par le composant, comme pour une tentative de
    // publication : la propriete est publique, le navigateur peut la fixer.
    expect($sien->fresh()->statut)->toBe('brouillon');
});
