<?php

use App\Models\Parametre;
use App\Support\Sauvegarde\DumpSql;
use App\Support\Sauvegarde\FluxChiffre;
use App\Support\Sauvegarde\SauvegardeExterne;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * La sauvegarde externe, de bout en bout, sur la VRAIE base : l'export est
 * propre a MySQL (SHOW CREATE TABLE, instantane coherent). Ces tests tournent
 * donc dans la passe MySQL de la CI et sont ignores sur SQLite.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Export propre a MySQL : couvert par la passe MySQL de la CI.');
    }

    Storage::fake('sauvegardes');
    $this->cle = base64_encode(random_bytes(32));
    $this->public = sys_get_temp_dir().'/sci4k-public-'.bin2hex(random_bytes(4));
    mkdir($this->public.'/biens', 0777, true);
    file_put_contents($this->public.'/biens/villa.jpg', 'photo-de-la-villa');
});

afterEach(function () {
    if (isset($this->public) && is_dir($this->public)) {
        foreach (glob($this->public.'/biens/*') as $f) {
            unlink($f);
        }
        rmdir($this->public.'/biens');
        rmdir($this->public);
    }
});

function sauvegarde(object $t, ?string $ping = null, ?string $cle = null): SauvegardeExterne
{
    return new SauvegardeExterne(
        pdo: DB::connection()->getPdo(),
        disque: Storage::disk('sauvegardes'),
        cle: $cle ?? $t->cle,
        dossierFichiers: $t->public,
        prefixe: 'essai',
        urlSurveillance: $ping,
    );
}

it('depose une base et des fichiers chiffres, relus avec succes', function () {
    Parametre::poser('nom_du_site', 'SCI4K — essai');

    $r = sauvegarde($this)->executer(true, CarbonImmutable::parse('2026-10-13 03:20:00', 'UTC')); // un mardi

    $disque = Storage::disk('sauvegardes');
    $base = 'essai/quotidien/2026-10-13/'.$r['base'];

    expect($r['verifie'])->toBeTrue()
        ->and($r['tables'])->toBeGreaterThan(10)
        ->and($disque->exists($base))->toBeTrue()
        ->and($disque->exists('essai/quotidien/2026-10-13/'.$r['fichiers']))->toBeTrue()
        ->and($disque->exists('essai/quotidien/2026-10-13/manifeste.json'))->toBeTrue()
        // pas un dimanche : pas de copie hebdomadaire
        ->and($disque->allFiles('essai/hebdomadaire'))->toBeEmpty();

    // Ce qui est depose est illisible sans la cle...
    expect($disque->get($base))->not->toContain('SCI4K — essai')->not->toContain('CREATE TABLE')->not->toContain('parametres');

    // ... et, avec elle, c'est l'export complet.
    $chiffre = tempnam(sys_get_temp_dir(), 'c');
    $clair = tempnam(sys_get_temp_dir(), 'g');
    file_put_contents($chiffre, $disque->get($base));
    FluxChiffre::dechiffrer($chiffre, $clair, FluxChiffre::cle($this->cle));
    $sql = gzdecode(file_get_contents($clair));
    unlink($chiffre);
    unlink($clair);

    expect($sql)->toContain('CREATE TABLE `parametres`')
        ->toContain('SCI4K — essai')
        ->toContain('-- lignes: parametres=')
        ->toEndWith(DumpSql::FIN."\n");
});

it('ajoute une copie hebdomadaire le dimanche', function () {
    sauvegarde($this)->executer(false, CarbonImmutable::parse('2026-10-11 03:20:00', 'UTC')); // un dimanche

    expect(Storage::disk('sauvegardes')->allFiles('essai/hebdomadaire'))->toHaveCount(2);
});

it('compte les lignes de chaque table dans l export', function () {
    $comptes = (new DumpSql(DB::connection()->getPdo()))->ecrire($fichier = tempnam(sys_get_temp_dir(), 'd'));

    $sql = gzdecode(file_get_contents($fichier));
    unlink($fichier);

    expect($comptes)->toHaveKey('parametres')
        ->and($sql)->toContain('-- lignes: parametres='.$comptes['parametres']);
});

it('previent le service de surveillance du debut, puis de la reussite', function () {
    Http::fake();

    sauvegarde($this, 'https://ping.exemple.test/abc')->executer(false);

    Http::assertSent(fn ($r) => $r->url() === 'https://ping.exemple.test/abc/start');
    Http::assertSent(fn ($r) => $r->url() === 'https://ping.exemple.test/abc');
    Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/fail'));
});

it('signale l echec, sans jamais ecrire la cle dans le journal', function () {
    Http::fake();
    Log::spy();

    // Un volume vide : l'archive des fichiers echoue, donc la sauvegarde aussi.
    $vide = sys_get_temp_dir().'/sci4k-vide-'.bin2hex(random_bytes(4));
    mkdir($vide);

    $echec = new SauvegardeExterne(
        pdo: DB::connection()->getPdo(),
        disque: Storage::disk('sauvegardes'),
        cle: $this->cle,
        dossierFichiers: $vide,
        prefixe: 'essai',
        urlSurveillance: 'https://ping.exemple.test/abc',
    );

    expect(fn () => $echec->executer(true))->toThrow(RuntimeException::class, 'volume vide');
    rmdir($vide);

    Http::assertSent(fn ($r) => $r->url() === 'https://ping.exemple.test/abc/fail');
    Log::shouldHaveReceived('error')->withArgs(
        fn ($message, $contexte) => str_contains($message, 'ECHOUEE') && ! str_contains(json_encode($contexte), $this->cle),
    );
    expect(Storage::disk('sauvegardes')->allFiles())->toBeEmpty('Un echec ne doit rien laisser de depose a moitie.');
});

it('n est pas planifiee tant que la sauvegarde n est pas activee', function () {
    $commandes = collect(app(Schedule::class)->events())
        ->map(fn ($e) => $e->command)
        ->filter(fn ($c) => str_contains((string) $c, 'sauvegarde:externe'));

    expect(config('sauvegarde.active'))->toBeFalse()
        ->and($commandes)->toBeEmpty();
});

it('refuse de demarrer sans cle ni bucket', function () {
    config(['sauvegarde.cle' => null, 'sauvegarde.bucket' => null]);

    SauvegardeExterne::depuisLaConfiguration();
})->throws(RuntimeException::class, 'non configuree');
