<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ArticleListe;
use App\Livewire\Admin\RechercheGlobale;
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
    $this->actingAs($this->redacteur)->get('/admin/pages/actualites')->assertOk();
});

it('lui refuse les ecrans qui ne sont pas les articles', function () {
    // Son role porte sur les articles, et rien d'autre. Les ecrans par type de
    // contenu ayant ete retires, la garde se lit sur l'ACTION des composants —
    // la ou elle a toujours ete posee.
    $this->actingAs($this->redacteur)->get('/admin/configuration')->assertForbidden();

    Livewire::actingAs($this->redacteur)
        ->test(App\Livewire\Admin\ServiceFormulaire::class)
        ->call('enregistrer')
        ->assertForbidden();
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

    Livewire::actingAs($this->redacteur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->assertForbidden();
});

it('laisse un redacteur ouvrir son propre article', function () {
    $article = Article::factory()->create(['auteur_id' => $this->redacteur->id, 'categorie_id' => $this->categorie->id]);

    Livewire::actingAs($this->redacteur)
        ->test(ArticleFormulaire::class, ['article' => $article])
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

/* -------------------------------------------- le role est ATTEIGNABLE */

/*
 * Tout ce qui precede decrit un role que personne ne pouvait exercer.
 *
 * ArticleListe excluait le redacteur de `peutEcrire()`, et la refonte a fait
 * de cette liste le SEUL chemin vers le formulaire — les routes qui y menaient
 * directement ont ete retirees. Les tests ci-dessus montaient ArticleFormulaire
 * a la main : ils ne pouvaient donc pas voir que la porte etait murée.
 */
it('laisse un redacteur ouvrir le formulaire de creation depuis la liste', function () {
    Livewire::actingAs($this->redacteur)
        ->test(ArticleListe::class)
        ->call('ouvrirCreation')
        ->assertSet('formulaireOuvert', 'creation');
});

it('laisse un redacteur ouvrir son article depuis la liste', function () {
    $sien = Article::factory()->create([
        'auteur_id' => $this->redacteur->id,
        'categorie_id' => $this->categorie->id,
    ]);

    Livewire::actingAs($this->redacteur)
        ->test(ArticleListe::class)
        ->call('ouvrirEdition', $sien->id)
        ->assertSet('formulaireOuvert', $sien->id);
});

it("refuse d'ouvrir depuis la liste l'article d'un autre", function () {
    $autre = Article::factory()->create([
        'auteur_id' => $this->autreRedacteur->id,
        'categorie_id' => $this->categorie->id,
    ]);

    // ArticleFormulaire le refuse deja a son montage ; la liste le dit avant,
    // pour ne pas ouvrir un panneau qui ne rendrait qu'une page 403.
    Livewire::actingAs($this->redacteur)
        ->test(ArticleListe::class)
        ->call('ouvrirEdition', $autre->id)
        ->assertForbidden();
});

it('ne laisse pas un redacteur supprimer un article, meme le sien', function () {
    // Ecrire n'est pas effacer : la suppression est definitive — les articles
    // n'ont pas de corbeille — et elle reste aux editeurs.
    $sien = Article::factory()->create([
        'auteur_id' => $this->redacteur->id,
        'categorie_id' => $this->categorie->id,
    ]);

    Livewire::actingAs($this->redacteur)
        ->test(ArticleListe::class)
        ->call('supprimer', $sien->id)
        ->assertForbidden();

    expect(Article::whereKey($sien->id)->exists())->toBeTrue();
});

it('ne compte pas les articles des autres dans les indicateurs', function () {
    Article::factory()->create([
        'auteur_id' => $this->autreRedacteur->id,
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'vues' => 500,
    ]);
    Article::factory()->create([
        'auteur_id' => $this->redacteur->id,
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
        'vues' => 7,
    ]);

    $indicateurs = Livewire::actingAs($this->redacteur)
        ->test(ArticleListe::class)
        ->viewData('indicateurs');

    // Les cartes du haut de liste comptaient TOUS les articles, y compris ceux
    // que le redacteur n'a pas le droit de voir juste en dessous.
    expect($indicateurs['publies'])->toBe(0)
        ->and($indicateurs['brouillons'])->toBe(1)
        ->and($indicateurs['vues'])->toBe(7);
});

it('ne trouve pas les brouillons des autres dans la recherche globale', function () {
    Article::factory()->create([
        'auteur_id' => $this->autreRedacteur->id,
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Brouillon confidentiel',
        'statut' => 'brouillon',
    ]);
    Article::factory()->create([
        'auteur_id' => $this->redacteur->id,
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Brouillon personnel',
        'statut' => 'brouillon',
    ]);

    Livewire::actingAs($this->redacteur)
        ->test(RechercheGlobale::class)
        ->set('terme', 'Brouillon')
        ->assertSee('Brouillon personnel')
        ->assertDontSee('Brouillon confidentiel');
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
