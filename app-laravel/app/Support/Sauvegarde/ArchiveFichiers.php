<?php

namespace App\Support\Sauvegarde;

use FilesystemIterator;
use Phar;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

/**
 * Archive tar.gz des fichiers televerses depuis le backoffice.
 *
 * Ils ne sont PAS dans la base : la base ne garde que leur chemin. Une
 * sauvegarde de la base seule rend un site dont toutes les photos manquent.
 * Ils vivent sur le volume de Railway (storage/app/public), qui n'est pas
 * sauvegarde non plus sur le plan actuel.
 */
final class ArchiveFichiers
{
    /**
     * @return int le nombre de fichiers archives
     */
    public static function creer(string $dossier, string $cheminTarGz): int
    {
        if (! is_dir($dossier)) {
            throw new RuntimeException("Dossier introuvable : $dossier");
        }

        $tar = preg_replace('/\.gz$/', '', $cheminTarGz);
        @unlink($tar);

        $archive = new PharData($tar);
        $racine = rtrim(str_replace('\\', '/', realpath($dossier)), '/').'/';
        $n = 0;

        $parcours = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($parcours as $fichier) {
            if (! $fichier->isFile() || $fichier->isLink()) {
                continue;
            }

            $chemin = str_replace('\\', '/', $fichier->getRealPath());
            $archive->addFile($chemin, substr($chemin, strlen($racine)));
            $n++;
        }

        if ($n === 0) {
            // Un tar vide n'est pas une sauvegarde : un volume vide est un signal.
            throw new RuntimeException("Aucun fichier a archiver dans $dossier : volume vide ou mal monte.");
        }

        $archive->compress(Phar::GZ);
        unset($archive);
        Phar::unlinkArchive($tar);

        if (! is_file($cheminTarGz)) {
            throw new RuntimeException("L'archive n'a pas ete produite : $cheminTarGz");
        }

        return $n;
    }
}
