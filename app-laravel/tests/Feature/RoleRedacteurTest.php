<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ArticleListe;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Le role Redacteur.
 *
 * La maquette le decrit ainsi : « Cree et modifie SES PROPRES contenus,
 * publication soumise a validation ». Ces deux promesses doivent etre tenues
 * par du code, sans quoi le panneau des roles ne serait qu'un texte — et
 * l'ecran mentirait sur ce qu'il autorise.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->categorie = Categorie::factory()->create();

    $this->redacteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->redacteur->assignRole('redacteur');

    $this->autreRedacteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->autreRedacteur->assignRole('redacteur');

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');
});

it('laisse un redacteur entrer dans l administration', function () {
    $this->actingAs($this->redacteur)->get('/admin/articles')->assertOk();
});

it('lui refuse les ecrans qui ne sont pas les articles', function () {
    // Son role porte sur les articles, et rien d'autre.
    $this->actingAs($this->redacteur)->get('/admin/services/creation')->assertForbidden();
    $this->actingAs($this->redacteur)->get('/admin/configuration')->assertForbidden();
});

/* ------------------------------------------------ ses propres articles */

it('ne montre a un redacteur que ses propres articles', function () {
    Article::factory()->create(['auteur_id' => $this->redacteur->id, 'titre_fr' => 'Le mien', 'categorie_id' => $this->categorie->id]);
    Article::factory()->create(['auteur_id' => $this->autreRedacteur->id, 'titre_fr' => 'Celui du voisin', 'categorie_id' => $this->categorie->id]);

    $corps = Livewire::actingAs($this->redacteur)->test(ArticleListe::class)->html();

    expect($corps)->toContain('Le mien')
        ->and($corps)->not->toContain('Celui du voisin');
});

it('montre a un editeur les articles de tout le monde', function () {
    Article::factory()->create(['auteur_id' => $this->redacteur->id, 'titre_fr' => 'Le sien', 'categorie_id' => $this->categorie->id]);

    expect(Livewire::actingAs($this->editeur)->test(ArticleListe::class)->html())
        ->toContain('Le sien');
});

it('refuse a un redacteur d ouvrir l article d un autre', function () {
    $article = Article::factory()->create(['auteur_id' => $this->autreRedacteur->id, 'categorie_id' => $this->categorie->id]);

    $this->actingAs($this->redacteur)
        ->get(route('admin.articles.edition', $article))
        ->assertForbidden();
});

it('laisse un redacteur ouvrir son propre article', function () {
    $article = Article::factory()->create(['auteur_id' => $this->redacteur->id, 'categorie_id' => $this->categorie->id]);

    $this->actingAs($this->redacteur)
        ->get(route('admin.articles.edition', $article))
        ->assertOk();
});

/* ------------------------------------------------ pas de publication */

it('empeche un redacteur de publier, meme en forgeant la valeur', function () {
    // `statut` est une propriete publique : le champ est masque dans le
    // gabarit, mais le navigateur peut en fixer la valeur sur
    // /livewire/update. Le controle vit donc dans le composant, pas dans la
    // vue — c'est le seul endroit ou il tient.
    Livewire::actingAs($this->redacteur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'mon-article')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', now()->format('Y-m-d'))
        ->set('statut', 'publie')
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')->set('contenuEn', 'Content')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Article::where('slug', 'mon-article')->value('statut'))->toBe('brouillon');
});

it('laisse un editeur publier', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'article-publie')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', now()->format('Y-m-d'))
        ->set('statut', 'publie')
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')->set('contenuEn', 'Content')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Article::where('slug', 'article-publie')->value('statut'))->toBe('publie');
});

it('ne propose pas le choix du statut a un redacteur', function () {
    $corps = Livewire::actingAs($this->redacteur)->test(ArticleFormulaire::class)->html();

    // On le lui DIT plutot que de lui presenter un choix sans effet.
    expect($corps)->not->toContain('wire:model="statut"')
        ->and($corps)->toContain(__('Cet article restera un brouillon : un éditeur le relira avant publication.'));
});

/* ------------------------------------------------ auteur */

it('note le redacteur comme auteur de l article qu il cree', function () {
    Livewire::actingAs($this->redacteur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'signe')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', now()->format('Y-m-d'))
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')->set('contenuEn', 'Content')
        ->call('enregistrer');

    expect(Article::where('slug', 'signe')->value('auteur_id'))->toBe($this->redacteur->id);
});

it('ne change pas l auteur quand un editeur corrige un article', function () {
    $article = Article::factory()->create([
        'auteur_id' => $this->redacteur->id,
        'categorie_id' => $this->categorie->id,
    ]);

    // Reprendre un article pour y corriger une virgule ne doit pas en changer
    // la signature.
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('titreFr', 'Titre corrigé')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($article->fresh()->auteur_id)->toBe($this->redacteur->id);
});

it('decrit les quatre roles a cote de la regle qui les applique', function () {
    // La description vient du modele, pas d'une liste ecrite dans le gabarit :
    // une description qui vit loin de sa regle finit par la contredire.
    expect(array_keys(User::descriptionsDesRoles()))
        ->toBe(['administrateur', 'editeur', 'redacteur', 'lecteur']);

    expect($this->redacteur->peutPublier())->toBeFalse()
        ->and($this->redacteur->limiteASesArticles())->toBeTrue()
        ->and($this->editeur->peutPublier())->toBeTrue()
        ->and($this->editeur->limiteASesArticles())->toBeFalse();
});
