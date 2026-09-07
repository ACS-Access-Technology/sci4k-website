<?php

use App\Support\TachesDEntretien;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// La table des visites recevait une ligne par page vue et rien ne l'en
// empechait. Cette commande la borne : elle agrege les jours clos, puis purge
// le detail au-dela de la retention.
//
// A 3 h 10 : le jour a change depuis longtemps, et l'heure creuse evite que la
// purge ne croise le trafic. Une minute decalee plutot que l'heure ronde, ou se
// pressent toutes les taches de tous les heberges.
//
// SANS CRON, RIEN DE CECI NE TOURNE. L'hebergement doit porter la ligne qui
// appelle « php artisan schedule:run » chaque minute — voir docs/MISE_EN_LIGNE.md.
Schedule::command(TachesDEntretien::COMMANDES[0])->dailyAt('03:10');

// Le journal d'activite recevait une ligne par action d'administration et rien
// ne l'effaçait. Une demi-heure apres l'agregation de la frequentation, pour
// que les deux entretiens ne se disputent pas la base.
Schedule::command(TachesDEntretien::COMMANDES[1])->dailyAt('03:40');
