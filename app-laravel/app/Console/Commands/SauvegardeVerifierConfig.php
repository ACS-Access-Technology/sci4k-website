<?php

namespace App\Console\Commands;

use App\Support\Sauvegarde\VerificationConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SauvegardeVerifierConfig extends Command
{
    protected $signature = 'sauvegarde:verifier-config {--sans-stockage : Ne controle que les variables, sans toucher au stockage}';

    protected $description = 'Controle la configuration de la sauvegarde externe, y compris les droits reels de la cle (ecriture, lecture, suppression refusee)';

    public function handle(): int
    {
        $disque = (array) config('filesystems.disks.sauvegardes');
        $stockage = null;

        if (! $this->option('sans-stockage')) {
            try {
                $stockage = Storage::disk('sauvegardes');
            } catch (Throwable $e) {
                $this->error('Disque « sauvegardes » inutilisable : '.class_basename($e));
            }
        }

        $controle = new VerificationConfiguration((array) config('sauvegarde'), $disque, $stockage);
        $resultats = $controle->controler(! $this->option('sans-stockage'));

        foreach ($resultats as [$niveau, $message]) {
            match ($niveau) {
                VerificationConfiguration::OK => $this->line("  [ok]            $message"),
                VerificationConfiguration::AVERTISSEMENT => $this->warn("  [avertissement] $message"),
                default => $this->error("  [ERREUR]        $message"),
            };
        }

        if ($controle->contientUneErreur($resultats)) {
            $this->error('Configuration NON valide : ne pas activer.');

            return self::FAILURE;
        }

        $this->info('Configuration valide.');

        return self::SUCCESS;
    }
}
