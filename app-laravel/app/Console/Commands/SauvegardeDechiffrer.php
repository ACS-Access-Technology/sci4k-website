<?php

namespace App\Console\Commands;

use App\Support\Sauvegarde\FluxChiffre;
use Illuminate\Console\Command;
use Throwable;

class SauvegardeDechiffrer extends Command
{
    protected $signature = 'sauvegarde:dechiffrer {entree : Fichier .enc} {sortie : Fichier dechiffre} {--cle= : Cle en base64 (sinon SAUVEGARDE_CLE)}';

    protected $description = 'Dechiffre une sauvegarde (a utiliser pour une restauration, jamais sur la base de production)';

    public function handle(): int
    {
        try {
            $cle = FluxChiffre::cle((string) ($this->option('cle') ?: config('sauvegarde.cle')));
            FluxChiffre::dechiffrer($this->argument('entree'), $this->argument('sortie'), $cle);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Fichier dechiffre : '.$this->argument('sortie'));

        return self::SUCCESS;
    }
}
