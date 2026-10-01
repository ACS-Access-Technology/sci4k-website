<?php

use Illuminate\Support\Env;
use Symfony\Component\Process\Process;

/**
 * Le conteneur refuse de demarrer avec APP_DEBUG actif, quel que soit APP_ENV.
 *
 * Le bloc de controle est extrait du vrai script et execute par bash : c'est
 * lui qui est teste, pas une copie. Le reste du script (root, migrations,
 * serveur) n'est jamais lance ici.
 */
function blocDuControleDeDebogage(): string
{
    $script = (string) file_get_contents(base_path('../tools/demarrer-conteneur.sh'));
    preg_match('/^debogage=.*?^esac$/ms', $script, $bloc);

    return $bloc[0] ?? '';
}

/** Lance le bloc avec APP_DEBUG pose a $valeur (null : variable absente). */
function lancerLeControleDeDebogage(?string $valeur): Process
{
    // Un fichier et non « bash -c » : la ligne de commande de Windows abime
    // les caracteres non ASCII des messages.
    $fichier = tempnam(sys_get_temp_dir(), 'debogage');
    file_put_contents($fichier, "set -euo pipefail\n".blocDuControleDeDebogage()."\necho ACCEPTE\n");

    $environnement = ['APP_DEBUG' => $valeur ?? false];
    $controle = new Process(['bash', $fichier], null, $environnement, null, 30);
    $controle->run();
    unlink($fichier);

    return $controle;
}

/** Ce que Laravel ferait de cette valeur : config/app.php, (bool) env('APP_DEBUG'). */
function laravelActiveLeDebogage(string $valeur): bool
{
    putenv('SCI4K_ESSAI_APP_DEBUG='.$valeur);

    try {
        return (bool) Env::get('SCI4K_ESSAI_APP_DEBUG', false);
    } finally {
        putenv('SCI4K_ESSAI_APP_DEBUG');
    }
}

it('controle APP_DEBUG avant toute autre commande du script, root compris', function () {
    // Les lignes executees seulement : les commentaires citent des commandes.
    $script = collect(file(base_path('../tools/demarrer-conteneur.sh')))
        ->reject(fn ($ligne) => str_starts_with(ltrim($ligne), '#'))
        ->implode('');

    expect(blocDuControleDeDebogage())->not->toBe('', 'le controle de APP_DEBUG a disparu du script');

    $controle = strpos($script, 'debogage=');
    foreach (['cd "$app"', 'id -u', 'exec setpriv', 'php artisan', 'frankenphp run'] as $commande) {
        expect(strpos($script, $commande))->toBeGreaterThan($controle, "« $commande » passerait avant le controle");
    }

    // L'ancien avertissement, limite a APP_ENV=production, ne revient pas.
    expect($script)->not->toContain('"${APP_ENV:-}" = "production"');
});

it('refuse de demarrer avec APP_DEBUG actif', function (string $valeur) {
    $controle = lancerLeControleDeDebogage($valeur);

    expect($controle->getExitCode())->toBe(1)
        ->and($controle->getOutput())->not->toContain('ACCEPTE')
        ->and($controle->getErrorOutput())->toContain('ERREUR : APP_DEBUG=')
        ->toContain('Poser APP_DEBUG=false');
})->with(['true', 'TRUE', '(true)', '1', 'yes', 'on', 'no', 'off', 'debug', ' false', '"false"']);

it('demarre avec APP_DEBUG inactif ou absent', function (?string $valeur) {
    $controle = lancerLeControleDeDebogage($valeur);

    expect($controle->getExitCode())->toBe(0, $controle->getErrorOutput())
        ->and($controle->getOutput())->toContain('ACCEPTE')
        ->and(laravelActiveLeDebogage($valeur ?? ''))->toBeFalse();
})->with([null, 'false', 'FALSE', 'False', '(false)', '0', 'null', 'empty']);

/*
 * Aucune valeur que Laravel lirait comme « vrai » ne passe le controle. La
 * reciproque n'est pas exigee : une valeur inhabituelle que Laravel lirait
 * comme « faux » (« '0' » entre apostrophes) est refusee, et c'est voulu.
 */
it('ne laisse passer aucune valeur que Laravel lirait comme active', function (string $valeur) {
    if (! laravelActiveLeDebogage($valeur)) {
        expect(lancerLeControleDeDebogage($valeur)->getExitCode())->toBeIn([0, 1]);

        return;
    }

    expect(lancerLeControleDeDebogage($valeur)->getExitCode())->toBe(1, "« $valeur » active le debogage et passe");
})->with([
    'true', 'TRUE', '(true)', '1', 'yes', 'on', 'no', 'off', 'debug', ' false', '"false"', "'0'",
    'false', '(false)', '0', 'null', '(null)', 'empty', '(empty)',
]);
