<?php

use App\Support\MoteurDeBase;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;

/**
 * La version de MySQL supportee, et la commande qui la compare au serveur.
 *
 * L'audit avait trouve la CI sur MySQL 8.0 et la production sur 9.4, sans que
 * rien ne le signale. La commande le dit desormais : en echec dans la CI, en
 * avertissement au demarrage du conteneur.
 */
it('reconnait la serie supportee, correctif compris', function (string $version, bool $attendu) {
    expect(MoteurDeBase::mysqlSupporte($version))->toBe($attendu);
})->with([
    'LTS exacte' => ['9.7.2', true],
    'serie seule' => ['9.7', true],
    'suffixe de distribution' => ['9.7.2-log', true],
    'serie voisine au prefixe trompeur' => ['9.70.1', false],
    'ancienne production, Innovation' => ['9.4.0', false],
    'ancienne CI' => ['8.0.46', false],
    'LTS precedente' => ['8.4.11', false],
    'version future' => ['10.0.0', false],
]);

/** Une connexion MySQL simulee, qui rapporte la version voulue. */
function connexionMySql(string $version, bool $maria = false): MySqlConnection
{
    $connexion = Mockery::mock(MySqlConnection::class);
    $connexion->shouldReceive('getServerVersion')->andReturn($version);
    $connexion->shouldReceive('isMaria')->andReturn($maria);
    $connexion->shouldReceive('getDriverTitle')->andReturn($maria ? 'MariaDB' : 'MySQL');

    return $connexion;
}

it('accepte le serveur de la version supportee', function () {
    DB::shouldReceive('connection')->andReturn(connexionMySql('9.7.2'));

    $this->artisan('base:verifier-version')
        ->expectsOutputToContain('version supportée')
        ->assertSuccessful();
});

it('signale un serveur d une autre version', function (string $version) {
    DB::shouldReceive('connection')->andReturn(connexionMySql($version));

    $this->artisan('base:verifier-version')
        ->expectsOutputToContain('le projet supporte et teste MySQL '.MoteurDeBase::MYSQL_SUPPORTE)
        ->assertFailed();
})->with(['9.4.0', '8.0.46']);

it('refuse MariaDB, qui partage le pilote sans etre supporte', function () {
    DB::shouldReceive('connection')->andReturn(connexionMySql('10.11.8', maria: true));

    $this->artisan('base:verifier-version')->expectsOutputToContain('MariaDB')->assertFailed();
});

it('ne compare rien sur SQLite', function () {
    $this->artisan('base:verifier-version')
        ->expectsOutputToContain('aucune version MySQL')
        ->assertSuccessful();
})->skip(fn () => DB::connection()->getDriverName() !== 'sqlite', 'La CI rejoue aussi la suite sur MySQL.');
