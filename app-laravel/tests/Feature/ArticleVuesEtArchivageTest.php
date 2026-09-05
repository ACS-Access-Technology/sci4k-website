<?php

use App\Livewire\Admin\ArticleListe;
use App\Models\ActiviteJournalisee;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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

/* --- compteur de vues --- */

it('part de zero vue', function () {
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);

    expect($article->vues)->toBe(0);
});

it('compte une vue a chaque consultation publique', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'article-consulte',
    ]);

    $this->get('/actualites/article-consulte');
    $this->get('/actualites/article-consulte');
    $this->get('/actualites/article-consulte');

    expect($article->fresh()->vues)->toBe(3);
});

it('ne fait pas passer une lecture pour une modification', function () {
    // Deux consequences d'un compteur trop bavard, et la seconde coute cher.
    //
    // Le journal des activites doit dire ce que font les COMPTES du
    // backoffice ; un `increment` ordinaire declenche `updated`, donc le trait
    // de journalisation, et le journal se remplissait de passages de visiteurs.
    //
    // Et `updated_at` alimente le `lastmod` du plan du site : chaque lecture
    // anonyme annonçait aux moteurs que l'article venait d'etre modifie, a
    // chaque passage, ce qui vide `lastmod` de tout sens.
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'article-lu',
    ]);

    $modifieLe = $article->updated_at;
    $journalAvant = ActiviteJournalisee::count();

    $this->travel(1)->days();
    $this->get('/actualites/article-lu');

    expect($article->fresh()->vues)->toBe(1)
        ->and($article->fresh()->updated_at->timestamp)->toBe($modifieLe->timestamp)
        ->and(ActiviteJournalisee::count())->toBe($journalAvant);
});

it('ne compte pas la liste comme une vue', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
    ]);

    $this->get('/actualites');

    expect($article->fresh()->vues)->toBe(0);
});

it('ne compte pas l apercu depuis l administration', function () {
    // Un editeur qui relit son article ne doit pas gonfler le compteur : le
    // chiffre sert a mesurer l'interet des lecteurs, pas l'activite interne.
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'relu-par-l-editeur',
    ]);

    $this->actingAs($this->editeur)->get('/actualites/relu-par-l-editeur');

    expect($article->fresh()->vues)->toBe(0);
});

/* --- statut archive --- */

it('accepte le statut archive', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'archive',
    ]);

    expect($article->fresh()->statut)->toBe('archive');
});

it('retire du site public un article archive', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'archive',
        'slug' => 'article-archive',
        'titre_fr' => 'Article retiré du site',
    ]);

    $this->get('/actualites')->assertOk()->assertDontSee('Article retiré du site');
    $this->get('/actualites/article-archive')->assertNotFound();
});

it('garde l article archive visible dans l administration', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'archive',
        'titre_fr' => 'Article retiré du site',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->assertSee('Article retiré du site');
});

it('filtre sur le statut archive', function () {
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'archive', 'titre_fr' => 'Rangé']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'publie', 'titre_fr' => 'En ligne']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->set('statut', 'archive')
        ->assertSee('Rangé')
        ->assertDontSee('En ligne');
});

/* --- suppression --- */

it('supprime un article depuis le tableau', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'a-supprimer',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->call('supprimer', $article->id);

    expect(Article::find($article->id))->toBeNull();
});

it('interdit a un lecteur de supprimer', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);

    Livewire::actingAs($lecteur)
        ->test(ArticleListe::class)
        ->call('supprimer', $article->id)
        ->assertForbidden();

    expect(Article::find($article->id))->not->toBeNull();
});

it('efface le fichier de couverture televerse avec l article', function () {
    Storage::fake('public');

    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => 'storage/actualites/photo.jpg',
    ]);
    Storage::disk('public')->put('actualites/photo.jpg', 'contenu');

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->call('supprimer', $article->id);

    Storage::disk('public')->assertMissing('actualites/photo.jpg');
});

it('ne touche jamais aux couvertures du site statique', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->call('supprimer', $article->id);

    expect(file_exists(public_path('images/actualites/article-1.jpg')))->toBeTrue();
});

/* --- indicateurs du tableau --- */

it('compte les articles par statut pour les cartes du haut', function () {
    Article::factory()->count(3)->create(['categorie_id' => $this->categorie->id, 'statut' => 'publie', 'vues' => 10]);
    Article::factory()->count(2)->create(['categorie_id' => $this->categorie->id, 'statut' => 'brouillon']);
    Article::factory()->create(['categorie_id' => $this->categorie->id, 'statut' => 'archive']);

    Livewire::actingAs($this->editeur)
        ->test(ArticleListe::class)
        ->assertViewHas('indicateurs', fn ($i) => $i['publies'] === 3
            && $i['brouillons'] === 2
            && $i['archives'] === 1
            && $i['vues'] === 30);
});
