<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');
    Role::findOrCreate('editeur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('rend null quand l article n a aucune couverture', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => null,
    ]);

    expect($article->urlCouverture())->toBeNull();
});

it('sert la couverture des douze articles importes, qui n ont pas de fichier televerse', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    expect($article->urlCouverture())->toContain('images/actualites/article-1.jpg');
});

it('televerse une couverture depuis le formulaire', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'avec-image')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('statut', 'publie')
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')->set('contenuEn', 'Content')
        ->set('couverture', UploadedFile::fake()->image('photo.jpg', 1200, 800))
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'avec-image')->first();

    expect($article->image_source)->toStartWith('storage/actualites/');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $article->image_source));
});

it('remplace la couverture et efface l ancien fichier', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'a-remplacer',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('couverture', UploadedFile::fake()->image('premiere.jpg'))
        ->call('enregistrer');

    $premier = $article->fresh()->image_source;

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article->fresh()])
        ->set('couverture', UploadedFile::fake()->image('seconde.jpg'))
        ->call('enregistrer');

    $second = $article->fresh()->image_source;

    expect($second)->not->toBe($premier);
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $premier));
    Storage::disk('public')->assertExists(str_replace('storage/', '', $second));
});

it('supprime la couverture a la demande', function () {
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'a-effacer',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('couverture', UploadedFile::fake()->image('photo.jpg'))
        ->call('enregistrer');

    $chemin = $article->fresh()->image_source;
    expect($chemin)->not->toBeNull();

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article->fresh()])
        ->call('supprimerCouverture')
        ->call('enregistrer');

    expect($article->fresh()->image_source)->toBeNull();
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $chemin));
});

it('ne supprime jamais un fichier du site statique', function () {
    // Les douze couvertures importees vivent dans frontoffice/, hors du disque
    // public : les effacer detruirait la source du site.
    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'article-importe',
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->call('supprimerCouverture')
        ->call('enregistrer');

    expect($article->fresh()->image_source)->toBeNull();
    expect(file_exists(public_path('images/actualites/article-1.jpg')))->toBeTrue();
});

it('refuse un fichier qui n est pas une image', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('couverture', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['couverture']);
});

it('refuse une image trop lourde', function () {
    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('couverture', UploadedFile::fake()->image('enorme.jpg')->size(5000))
        ->assertHasErrors(['couverture' => 'max']);
});

it('affiche la couverture sur la liste publique', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'publie',
        'slug' => 'visible',
        'image_source' => 'images/actualites/article-1.jpg',
    ]);

    $this->get('/actualites')
        ->assertOk()
        ->assertSee('images/actualites/article-1.jpg')
        ->assertDontSee('defaut.jpg');
});
