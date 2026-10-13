<?php

use App\Support\Sauvegarde\VerificationConfiguration as V;
use Illuminate\Contracts\Filesystem\Filesystem;

/**
 * Le controle qui precede l'activation. Il doit refuser une configuration
 * incomplete, et surtout refuser une cle qui PEUT supprimer : c'est la
 * promesse du dispositif, et elle se verifie aupres du fournisseur.
 */
function configurationComplete(array $surcharge = []): array
{
    return $surcharge + [
        'active' => false,
        'cle' => base64_encode(random_bytes(32)),
        'prefixe' => 'sci4k',
        'url_surveillance' => 'https://ping.exemple.test/abc',
        'sans_surveillance' => false,
    ];
}

function disqueComplet(array $surcharge = []): array
{
    return $surcharge + ['bucket' => 'b', 'endpoint' => 'https://s3.exemple.test', 'key' => 'k', 'secret' => 'secret-tres-confidentiel'];
}

/** Un stockage qui ecrit et lit, mais refuse de supprimer : ce qu'on attend de la cle B2. */
function stockageSansSuppression(): Filesystem
{
    $objets = [];
    $s = Mockery::mock(Filesystem::class);
    $s->shouldReceive('put')->andReturnUsing(function ($chemin, $contenu) use (&$objets) {
        $objets[$chemin] = $contenu;

        return true;
    });
    $s->shouldReceive('get')->andReturnUsing(function ($c) use (&$objets) {
        return $objets[$c] ?? null;
    });
    $s->shouldReceive('files')->andReturnUsing(function () use (&$objets) {
        return array_keys($objets);
    });
    $s->shouldReceive('delete')->andThrow(new RuntimeException('AccessDenied'));
    $s->shouldReceive('exists')->andReturnUsing(function ($c) use (&$objets) {
        return isset($objets[$c]);
    });

    return $s;
}

/** Un stockage ou la suppression reussit : la cle est trop puissante. */
function stockageQuiSupprime(): Filesystem
{
    $objets = [];
    $s = Mockery::mock(Filesystem::class);
    $s->shouldReceive('put')->andReturnUsing(function ($chemin, $contenu) use (&$objets) {
        $objets[$chemin] = $contenu;

        return true;
    });
    $s->shouldReceive('get')->andReturnUsing(function ($c) use (&$objets) {
        return $objets[$c] ?? null;
    });
    $s->shouldReceive('files')->andReturnUsing(function () use (&$objets) {
        return array_keys($objets);
    });
    $s->shouldReceive('delete')->andReturnUsing(function ($c) use (&$objets) {
        unset($objets[$c]);

        return true;
    });
    $s->shouldReceive('exists')->andReturnUsing(function ($c) use (&$objets) {
        return isset($objets[$c]);
    });

    return $s;
}

it('valide une cle qui ecrit et lit mais ne peut pas supprimer', function () {
    $r = (new V(configurationComplete(), disqueComplet(), stockageSansSuppression()))->controler();

    expect(array_column($r, 0))->not->toContain(V::ERREUR);
    expect(implode("\n", array_column($r, 1)))->toContain('REFUSEE par le fournisseur');
});

it('REFUSE une cle qui peut supprimer', function () {
    $c = new V(configurationComplete(), disqueComplet(), stockageQuiSupprime());
    $r = $c->controler();

    expect($c->contientUneErreur($r))->toBeTrue()
        ->and(implode("\n", array_column($r, 1)))->toContain('LA CLE PEUT SUPPRIMER');
});

it('refuse une configuration incomplete sans toucher au stockage', function () {
    $stockage = Mockery::mock(Filesystem::class);
    $stockage->shouldNotReceive('put');

    $c = new V(configurationComplete(['cle' => 'courte']), disqueComplet(['bucket' => null]), $stockage);
    $r = $c->controler();

    expect($c->contientUneErreur($r))->toBeTrue()
        ->and(implode("\n", array_column($r, 1)))->toContain('SAUVEGARDE_CLE')->toContain('SAUVEGARDE_S3_BUCKET');
});

it('exige la surveillance externe, sauf choix explicite pour des essais', function () {
    $sans = new V(configurationComplete(['url_surveillance' => null]), disqueComplet(), null);
    expect($sans->contientUneErreur($sans->controler(false)))->toBeTrue();

    $essai = new V(configurationComplete(['url_surveillance' => null, 'sans_surveillance' => true]), disqueComplet(), null);
    $r = $essai->controler(false);
    expect($essai->contientUneErreur($r))->toBeFalse()->and(array_column($r, 0))->toContain(V::AVERTISSEMENT);
});

it('signale un stockage injoignable', function () {
    $stockage = Mockery::mock(Filesystem::class);
    $stockage->shouldReceive('put')->andThrow(new RuntimeException('connexion refusee'));

    $c = new V(configurationComplete(), disqueComplet(), $stockage);

    expect($c->contientUneErreur($c->controler()))->toBeTrue();
});

it('ne revele jamais la cle ni le secret dans ses messages', function () {
    $configuration = configurationComplete();
    $r = (new V($configuration, disqueComplet(), stockageSansSuppression()))->controler();
    $texte = implode("\n", array_column($r, 1));

    expect($texte)->not->toContain($configuration['cle'])->not->toContain('secret-tres-confidentiel');
});

it('la commande echoue sur une configuration vide', function () {
    config(['sauvegarde.cle' => null, 'sauvegarde.bucket' => null, 'sauvegarde.url_surveillance' => null]);

    $this->artisan('sauvegarde:verifier-config', ['--sans-stockage' => true])
        ->expectsOutputToContain('NON valide')
        ->assertFailed();
});
