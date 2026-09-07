<?php

use App\Models\ActiviteJournalisee;

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
