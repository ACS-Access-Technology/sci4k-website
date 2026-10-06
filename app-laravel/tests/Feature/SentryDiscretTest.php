<?php

use App\Models\User;
use App\Support\SentryAvantEnvoi;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Sentry\SentrySdk;
use Sentry\State\Scope;

/*
 * Ce que Sentry a le droit de recevoir.
 *
 * La politique de confidentialite du site promet, en gras, « Aucune adresse IP
 * n'est conservee ». CollecteDeDonneesTest verifie qu'aucune COLONNE n'en
 * stocke ; ce fichier-ci ferme l'autre chemin, celui par lequel la donnee ne
 * serait pas conservee mais simplement envoyee ailleurs.
 *
 * send_default_pii=false n'y suffit pas : le SDK joignait encore le corps de
 * la requete, l'adresse complete et une partie des en-tetes, et PHP les
 * arguments de chaque fonction de la pile. Ce fichier verrouille les reglages ;
 * SentryContenuEnvoyeTest intercepte ce qui part reellement.
 */

it('ne transmet aucune donnee personnelle', function () {
    expect(config('sentry.send_default_pii'))->toBeFalse()
        // Le corps de la requete partait MEME avec send_default_pii=false.
        ->and(config('sentry.max_request_body_size'))->toBe('never')
        ->and(config('sentry.before_send'))->toBe([SentryAvantEnvoi::class, 'filtrer'])
        ->and(config('sentry.before_send_transaction'))->toBe([SentryAvantEnvoi::class, 'filtrer'])
        // Les valeurs des requetes SQL sont ce que le visiteur a saisi.
        ->and(config('sentry.breadcrumbs.sql_bindings'))->toBeFalse()
        ->and(config('sentry.tracing.sql_bindings'))->toBeFalse()
        // Les journaux contiennent les courriels tant que MAIL_MAILER=log.
        ->and(config('sentry.enable_logs'))->toBeFalse()
        ->and(config('sentry.breadcrumbs.logs'))->toBeFalse();
});

/*
 * Le filtre est une methode statique, et non une fonction anonyme : le
 * conteneur met la configuration en cache au demarrage (config:cache), et une
 * fonction anonyme ne se met pas en cache — le demarrage echouerait.
 */
it('garde une configuration que config:cache sait ecrire', function () {
    expect(var_export(config('sentry'), true))->not->toContain('Closure');
});

it('ne garde pas les arguments des fonctions dans les traces de production', function () {
    // PHP les y place par defaut, et un rapport d'erreur les transmet.
    $reglages = parse_ini_file(base_path('../docker/php.ini'));

    expect($reglages['zend.exception_ignore_args'] ?? null)->toBe('1');
});

it('nomme la version deployee', function (array $variables, ?string $attendue) {
    foreach (['SENTRY_RELEASE', 'RAILWAY_GIT_COMMIT_SHA', 'RAILWAY_DEPLOYMENT_ID'] as $nom) {
        putenv($nom);
        unset($_ENV[$nom], $_SERVER[$nom]);
    }
    foreach ($variables as $nom => $valeur) {
        putenv("$nom=$valeur");
        $_ENV[$nom] = $_SERVER[$nom] = $valeur;
    }

    try {
        $configuration = require config_path('sentry.php');
    } finally {
        foreach (array_keys($variables) as $nom) {
            putenv($nom);
            unset($_ENV[$nom], $_SERVER[$nom]);
        }
    }

    expect($configuration['release'])->toBe($attendue);
})->with([
    'posee a la main' => [['SENTRY_RELEASE' => 'v1.2', 'RAILWAY_GIT_COMMIT_SHA' => 'abc123', 'RAILWAY_DEPLOYMENT_ID' => 'dep-1'], 'v1.2'],
    'deploiement GitHub' => [['RAILWAY_GIT_COMMIT_SHA' => 'abc123', 'RAILWAY_DEPLOYMENT_ID' => 'dep-1'], 'abc123'],
    'railway up' => [['RAILWAY_DEPLOYMENT_ID' => 'dep-1'], 'dep-1'],
    'poste de developpement' => [[], null],
]);

it('reste inerte tant qu aucun DSN n est renseigne', function () {
    // Meme patron que la traduction automatique : sans cle, la fonction se
    // tait. Un deploiement qui n'a pas encore de compte Sentry doit servir le
    // site normalement, et non tomber au premier chargement.
    expect(config('sentry.dsn'))->toBeEmpty();

    $this->get('/')->assertOk();
});

it('branche Sentry sur le gestionnaire d exceptions', function () {
    // Sentry ne s'accroche pas tout seul : sans Integration::handles() dans
    // bootstrap/app.php, aucune exception ne lui parvient. Et rien ne le
    // signalerait — un rapport qui n'est pas envoye ne casse rien, il manque,
    // ce qui ne se voit que le jour ou on cherche une panne et qu'il n'y a
    // rien a lire.
    //
    // On inspecte donc les rappels de rapport enregistres sur le gestionnaire.
    $gestionnaire = app(ExceptionHandler::class);
    $rappels = (new ReflectionProperty($gestionnaire, 'reportCallbacks'))->getValue($gestionnaire);

    expect($rappels)->not->toBeEmpty();
});

it('identifie le compte connecte par son seul identifiant', function () {
    $compte = User::factory()->create(['email' => 'editrice@exemple.ci']);

    $this->actingAs($compte);
    event(new Authenticated('web', $compte));

    $utilisateur = null;
    SentrySdk::getCurrentHub()->configureScope(function (Scope $portee) use (&$utilisateur) {
        $utilisateur = $portee->getUser();
    });

    // L'identifiant interne suffit a retrouver qui a rencontre la panne, en
    // interrogeant la base. L'adresse electronique et l'adresse IP, elles,
    // partiraient chez un tiers sans rien apporter de plus — et la seconde
    // contredirait la politique de confidentialite.
    expect($utilisateur?->getId())->toBe((string) $compte->id)
        ->and($utilisateur?->getEmail())->toBeNull()
        ->and($utilisateur?->getIpAddress())->toBeNull()
        ->and($utilisateur?->getUsername())->toBeNull();
});
