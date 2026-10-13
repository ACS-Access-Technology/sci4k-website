<?php

use App\Support\Sauvegarde\FluxChiffre;

/**
 * Le chiffrement des sauvegardes. Une sauvegarde qu'on ne peut pas relire, ou
 * qu'on relit tronquee sans le savoir, est pire qu'une absence de sauvegarde.
 */
beforeEach(function () {
    $this->dossier = sys_get_temp_dir().'/sci4k-chiffre-'.bin2hex(random_bytes(4));
    mkdir($this->dossier);
    $this->cle = random_bytes(32);
});

afterEach(function () {
    foreach (glob($this->dossier.'/*') as $f) {
        unlink($f);
    }
    rmdir($this->dossier);
});

function aller_retour(object $t, string $contenu, ?string $cleRetour = null): string
{
    file_put_contents("{$t->dossier}/clair", $contenu);
    FluxChiffre::chiffrer("{$t->dossier}/clair", "{$t->dossier}/chiffre", $t->cle);
    FluxChiffre::dechiffrer("{$t->dossier}/chiffre", "{$t->dossier}/relu", $cleRetour ?? $t->cle);

    return file_get_contents("{$t->dossier}/relu");
}

it('relit exactement ce qui a ete chiffre', function (string $contenu) {
    expect(aller_retour($this, $contenu))->toBe($contenu);
})->with([
    'fichier vide' => [''],
    'quelques octets' => ["Villa F6 — Bingerville\n"],
    'un bloc exact' => [fn () => str_repeat('a', FluxChiffre::BLOC)],
    'plusieurs blocs' => [fn () => random_bytes(FluxChiffre::BLOC * 2 + 12345)],
]);

it('ne laisse rien de lisible dans le fichier chiffre', function () {
    aller_retour($this, 'info@acsgroupe.ci Villa F6 Bingerville');

    $brut = file_get_contents("{$this->dossier}/chiffre");

    expect($brut)->toStartWith(FluxChiffre::MAGIE)
        ->not->toContain('Villa F6')
        ->not->toContain('acsgroupe');
});

it('refuse une mauvaise cle', function () {
    aller_retour($this, 'contenu', random_bytes(32));
})->throws(RuntimeException::class, 'mauvaise cle');

it('refuse un octet modifie', function () {
    file_put_contents("{$this->dossier}/clair", random_bytes(5000));
    FluxChiffre::chiffrer("{$this->dossier}/clair", "{$this->dossier}/chiffre", $this->cle);

    $octets = file_get_contents("{$this->dossier}/chiffre");
    $octets[strlen($octets) - 20] = chr(ord($octets[strlen($octets) - 20]) ^ 1);
    file_put_contents("{$this->dossier}/chiffre", $octets);

    FluxChiffre::dechiffrer("{$this->dossier}/chiffre", "{$this->dossier}/relu", $this->cle);
})->throws(RuntimeException::class);

it('refuse un fichier tronque, y compris a la frontiere d un bloc', function () {
    file_put_contents("{$this->dossier}/clair", random_bytes(FluxChiffre::BLOC * 2 + 10));
    FluxChiffre::chiffrer("{$this->dossier}/clair", "{$this->dossier}/chiffre", $this->cle);

    $entier = file_get_contents("{$this->dossier}/chiffre");

    // Retire le dernier bloc, proprement, a sa frontiere : seul le marquage de
    // fin peut le trahir.
    $position = strlen(FluxChiffre::MAGIE) + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES;
    $positions = [];
    while ($position < strlen($entier)) {
        $positions[] = $position;
        $position += 4 + unpack('N', substr($entier, $position, 4))[1];
    }
    file_put_contents("{$this->dossier}/chiffre", substr($entier, 0, end($positions)));

    expect(fn () => FluxChiffre::dechiffrer("{$this->dossier}/chiffre", "{$this->dossier}/relu", $this->cle))
        ->toThrow(RuntimeException::class, 'tronquee');
});

it('refuse un fichier qui n est pas une sauvegarde', function () {
    file_put_contents("{$this->dossier}/chiffre", 'ceci est un texte quelconque, assez long pour passer la lecture');

    expect(fn () => FluxChiffre::dechiffrer("{$this->dossier}/chiffre", "{$this->dossier}/relu", $this->cle))
        ->toThrow(RuntimeException::class, "n'est pas une sauvegarde");
});

it('n accepte qu une cle de 32 octets en base64', function () {
    expect(strlen(FluxChiffre::cle(base64_encode(random_bytes(32)))))->toBe(32);

    expect(fn () => FluxChiffre::cle('courte'))->toThrow(RuntimeException::class);
    expect(fn () => FluxChiffre::cle(base64_encode(random_bytes(16))))->toThrow(RuntimeException::class);
});
