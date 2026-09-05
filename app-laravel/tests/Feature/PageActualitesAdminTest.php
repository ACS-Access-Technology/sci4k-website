<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ArticleListe;
use App\Livewire\Admin\CategorieEnsemble;
use App\Livewire\Admin\PageActualites;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Cinquieme ecran de la refonte : la page Actualites.
 *
 * Particularite : le module Filtres porte les CATEGORIES, qui n'etaient
 * modifiables nulle part dans le backoffice. L'ecran des referentiels les
 * montrait en lecture avec un renvoi vers la liste des articles, qui ne les
 * edite pas davantage.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    // La fabrique d'article ne pose pas de categorie : la colonne est non
    // nulle et contrainte, chaque test doit donc en fournir une.
    $this->categorie = Categorie::factory()->create();
});

/** Raccourci : un article rattache a la categorie du test. */
function unArticle(array $attributs = []): Article
{
    return Article::factory()->create($attributs + ['categorie_id' => test()->categorie->id]);
}

it('affiche les quatre modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->assertOk()
        ->assertSeeInOrder(['Bannière', 'Filtres', 'Articles', 'Appel']);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete de la banniere et la sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'banniere')
        ->set('entete.titre_fr', 'Le journal de SCI4K')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('actualites.index'))
        ->assertOk()
        ->assertSee('Le journal de SCI4K', false);
});

it('enregistre l appel a l action et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'appel')
        ->set('entete.titre_fr', 'Une question sur le foncier ?')
        ->set('entete.chapo_fr', 'Nos conseillers vous répondent.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'news.cta')->value('titre_fr'))
        ->toBe('Une question sur le foncier ?');

    $this->get(route('actualites.index'))->assertSee('Une question sur le foncier ?', false);
});

/**
 * Le bloc de contact n'affiche ni etiquette ni image : proposer une etiquette
 * aurait ete offrir un champ dont rien ne montre le contenu.
 */
it('ne propose que le titre et l accroche sur le module Appel', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'appel')
        ->html();

    expect($rendu)->toContain('wire:model="entete.titre_fr"')
        ->and($rendu)->toContain('wire:model="entete.chapo_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.etiquette_fr"');
});

it('n affiche pas de formulaire sur les modules sans en-tete', function (string $module) {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', $module)
        ->assertDontSee('wire:model="entete.titre_fr"', false);
})->with(['filtres', 'articles']);

/*
 * Ce test exigeait auparavant que le module « Articles » REFUSE
 * l'enregistrement, faute de section. Il n'en avait pas parce que la grille
 * n'affiche pas d'en-tete — et c'est precisement ce qui laissait le lien de
 * retour et les boutons de partage de la PAGE d'un article figes dans la vue,
 * modifiables nulle part. Le module porte desormais une section, et le test dit
 * ce qui est vrai maintenant.
 */
it('enregistre les textes de la page d un article', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'articles')
        ->set('textes.lien_retour_fr', 'Revenir aux actualités')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'news.article')->first()?->texteBilingue('lien_retour', 'fr'))
        ->toBe('Revenir aux actualités');
});

/* ------------------------------------------------------------------ */
/* Les ecrans embarques                                                */
/* ------------------------------------------------------------------ */

it('embarque l editeur des categories dans le module Filtres', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'filtres')
        ->assertSee('wire:name="admin.categorie-ensemble"', false);
});

it('embarque la liste des articles dans le module Articles', function () {
    unArticle();

    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'articles')
        ->assertSee('wire:name="admin.article-liste"', false);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', $module)
        ->html();

    foreach (['/admin/articles', route('admin.referentiels')] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse);
    }
})->with(['banniere', 'filtres', 'articles', 'appel']);

/* ------------------------------------------------------------------ */
/* L'edition d'un article, sur place                                   */
/* ------------------------------------------------------------------ */

it('ouvre la fiche d un article sur place', function () {
    $article = unArticle();

    Livewire::actingAs($this->admin)
        ->test(ArticleListe::class, ['embarque' => true])
        ->assertSet('formulaireOuvert', null)
        ->call('ouvrirEdition', $article->id)
        ->assertSet('formulaireOuvert', $article->id)
        ->assertSee('wire:name="admin.article-formulaire"', false);
});

it('ouvre la creation d un article sur place', function () {
    Livewire::actingAs($this->admin)
        ->test(ArticleListe::class, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertSet('formulaireOuvert', 'creation');
});

it('refuse un identifiant d article inexistant', function () {
    Livewire::actingAs($this->admin)
        ->test(ArticleListe::class, ['embarque' => true])
        ->call('ouvrirEdition', 99999)
        ->assertNotFound();
});

it('interdit l ouverture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');
    $article = unArticle();

    Livewire::actingAs($lecteur)
        ->test(ArticleListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $article->id)
        ->assertForbidden();
});



/**
 * Enregistre depuis la liste, le formulaire ne redirige pas : il previent la
 * liste, qui se referme. Une redirection ferait quitter l'ecran de page au
 * milieu d'une modification.
 */
it('ne redirige pas quand le formulaire d article est embarque', function () {
    $article = unArticle();

    Livewire::actingAs($this->admin)
        ->test(ArticleFormulaire::class, ['article' => $article, 'embarque' => true])
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('bloc-enregistre');
});

/* ------------------------------------------------------------------ */
/* Les categories : le trou que la refonte comble                      */
/* ------------------------------------------------------------------ */

it('cree une categorie et son slug sans le demander a l editeur', function () {
    Livewire::actingAs($this->admin)->test(CategorieEnsemble::class, ['embarque' => true])
        ->call('ajouter')
        ->set('lignes.neuf-1.nom_fr', 'Marché immobilier')
        ->set('lignes.neuf-1.nom_en', 'Property market')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Categorie::where('nom_fr', 'Marché immobilier')->value('slug'))
        ->toBe('marche-immobilier');
});

/**
 * Deux categories peuvent porter le meme nom : sans suffixe, la seconde
 * heurterait l'index unique et l'enregistrement echouerait sur une erreur SQL
 * brute. Le piege serait de filtrer sur `whereKeyNot(null)` a la creation :
 * « id <> NULL » ne ramene aucune ligne, et le doublon passerait au travers.
 */
it('distingue le slug de deux categories homonymes', function () {
    Categorie::create(['nom_fr' => 'Foncier', 'nom_en' => 'Land']);
    $seconde = Categorie::create(['nom_fr' => 'Foncier', 'nom_en' => 'Land rights']);

    expect(Categorie::where('nom_fr', 'Foncier')->orderBy('id')->pluck('slug')->all())
        ->toBe(['foncier', 'foncier-2'])
        ->and($seconde->slug)->toBe('foncier-2');
});

/** Un slug déjà posé — celui des seeders — n'est jamais réécrit. */
it('ne touche pas a un slug fourni', function () {
    $categorie = Categorie::create(['slug' => 'conseils', 'nom_fr' => 'Nos conseils', 'nom_en' => 'Our advice']);

    $categorie->update(['nom_fr' => 'Autre chose']);

    expect($categorie->fresh()->slug)->toBe('conseils');
});

it('renomme une categorie et le nouveau nom apparait dans le filtre public', function () {
    $categorie = Categorie::factory()->create(['nom_fr' => 'Ancien nom']);

    Livewire::actingAs($this->admin)->test(CategorieEnsemble::class, ['embarque' => true])
        ->set('lignes.'.$categorie->id.'.nom_fr', 'Gestion locative')
        ->set('lignes.'.$categorie->id.'.nom_en', 'Rental management')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('actualites.index'))->assertSee('Gestion locative', false);
});

/**
 * Les articles et les services referencent la categorie par cle etrangere
 * contrainte : laisser passer le retrait ferait echouer l'enregistrement sur
 * une erreur SQL brute, en perdant toutes les autres modifications de l'ecran.
 */
it('refuse de retirer une categorie encore utilisee', function () {
    $article = unArticle();
    $categorie = $article->categorie;

    Livewire::actingAs($this->admin)->test(CategorieEnsemble::class, ['embarque' => true])
        ->call('retirer', $categorie->id)
        ->assertHasErrors('lignes.'.$categorie->id.'.nom_fr');

    expect(Categorie::whereKey($categorie->id)->exists())->toBeTrue();
});

it('retire une categorie vide', function () {
    $categorie = Categorie::factory()->create();

    Livewire::actingAs($this->admin)->test(CategorieEnsemble::class, ['embarque' => true])
        ->call('retirer', $categorie->id)
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Categorie::whereKey($categorie->id)->exists())->toBeFalse();
});

/**
 * Le modele n'a pas de colonne « visible » : le filtre public liste toutes les
 * categories de la table. Une case a decocher sans effet aurait menti.
 */
it('n affiche pas de case Visible sur les categories', function () {
    Categorie::factory()->create();

    $rendu = Livewire::actingAs($this->admin)
        ->test(CategorieEnsemble::class, ['embarque' => true])
        ->html();

    expect($rendu)->not->toContain('.visible"');
});

it('interdit l edition des categories a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(CategorieEnsemble::class, ['embarque' => true])
        ->call('enregistrer')
        ->assertForbidden();
});

/* ------------------------------------------------------------------ */
/* L'ecran complet, rendu de bout en bout                              */
/* ------------------------------------------------------------------ */

/**
 * `Livewire::test()` ne rend les composants imbriques qu'en marque-place :
 * il montre `wire:name`, pas leur contenu. Seule une vraie requete HTTP
 * traverse l'arbre entier — ecran de page, liste embarquee, formulaire — et
 * ferait tomber une erreur de rendu dans l'un des trois.
 */
it('rend l ecran de page et ses composants imbriques', function () {
    $article = unArticle(['titre_fr' => 'Le foncier en 2026']);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.actualites'))
        ->assertOk()
        ->assertSee('Page Actualités', false);

    // Le module Articles, ouvert : la liste doit montrer ses lignes, et non
    // seulement son marque-place.
    Livewire::actingAs($this->admin)->test(ArticleListe::class, ['embarque' => true])
        ->assertSee('Le foncier en 2026', false)
        ->call('ouvrirEdition', $article->id)
        ->assertSee('wire:model="titreFr"', false);
});

/* ------------------------------------------------------------------ */
/* Droits et acces                                                     */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur sur l ecran de page', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageActualites::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PageActualites::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.actualites'), false);
});
