<?php

use App\Models\User;
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
 * sentry-laravel transmet par defaut l'adresse IP, les en-tetes et le corps de
 * la requete des qu'on active send_default_pii. Le laisser faire rendrait la
 * page menteuse une seconde fois, par un chemin qu'aucun test ne surveillait.
 */

it('ne transmet aucune donnee personnelle', function () {
    expect(config('sentry.send_default_pii'))->toBeFalse();
});

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
