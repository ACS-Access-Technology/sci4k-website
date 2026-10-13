<?php

use App\Support\Sauvegarde\ArchiveFichiers;

beforeEach(function () {
    $this->dossier = sys_get_temp_dir().'/sci4k-archive-'.bin2hex(random_bytes(4));
    mkdir($this->dossier.'/source/biens', 0777, true);
    $this->sortie = $this->dossier.'/fichiers.tar.gz';
});

afterEach(function () {
    $supprimer = function (string $d) use (&$supprimer) {
        foreach (array_diff(scandir($d), ['.', '..']) as $f) {
            is_dir("$d/$f") ? $supprimer("$d/$f") : unlink("$d/$f");
        }
        rmdir($d);
    };
    $supprimer($this->dossier);
});

it('archive les fichiers televerses avec leurs chemins relatifs', function () {
    file_put_contents($this->dossier.'/source/biens/a.jpg', 'photo A');
    file_put_contents($this->dossier.'/source/fond.webp', 'fond');

    expect(ArchiveFichiers::creer($this->dossier.'/source', $this->sortie))->toBe(2);

    // Lecture directe dans l'archive, sans extraction : l'extraction de Phar
    // echoue sous Windows sur des chemins aux separateurs mixtes, ce que le
    // conteneur Linux ne connait pas.
    file_put_contents($this->dossier.'/lu.tar', gzdecode(file_get_contents($this->sortie)));
    $archive = new PharData($this->dossier.'/lu.tar');

    expect($archive['biens/a.jpg']->getContent())->toBe('photo A')
        ->and($archive['fond.webp']->getContent())->toBe('fond');
});

it('refuse d archiver un volume vide : c est un signal, pas une sauvegarde', function () {
    ArchiveFichiers::creer($this->dossier.'/source', $this->sortie);
})->throws(RuntimeException::class, 'volume vide');

it('refuse un dossier introuvable', function () {
    ArchiveFichiers::creer($this->dossier.'/absent', $this->sortie);
})->throws(RuntimeException::class, 'introuvable');
