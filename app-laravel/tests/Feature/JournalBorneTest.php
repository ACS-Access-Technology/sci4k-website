<?php

use App\Models\ActiviteJournalisee;
use Illuminate\Console\Scheduling\Schedule;

/*
 * Le journal d'activite gardait tout, indefiniment.
 *
 * Une ligne a chaque creation, modification, publication et suppression de
 * contenu, ecrite par le trait JournaliseSesChangements. Rien ne l'effaçait :
 * le meme defaut que la table des visites, en plus lent.
 *
 * IL N'Y A PAS D'AGREGAT ICI, contrairement aux visites. Un journal d'audit
 * repond a « qui a touche a quoi, et quand » : un resume par jour ne
 * repondrait plus a la question. On garde la ligne entiere, ou on ne garde
 * rien.
 */

/** Depose une entree de journal a une date donnee. */
function entreeDeJournal(string $quand, string $intitule = 'Un article'): ActiviteJournalisee
{
    $entree = ActiviteJournalisee::create([
        'user_id' => null,
        'auteur_nom' => 'Une editrice',
        'action' => ActiviteJournalisee::MODIFICATION,
        'sujet_type' => 'App\\Models\\Article',
        'sujet_id' => 1,
        'sujet_intitule' => $intitule,
    ]);

    // created_at est gere par Eloquent : on le repositionne sans repasser par
    // les evenements du modele, qui l'ecraseraient.
    ActiviteJournalisee::withoutTimestamps(
        fn () => $entree->forceFill(['created_at' => $quand])->save()
    );

    return $entree->refresh();
}

it('purge les entrees de plus d un an', function () {
    entreeDeJournal(now()->subDays(400)->format('Y-m-d H:i:s'), 'Trop vieux');
    entreeDeJournal(now()->subDays(300)->format('Y-m-d H:i:s'), 'Encore dans l annee');

    $this->artisan('journal:purger')->assertSuccessful();

    // On vise ces deux entrees et non le total : les migrations qui inserent du
    // contenu passent par des modeles qui se journalisent, si bien qu'une base
    // neuve porte deja quelques lignes. Compter la table entiere ferait
    // dependre ce test de tout ce que les migrations ajouteront un jour.
    expect(ActiviteJournalisee::where('sujet_intitule', 'Trop vieux')->exists())->toBeFalse()
        ->and(ActiviteJournalisee::where('sujet_intitule', 'Encore dans l annee')->exists())->toBeTrue();
});

it('est planifiee chaque jour', function () {
    $planifiee = collect(app(Schedule::class)->events())
        ->first(fn ($evenement) => str_contains((string) $evenement->command, 'journal:purger'));

    expect($planifiee)->not->toBeNull();
});
