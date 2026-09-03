<?php

use App\Livewire\Admin\ArticleListe;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
    Role::findOrCreate('lecteur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('liste les articles, brouillons compris', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Publié', 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Brouillon', 'statut' => 'brouillon']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->assertSee('Publié')
        ->assertSee('Brouillon');
});

it('filtre par recherche sur le titre', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Sécuriser un terrain']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Marché immobilier']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('recherche', 'terrain')
        ->assertSee('Sécuriser un terrain')
        ->assertDontSee('Marché immobilier');
});

it('cherche aussi dans le titre anglais', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Sécuriser un terrain',
        'titre_en' => 'Securing a plot',
    ]);
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Marché immobilier',
        'titre_en' => 'Real estate market',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('recherche', 'plot')
        ->assertSee('Sécuriser un terrain')
        ->assertDontSee('Marché immobilier');
});

it('filtre par statut', function () {
    // Les titres ne reprennent pas les mots « Publié » et « Brouillon » : ces
    // libelles figurent en permanence dans les options du filtre, et un
    // assertDontSee dessus passerait pour un echec du filtrage.
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Article en ligne', 'statut' => 'publie']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Article en attente', 'statut' => 'brouillon']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('statut', 'brouillon')
        ->assertSee('Article en attente')
        ->assertDontSee('Article en ligne');
});

it('filtre par categorie', function () {
    $autre = Categorie::create(['slug' => 'marche', 'nom_fr' => 'Marché', 'nom_en' => 'Market', 'ordre' => 2]);

    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Sur le foncier']);
    Article::factory()->create(['categorie_id' => $autre->id, 'titre_fr' => 'Sur le marché']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('categorieId', (string) $this->categorie->id)
        ->assertSee('Sur le foncier')
        ->assertDontSee('Sur le marché');
});

it('affiche les titres dans la langue de l interface', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'titre_fr' => 'Sécuriser un terrain',
        'titre_en' => 'Securing a plot',
    ]);

    app()->setLocale('en');

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->assertSee('Securing a plot')
        ->assertSee('Land &amp; Title', false)   // la categorie suit aussi
        ->assertDontSee('Sécuriser un terrain');
});

/*
 * Les tests ci-dessous frappent la route reelle, et non le composant : c'est le
 * seul moyen de verifier le gabarit et les middlewares, que Livewire::test()
 * court-circuite. Cette liste expose les brouillons, donc du contenu non
 * publie — l'endroit ou le controle d'acces merite d'etre teste pour de vrai.
 *
 * L'adresse a change : la liste n'a plus d'ecran a elle, elle est rendue par
 * « Pages du site → Actualités ».
 */

it('refuse la page a un visiteur non connecte', function () {
    $this->get('/admin/pages/actualites')->assertRedirect('/login');
});

it('sert la liste dans le gabarit de l administration', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Un article']);

    $this->actingAs($this->editeur)
        ->get('/admin/pages/actualites')
        ->assertOk()
        ->assertSee('sidebar', false);   // repere du gabarit d'administration
});

it('laisse un lecteur consulter la liste', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Article::factory()->create(['categorie_id' => $this->categorie->id, 'titre_fr' => 'Un article']);

    Livewire::actingAs($lecteur)
        ->test(ArticleListe::class)
        ->assertOk()
        ->assertSee('Un article');
});

/** L'ancienne adresse ne repond plus. */
it('ne sert plus l ancien ecran des articles', function () {
    $this->actingAs($this->editeur)->get('/admin/articles')->assertNotFound();
});
