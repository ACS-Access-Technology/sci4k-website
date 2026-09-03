<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

/** Remplit le formulaire d'un article valide, pour n'ecrire qu'une fois les dix champs. */
function formulaireRempli($composant, array $remplacements = [])
{
    $valeurs = array_merge([
        'slug' => 'nouvel-article',
        'categorieId' => (string) test()->categorie->id,
        'datePublication' => '2026-08-23',
        'statut' => 'publie',
        'titreFr' => 'Titre français',
        'titreEn' => 'English title',
        'resumeFr' => 'Résumé français',
        'resumeEn' => 'English summary',
        'contenuFr' => 'Contenu français',
        'contenuEn' => 'English content',
    ], $remplacements);

    foreach ($valeurs as $champ => $valeur) {
        $composant->set($champ, $valeur);
    }

    return $composant;
}

it('cree un article dans les deux langues', function () {
    formulaireRempli(Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class))
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'nouvel-article')->first();

    expect($article)->not->toBeNull();
    expect($article->titre('fr'))->toBe('Titre français');
    expect($article->titre('en'))->toBe('English title');
    expect($article->resume('en'))->toBe('English summary');
    expect($article->contenu('en'))->toBe('English content');
});

it('modifie un article existant sans en creer un second', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'a-modifier',
        'titre_fr' => 'Ancien titre',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('titreFr', 'Nouveau titre')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Article::count())->toBe(1);
    expect($article->fresh()->titre_fr)->toBe('Nouveau titre');
});

it('charge les valeurs existantes a l edition', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'deja-ecrit',
        'titre_fr' => 'Titre déjà écrit',
        'titre_en' => 'Already written',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->assertSet('slug', 'deja-ecrit')
        ->assertSet('titreFr', 'Titre déjà écrit')
        ->assertSet('titreEn', 'Already written');
});

it('refuse un enregistrement sans titre anglais', function () {
    formulaireRempli(
        Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class),
        ['slug' => 'incomplet', 'titreEn' => '']
    )
        ->call('enregistrer')
        ->assertHasErrors(['titreEn' => 'required']);

    expect(Article::count())->toBe(0);
});

it('refuse un slug deja pris', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'deja-pris']);

    formulaireRempli(
        Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class),
        ['slug' => 'deja-pris']
    )
        ->call('enregistrer')
        ->assertHasErrors(['slug' => 'unique']);
});

it('laisse un article garder son propre slug a l edition', function () {
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id, 'slug' => 'son-slug']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('titreFr', 'Titre revu')
        ->call('enregistrer')
        ->assertHasNoErrors();
});

it('refuse un slug mal forme', function () {
    formulaireRempli(
        Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class),
        ['slug' => 'Mon Article Accentué']
    )
        ->call('enregistrer')
        ->assertHasErrors(['slug']);

    expect(Article::count())->toBe(0);
});

it('ouvre l onglet de la langue de l interface', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->assertSet('langueActive', 'fr');

    app()->setLocale('en');

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->assertSet('langueActive', 'en');
});

/*
 * Le formulaire n'a plus d'adresse a lui : il s'ouvre DANS la liste, elle-meme
 * rendue par « Pages du site → Actualités ». Le controle de role se verifie
 * donc sur l'action, la ou il a toujours ete pose — la route ne protegeait que
 * l'ecran, et Livewire ne rejoue pas ses middlewares sur /livewire/update.
 */
it('interdit a un lecteur d enregistrer', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)
        ->test(ArticleFormulaire::class)
        ->call('enregistrer')
        ->assertForbidden();
});

it('laisse un editeur ouvrir le formulaire', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->assertOk()
        ->assertSee(__('Enregistrer'));
});

it('depuis le tableau, chaque titre ouvre son edition sur place', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'article-editable',
        'titre_fr' => 'Article éditable',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(App\Livewire\Admin\ArticleListe::class)
        ->assertOk()
        ->assertSee('ouvrirEdition('.$article->id.')', false);
});

/*
 * Le test qui justifie le lot : un article ecrit dans l'administration doit
 * apparaitre sur le site public, dans les deux langues. Les autres s'arretent
 * a la base de donnees ; celui-ci parcourt la chaine entiere.
 */
it('publie sur le site public un article cree dans l administration', function () {
    formulaireRempli(
        Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class),
        ['slug' => 'ecrit-au-backoffice', 'statut' => 'publie']
    )
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get('/actualites')->assertOk()->assertSee('Titre français');
    $this->get('/actualites/ecrit-au-backoffice')
        ->assertOk()
        ->assertSee('Titre français')
        ->assertSee('Contenu français');

    $this->get('/langue/en');
    $this->get('/actualites/ecrit-au-backoffice')
        ->assertOk()
        ->assertSee('English title')
        ->assertSee('English content');
});

it('garde hors du site public un article laisse en brouillon', function () {
    formulaireRempli(
        Livewire::actingAs($this->editeur)->test(ArticleFormulaire::class),
        ['slug' => 'pas-encore-pret', 'statut' => 'brouillon']
    )
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get('/actualites')->assertOk()->assertDontSee('Titre français');
    $this->get('/actualites/pas-encore-pret')->assertNotFound();
});
