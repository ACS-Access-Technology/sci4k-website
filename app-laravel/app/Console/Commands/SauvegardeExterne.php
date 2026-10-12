<?php

namespace App\Console\Commands;

use App\Support\Sauvegarde\SauvegardeExterne as Sauvegarde;
use Illuminate\Console\Command;
use Throwable;

class SauvegardeExterne extends Command
{
    protected $signature = 'sauvegarde:externe {--sans-fichiers : Ne sauvegarde que la base}';

    protected $description = 'Exporte la base et les fichiers televerses, les chiffre, les depose hors du serveur et verifie le depot';

    public function handle(): int
    {
        try {
            $r = Sauvegarde::depuisLaConfiguration()->executer(! $this->option('sans-fichiers'));
        } catch (Throwable $e) {
            $this->error('Sauvegarde echouee : '.class_basename($e).' — '.mb_substr($e->getMessage(), 0, 300));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sauvegarde reussie et verifiee : %d tables, %d lignes, %s octets chiffres.',
            $r['tables'], $r['lignes'], number_format($r['octets'], 0, ',', ' '),
        ));

        return self::SUCCESS;
    }
}
