<?php

use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\ChiffreCle;
use App\Models\CommuneDuBandeau;
use App\Models\EntreeDeMenu;
use App\Models\EtapeProcessus;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\Referentiel;
use App\Models\ReglageDeSection;
use App\Models\RubriqueFaq;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\User;
use App\Models\Valeur;
use Database\Seeders\DemonstrationSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

/**
 * Une installation neuve, de la base vide au site navigable.
 *
 *     migrate  ->  db:seed  ->  compte:creer-administrateur  ->  connexion
 *
 * Avant : `migrate --seed` donnait un compte test@example.com / « password »,
 * actif, sans administrateur, et des pages vides — ni menu, ni filtre, ni
 * service, ni FAQ. Personne ne pouvait administrer, et tout le monde pouvait
 * entrer.
 *
 * La base de test a deja recu les migrations (RefreshDatabase) : c'est l'etat
 * exact d'une installation qui vient de lancer `php artisan migrate`.
 */

/** Le nombre d'entrees d'un fichier d'import. */
function entreesDuFichier(string $fichier): int
{
    return count(json_decode(file_get_contents(database_path("data/$fichier")), true));
}

/** Repond aux questions de la commande. */
function creerAdministrateur(object $test, string $email, string $motDePasse, ?string $confirmation = null)
{
    return $test->artisan('compte:creer-administrateur')
        ->expectsQuestion('Nom', 'Direction SCI4K')
        ->expectsQuestion('Adresse e-mail', $email)
        ->expectsQuestion('Mot de passe', $motDePasse)
        ->expectsQuestion('Confirmez le mot de passe', $confirmation ?? $motDePasse);
}

/* ------------------------------------------------ db:seed */

it('pose la structure du site et aucun compte', function () {
    expect(User::count())->toBe(0);

    $this->seed();

    // Aucun compte, et en particulier plus l'identifiant public d'autrefois.
    expect(User::count())->toBe(0)
        ->and(User::where('email', 'test@example.com')->exists())->toBeFalse();

    expect(Role::pluck('name')->sort()->values()->all())
        ->toBe(['administrateur', 'editeur', 'lecteur', 'redacteur']);

    expect(Categorie::count())->toBe(7)
        ->and(Referentiel::count())->toBe(entreesDuFichier('referentiels.json'))
        ->and(EntreeDeMenu::count())->toBe(entreesDuFichier('menus.json'))
        ->and(Service::count())->toBe(entreesDuFichier('services.json'))
        ->and(RubriqueFaq::count())->toBe(entreesDuFichier('services.json'))
        ->and(QuestionFaq::count())->toBe(entreesDuFichier('questions-faq.json'))
        ->and(ImageDeFond::count())->toBe(entreesDuFichier('images-de-fond.json'))
        ->and(Valeur::count())->toBe(entreesDuFichier('valeurs.json'))
        ->and(EtapeProcessus::count())->toBe(entreesDuFichier('etapes-processus.json'))
        ->and(CommuneDuBandeau::count())->toBe(7);

    $sections = collect(json_decode(file_get_contents(database_path('data/reglages-de-section.json')), true))->pluck('slug');
    expect(ReglageDeSection::whereIn('slug', $sections)->count())->toBe($sections->count());
});

/**
 * Le contenu de la maquette qui affirme quelque chose — clients, equipe,
 * chiffres, partenaires, offres — n'arrive pas par `db:seed`.
 */
it('ne seme aucun contenu de demonstration', function () {
    $this->seed();

    foreach ([Temoignage::class, MembreEquipe::class, Partenaire::class, ChiffreCle::class, Article::class, Bien::class] as $modele) {
        expect($modele::count())->toBe(0, "$modele a ete seme par db:seed");
    }
});

/**
 * Rejouer `db:seed` sur une base en service ne defait rien : ni un renommage,
 * ni une suppression faits depuis l'administration.
 */
it('ne modifie rien sur une base deja remplie', function () {
    $this->seed();

    $service = Service::where('slug', 'foncier')->first();
    $service->update(['nom_fr' => 'Foncier (renommé)']);
    $question = QuestionFaq::first();
    $question->delete();
    EntreeDeMenu::first()->update(['libelle_fr' => 'Libellé corrigé']);
    ReglageDeSection::where('slug', 'home.hero')->update(['titre_fr' => 'Titre corrigé']);
    $totalQuestions = QuestionFaq::count();

    $this->seed();

    expect($service->fresh()->nom_fr)->toBe('Foncier (renommé)')
        ->and(QuestionFaq::find($question->id))->toBeNull()
        ->and(QuestionFaq::count())->toBe($totalQuestions)
        ->and(EntreeDeMenu::where('libelle_fr', 'Libellé corrigé')->exists())->toBeTrue()
        ->and(ReglageDeSection::where('slug', 'home.hero')->value('titre_fr'))->toBe('Titre corrigé');
});

/* ------------------------------------------------ le parcours complet */

it('mene de la base vide a un site administre', function () {
    $this->seed();

    creerAdministrateur($this, 'direction@sci4k.test', 'Un-Mot-De-Passe-Solide-2026!')
        ->expectsOutputToContain('Administrateur créé')
        ->assertSuccessful();

    $admin = User::where('email', 'direction@sci4k.test')->firstOrFail();

    expect($admin->hasRole('administrateur'))->toBeTrue()
        ->and($admin->statut)->toBe(User::ACTIF)
        ->and($admin->getAuthPassword())->not->toBe('Un-Mot-De-Passe-Solide-2026!')
        ->and(Hash::check('Un-Mot-De-Passe-Solide-2026!', $admin->getAuthPassword()))->toBeTrue();

    // Connexion par le vrai formulaire.
    $this->post(route('login.store'), [
        'email' => 'direction@sci4k.test',
        'password' => 'Un-Mot-De-Passe-Solide-2026!',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($admin);

    foreach (['/dashboard', '/admin/configuration', '/admin/menus', '/admin/referentiels', '/admin/utilisateurs', '/admin/pages/services'] as $adresse) {
        $this->get($adresse)->assertOk();
    }
});

it('sert des pages publiques remplies', function () {
    $this->seed();

    $accueil = $this->get('/')->assertOk();
    foreach (EntreeDeMenu::where('menu', 'principal')->where('visible', true)->pluck('libelle_fr') as $libelle) {
        $accueil->assertSee($libelle);
    }

    $this->get('/services')->assertOk()->assertSee(Service::orderBy('ordre')->value('nom_fr'));
    $this->get('/faq')->assertOk()->assertSee(QuestionFaq::orderBy('id')->value('question_fr'));
    $this->get('/biens')->assertOk()->assertSee(Referentiel::where('famille', 'types_de_bien')->value('libelle_fr'));

    foreach (['/presentation', '/actualites', '/contact', '/politique-confidentialite', '/mentions-legales', '/en', '/en/services'] as $adresse) {
        $this->get($adresse)->assertOk();
    }
});

/* ------------------------------------------------ la commande */

it('refuse une confirmation differente', function () {
    $this->seed();

    creerAdministrateur($this, 'a@sci4k.test', 'Un-Mot-De-Passe-Solide-2026!', 'Autre-Chose-2026!')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('refuse une adresse deja prise', function () {
    $this->seed();
    User::factory()->create(['email' => 'pris@sci4k.test']);

    creerAdministrateur($this, 'pris@sci4k.test', 'Un-Mot-De-Passe-Solide-2026!')->assertFailed();

    expect(User::count())->toBe(1);
});

it('refuse une adresse invalide', function () {
    $this->seed();

    creerAdministrateur($this, 'pas-une-adresse', 'Un-Mot-De-Passe-Solide-2026!')->assertFailed();

    expect(User::count())->toBe(0);
});

/**
 * La regle de production — douze caracteres, casse mixte, chiffre, symbole,
 * absent des fuites — s'applique aussi a la ligne de commande.
 */
it('applique la politique de mot de passe de production', function () {
    $this->seed();
    app()->detectEnvironment(fn () => 'production');
    Http::preventStrayRequests();
    Http::fake();

    creerAdministrateur($this, 'faible@sci4k.test', 'motdepasse')->assertFailed();

    expect(User::count())->toBe(0);
});

it('echoue sans terminal plutot que de poser un mot de passe', function () {
    $this->seed();

    $this->artisan('compte:creer-administrateur', ['--nom' => 'X', '--email' => 'x@sci4k.test', '--no-interaction' => true])
        ->assertFailed();

    expect(User::count())->toBe(0);
});

it('exige les roles avant de creer un compte', function () {
    // Pas de db:seed : la table des roles est vide.
    $this->artisan('compte:creer-administrateur')->assertFailed();

    expect(User::count())->toBe(0);
});

/* ------------------------------------------------ la demonstration */

it('seme la demonstration quand on la demande', function () {
    $this->seed(DemonstrationSeeder::class);

    expect(Service::count())->toBeGreaterThan(0)
        ->and(Temoignage::count())->toBe(entreesDuFichier('temoignages.json'))
        ->and(MembreEquipe::count())->toBe(entreesDuFichier('equipe.json'))
        ->and(Article::count())->toBe(12)
        ->and(Bien::where('reference', 'like', 'DEMO-%')->exists())->toBeTrue()
        ->and(User::count())->toBe(0);
});

it('ne seme rien de fictif en production sans confirmation', function () {
    $this->seed();
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--class' => DemonstrationSeeder::class, '--force' => true, '--no-interaction' => true])
        ->assertSuccessful();

    expect(Temoignage::count())->toBe(0)
        ->and(Bien::count())->toBe(0)
        ->and(Article::count())->toBe(0);
});

it('seme en production seulement si on le confirme', function () {
    $this->seed();
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--class' => DemonstrationSeeder::class, '--force' => true])
        ->expectsConfirmation('Environnement de PRODUCTION. Semer du contenu fictif (témoignages, équipe, biens, articles de la maquette) ?', 'yes')
        ->assertSuccessful();

    expect(Temoignage::count())->toBeGreaterThan(0);
});
