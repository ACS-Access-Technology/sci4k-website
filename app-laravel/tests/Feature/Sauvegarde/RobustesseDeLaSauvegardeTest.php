<?php

use App\Support\Sauvegarde\SauvegardeExterne;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Robustesse de la sauvegarde : reprises sur les pannes passageres, relecture
 * octet pour octet, surveillance exigee, et surtout : le succes n'est JAMAIS
 * annonce tant que les controles d'integrite ne sont pas passes.
 *
 * Ces tests utilisent la vraie base (export MySQL) : ignores sur SQLite.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Export propre a MySQL : couvert par la passe MySQL de la CI.');
    }

    Storage::fake('sauvegardes');
    $this->cle = base64_encode(random_bytes(32));
    $this->public = sys_get_temp_dir().'/sci4k-robuste-'.bin2hex(random_bytes(4));
    mkdir($this->public, 0777, true);
    file_put_contents($this->public.'/photo.jpg', 'photo');
});

afterEach(function () {
    if (isset($this->public) && is_dir($this->public)) {
        foreach (glob($this->public.'/*') as $f) {
            unlink($f);
        }
        rmdir($this->public);
    }
});

function sauvegardeRobuste(object $t, $disque, ?string $ping = 'https://ping.exemple.test/abc'): SauvegardeExterne
{
    return new SauvegardeExterne(
        pdo: DB::connection()->getPdo(),
        disque: $disque,
        cle: $t->cle,
        dossierFichiers: $t->public,
        prefixe: 'essai',
        urlSurveillance: $ping,
        tentatives: 3,
        attenteEnSecondes: 0,
    );
}

it('reprend un depot qui echoue une fois, et reussit', function () {
    Http::fake();
    $reel = Storage::disk('sauvegardes');
    $disque = Mockery::mock($reel)->makePartial();
    $essais = 0;
    $disque->shouldReceive('writeStream')->andReturnUsing(function (...$a) use (&$essais, $reel) {
        if (++$essais === 1) {
            throw new RuntimeException('reseau coupe');
        }

        return $reel->writeStream(...$a);
    });

    $r = sauvegardeRobuste($this, $disque)->executer(false);

    expect($r['verifie'])->toBeTrue()->and($essais)->toBeGreaterThan(1);
    Http::assertSent(fn ($req) => $req->url() === 'https://ping.exemple.test/abc');
    Http::assertNotSent(fn ($req) => str_ends_with($req->url(), '/fail'));
});

it('echoue apres trois essais, signale l echec, et ne confirme jamais la reussite', function () {
    Http::fake();
    $disque = Mockery::mock(Storage::disk('sauvegardes'))->makePartial();
    $disque->shouldReceive('writeStream')->times(3)->andThrow(new RuntimeException('reseau coupe'));

    expect(fn () => sauvegardeRobuste($this, $disque)->executer(false))
        ->toThrow(RuntimeException::class, 'reseau coupe');

    Http::assertSent(fn ($req) => str_ends_with($req->url(), '/fail'));
    Http::assertNotSent(fn ($req) => $req->url() === 'https://ping.exemple.test/abc');
});

it('refuse un objet altere apres le depot : le succes n est pas annonce', function () {
    Http::fake();
    $reel = Storage::disk('sauvegardes');
    $disque = Mockery::mock($reel)->makePartial();
    $disque->shouldReceive('writeStream')->andReturnUsing(fn (...$a) => $reel->writeStream(...$a));
    $disque->shouldReceive('readStream')->andReturnUsing(function ($chemin) use ($reel) {
        $octets = $reel->get($chemin);
        $octets[strlen($octets) - 30] = chr(ord($octets[strlen($octets) - 30]) ^ 1);
        $flux = fopen('php://temp', 'w+b');
        fwrite($flux, $octets);
        rewind($flux);

        return $flux;
    });

    expect(fn () => sauvegardeRobuste($this, $disque)->executer(false))->toThrow(RuntimeException::class);

    Http::assertSent(fn ($req) => str_ends_with($req->url(), '/fail'));
    Http::assertNotSent(fn ($req) => $req->url() === 'https://ping.exemple.test/abc');
});

it('refuse de sauvegarder sans surveillance externe', function () {
    config(['sauvegarde.cle' => $this->cle, 'sauvegarde.bucket' => 'b', 'sauvegarde.url_surveillance' => null, 'sauvegarde.sans_surveillance' => false]);

    SauvegardeExterne::depuisLaConfiguration();
})->throws(RuntimeException::class, 'SAUVEGARDE_PING_URL');

it('inscrit les empreintes du clair dans le manifeste, et le manifeste dans la copie hebdomadaire', function () {
    sauvegardeRobuste($this, Storage::disk('sauvegardes'), null)->executer(false, CarbonImmutable::parse('2026-10-11 03:20:00', 'UTC')); // dimanche

    $disque = Storage::disk('sauvegardes');
    $manifeste = json_decode($disque->get('essai/quotidien/2026-10-11/manifeste.json'), true);

    expect($manifeste['objets']['base'])->toHaveKeys(['nom', 'octets', 'sha256', 'sha256_clair'])
        ->and($disque->allFiles('essai/hebdomadaire'))->toHaveCount(2); // la base et le manifeste
});
