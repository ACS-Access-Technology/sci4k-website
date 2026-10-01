<?php

use App\Support\TachesDEntretien;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// L'entretien de nuit : agregation de la frequentation, puis purge du journal
// d'activite. Les commandes et leurs heures vivent dans TachesDEntretien, la
// seule liste qui fasse foi — la route des plateformes sans cron la lit aussi.
//
// QUI APPELLE CE PLANIFICATEUR : dans le conteneur, « artisan schedule:work »,
// lance et relance par tools/demarrer-conteneur.sh. Sur un hebergement
// classique, une ligne de crontab chaque minute — voir docs/MISE_EN_LIGNE.md.
//
// withoutOverlapping(60) : pas de seconde passe tant que la premiere tourne.
// Le verrou expire apres une heure, et non apres les 24 h par defaut : un
// conteneur arrete en pleine passe laisserait sinon un verrou qui ferait
// sauter celle du lendemain.
//
// onOneServer() : si le site tourne un jour sur plusieurs instances, chacune
// avec son planificateur, une seule execute la tache. Le verrou vit dans le
// cache partage (CACHE_STORE=database en production, table cache_locks).
//
// sentryMonitor() : Sentry recoit un signal au debut et a la fin de chaque
// passe, et previent si une passe manque ou echoue. Inerte sans
// SENTRY_LARAVEL_DSN — le paquet desactive alors l'envoi. Les moniteurs se
// creent chez Sentry au premier signal. 10 minutes de marge au demarrage, 30
// de duree maximale : chaque passe dure quelques secondes.
foreach (TachesDEntretien::HEURES as $commande => $heure) {
    Schedule::command($commande)
        ->dailyAt($heure)
        ->withoutOverlapping(60)
        ->onOneServer()
        ->sentryMonitor(TachesDEntretien::moniteur($commande), checkInMargin: 10, maxRuntime: 30);
}
