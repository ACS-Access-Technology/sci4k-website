<?php

/*
 * Remplissage automatique de la langue manquante, a l'enregistrement.
 *
 * La regle arbitree avec le client tient en une phrase : on ne traduit QUE ce
 * qui est vide. C'est ce qui protege la traduction anglaise humaine des douze
 * articles repris du site, dont la recuperation a coute une investigation
 * entiere. Les tests ci-dessous verrouillent ce point autant que le mecanisme.
 */

use App\Livewire\Admin\ArticleFormulaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use App\Services\Traduction\Traducteur;
use App\Services\Traduction\TraducteurDeepL;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

/** Un traducteur de test, qui prefixe au lieu d'appeler le reseau. */
function traducteurFactice(bool $disponible = true): void
{
    app()->bind(Traducteur::class, fn () => new class($disponible) implements Traducteur
    {
        public function __construct(private bool $disponible) {}

        public function disponible(): bool
        {
            return $this->disponible;
        }

        public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
        {
            if (! $this->disponible) {
                return null;
            }

            return array_map(fn ($t) => '['.$vers.'] '.$t, $textes);
        }
    });
}

it('remplit l anglais manquant depuis le francais', function () {
    traducteurFactice();

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'seulement-francais')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('statut', 'publie')
        ->set('titreFr', 'Sécuriser un terrain')
        ->set('resumeFr', 'Un résumé.')
        ->set('contenuFr', "Premier.\n\nSecond.")
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'seulement-francais')->first();

    expect($article->titre_en)->toBe('[en] Sécuriser un terrain');
    expect($article->resume_en)->toBe('[en] Un résumé.');
    expect($article->contenu_en)->toBe("[en] Premier.\n\n[en] Second.");
});

it('remplit le francais manquant depuis l anglais', function () {
    traducteurFactice();

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'seulement-anglais')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('titreEn', 'Securing a plot')
        ->set('resumeEn', 'A summary.')
        ->set('contenuEn', 'Some content.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'seulement-anglais')->first();

    expect($article->titre_fr)->toBe('[fr] Securing a plot');
});

it('n ecrase jamais un texte deja saisi', function () {
    traducteurFactice();

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'les-deux-langues')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('titreFr', 'Titre écrit à la main')
        ->set('titreEn', 'Hand-written title')
        ->set('resumeFr', 'Résumé.')->set('resumeEn', 'Summary.')
        ->set('contenuFr', 'Contenu.')->set('contenuEn', 'Content.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'les-deux-langues')->first();

    expect($article->titre_en)->toBe('Hand-written title');
    expect($article->contenu_en)->toBe('Content.');
});

it('preserve la traduction humaine des articles importes a la modification', function () {
    traducteurFactice();

    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'acd-securiser-terrain',
        'titre_fr' => 'ACD, titre foncier',
        'titre_en' => 'ACD, land title',
        'contenu_en' => 'A genuine human translation.',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class, ['article' => $article])
        ->set('titreFr', 'ACD, titre foncier — mis à jour')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($article->fresh()->titre_en)->toBe('ACD, land title');
    expect($article->fresh()->contenu_en)->toBe('A genuine human translation.');
});

it('ne remplit qu un champ vide, laissant les autres intacts', function () {
    traducteurFactice();

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'partiel')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')   // seul le contenu anglais manque
        ->call('enregistrer')
        ->assertHasNoErrors();

    $article = Article::where('slug', 'partiel')->first();

    expect($article->titre_en)->toBe('Title');
    expect($article->contenu_en)->toBe('[en] Contenu');
});

it('refuse l enregistrement et le dit quand la traduction est indisponible', function () {
    traducteurFactice(disponible: false);

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'sans-service')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('titreFr', 'Titre')
        ->set('resumeFr', 'Résumé')
        ->set('contenuFr', 'Contenu')
        ->call('enregistrer')
        ->assertHasErrors(['titreEn']);

    expect(Article::count())->toBe(0);
});

it('n appelle pas le service quand rien ne manque', function () {
    Http::fake();
    app()->bind(Traducteur::class, fn () => new TraducteurDeepL('cle-de-test:fx'));

    Livewire::actingAs($this->editeur)
        ->test(ArticleFormulaire::class)
        ->set('slug', 'rien-a-traduire')
        ->set('categorieId', (string) $this->categorie->id)
        ->set('datePublication', '2026-08-25')
        ->set('titreFr', 'Titre')->set('titreEn', 'Title')
        ->set('resumeFr', 'Résumé')->set('resumeEn', 'Summary')
        ->set('contenuFr', 'Contenu')->set('contenuEn', 'Content')
        ->call('enregistrer')
        ->assertHasNoErrors();

    Http::assertNothingSent();
});

/* --- le client DeepL lui-meme --- */

it('vise le point d acces gratuit quand la cle porte le suffixe fx', function () {
    Http::fake(['api-free.deepl.com/*' => Http::response(['translations' => [['text' => 'Hello']]])]);

    $resultat = (new TraducteurDeepL('abc:fx'))->traduire(['Bonjour'], 'en', 'fr');

    expect($resultat)->toBe(['Hello']);
    Http::assertSent(fn ($r) => str_contains($r->url(), 'api-free.deepl.com'));
});

it('vise le point d acces payant quand la cle n a pas le suffixe', function () {
    Http::fake(['api.deepl.com/*' => Http::response(['translations' => [['text' => 'Hello']]])]);

    (new TraducteurDeepL('abc'))->traduire(['Bonjour'], 'en', 'fr');

    Http::assertSent(fn ($r) => str_contains($r->url(), 'api.deepl.com')
        && ! str_contains($r->url(), 'api-free'));
});

it('se tait quand aucune cle n est configuree', function () {
    Http::fake();

    $traducteur = new TraducteurDeepL(null);

    expect($traducteur->disponible())->toBeFalse();
    expect($traducteur->traduire(['Bonjour'], 'en'))->toBeNull();
    Http::assertNothingSent();
});

it('rend null plutot qu une traduction partielle', function () {
    // Deux textes envoyes, un seul revenu : l'appariement serait decale, et un
    // resume se retrouverait dans le titre.
    Http::fake(['*' => Http::response(['translations' => [['text' => 'Only one']]])]);

    expect((new TraducteurDeepL('abc:fx'))->traduire(['Un', 'Deux'], 'en'))->toBeNull();
});

it('rend null quand le service repond en erreur', function () {
    Http::fake(['*' => Http::response(['message' => 'quota exceeded'], 456)]);

    expect((new TraducteurDeepL('abc:fx'))->traduire(['Bonjour'], 'en'))->toBeNull();
});
