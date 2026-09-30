<?php

use App\Models\ActiviteJournalisee;
use App\Models\Parametre;
use App\Models\Visite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * Le declencheur des taches d'entretien, pour les plateformes sans cron.
 *
 * Sur un hebergement ordinaire, une ligne de crontab appelle « artisan
 * schedule:run » chaque minute. Une plateforme sans acces en ligne de commande
 * — Vercel — n'appelle que des adresses HTTP : il faut donc une porte, et une
 * porte se garde.
 *
 * ELLE REPOND 404 QUAND LE JETON MANQUE, et non 401. Un 401 confirmerait a qui
 * tatonne que l'adresse existe et qu'il ne manque qu'un secret ; un 404 ne dit
 * rien. La route n'est de toute façon annoncee nulle part.
 */

it('refuse une visite sans jeton, sans meme admettre qu elle existe', function () {
    config(['app.cron_secret' => 'un-secret']);

    $this->get('/_taches-planifiees')->assertNotFound();
});

it('refuse un jeton qui ne correspond pas', function () {
    config(['app.cron_secret' => 'un-secret']);

    $this->withHeader('Authorization', 'Bearer mauvais')
        ->get('/_taches-planifiees')
        ->assertNotFound();
});

it('reste fermee tant qu aucun secret n est configure', function () {
    // Sans secret, la porte n'existe pas : autrement un deploiement qui a
    // oublie de le poser ouvrirait l'entretien a tout le monde.
    config(['app.cron_secret' => null]);

    $this->withHeader('Authorization', 'Bearer ')
        ->get('/_taches-planifiees')
        ->assertNotFound();
});

it('execute les taches quand le jeton est bon', function () {
    config(['app.cron_secret' => 'un-secret']);

    $vieille = ActiviteJournalisee::create([
        'auteur_nom' => 'Une editrice', 'action' => ActiviteJournalisee::MODIFICATION,
        'sujet_type' => 'App\\Models\\Article', 'sujet_id' => 1, 'sujet_intitule' => 'Trop vieux',
    ]);
    ActiviteJournalisee::withoutTimestamps(
        fn () => $vieille->forceFill(['created_at' => now()->subDays(400)])->save()
    );

    $this->withHeader('Authorization', 'Bearer un-secret')
        ->get('/_taches-planifiees')
        ->assertOk();

    expect(ActiviteJournalisee::where('sujet_intitule', 'Trop vieux')->exists())->toBeFalse();
});

/* ------------------------------------------------ protections */

/**
 * Fait echouer l'agregation de la frequentation, sans toucher au schema : la
 * lecture des visites leve une exception, que la commande attrape et convertit
 * en code d'echec. Aucune table supprimee — ce qui, sous MySQL, validerait la
 * transaction du test et fausserait les suivants.
 */
function faireEchouerLAgregation(): void
{
    DB::connection()->beforeExecuting(function (string $requete) {
        if (str_starts_with(strtolower(ltrim($requete)), 'select') && str_contains($requete, 'visites')) {
            throw new RuntimeException('Lecture des visites impossible (essai).');
        }
    });
}

function journalPerime(): ActiviteJournalisee
{
    $vieille = ActiviteJournalisee::create([
        'auteur_nom' => 'Une editrice', 'action' => ActiviteJournalisee::MODIFICATION,
        'sujet_type' => 'App\Models\Article', 'sujet_id' => 1, 'sujet_intitule' => 'Trop vieux',
    ]);
    ActiviteJournalisee::withoutTimestamps(
        fn () => $vieille->forceFill(['created_at' => now()->subDays(400)])->save()
    );

    return $vieille;
}

it('repond 500 quand une commande echoue, et fait quand meme tourner les autres', function () {
    config(['app.cron_secret' => 'un-secret']);
    journalPerime();
    faireEchouerLAgregation();

    $this->withHeader('Authorization', 'Bearer un-secret')
        ->get('/_taches-planifiees')
        ->assertStatus(500)
        ->assertJson(['echecs' => ['frequentation:agreger']]);

    // La purge du journal ne depend pas de la frequentation : elle a tourne.
    expect(ActiviteJournalisee::where('sujet_intitule', 'Trop vieux')->exists())->toBeFalse();
});

it('ne compte pas l appel du declencheur comme une visite', function () {
    config(['app.cron_secret' => 'un-secret']);

    $this->withHeader('Authorization', 'Bearer un-secret')->get('/_taches-planifiees')->assertOk();

    expect(Visite::count())->toBe(0);
});

it('entretient le site meme quand il est ferme au public', function () {
    config(['app.cron_secret' => 'un-secret']);
    Parametre::poser('mode_maintenance', '1');
    journalPerime();

    // Le site public, lui, est bien ferme.
    $this->get('/')->assertStatus(503);

    $this->withHeader('Authorization', 'Bearer un-secret')->get('/_taches-planifiees')->assertOk();

    expect(ActiviteJournalisee::where('sujet_intitule', 'Trop vieux')->exists())->toBeFalse();
});

it('ne relance pas l entretien tant qu une passe tourne', function () {
    config(['app.cron_secret' => 'un-secret']);
    journalPerime();

    $verrou = Cache::lock('taches-d-entretien', 60);
    expect($verrou->get())->toBeTrue();

    try {
        $this->withHeader('Authorization', 'Bearer un-secret')
            ->get('/_taches-planifiees')
            ->assertStatus(409);
    } finally {
        $verrou->release();
    }

    // Rien n'a tourne pendant que le verrou etait pris.
    expect(ActiviteJournalisee::where('sujet_intitule', 'Trop vieux')->exists())->toBeTrue();

    // Et le verrou rendu, la passe suivante passe.
    $this->withHeader('Authorization', 'Bearer un-secret')->get('/_taches-planifiees')->assertOk();
});
