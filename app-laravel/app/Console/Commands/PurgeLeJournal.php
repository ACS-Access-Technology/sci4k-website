<?php

namespace App\Console\Commands;

use App\Models\ActiviteJournalisee;
use Illuminate\Console\Command;

/**
 * Retire du journal d'activite ce qui depasse la duree de conservation.
 *
 * Le journal recevait une ligne a chaque creation, modification, publication et
 * suppression de contenu, et rien ne l'effaçait jamais.
 *
 * PAS D'AGREGAT, contrairement aux visites. Un journal d'audit repond a « qui a
 * touche a quoi, et quand » : un resume par jour ne repondrait plus a la
 * question. On garde la ligne entiere, ou on ne garde rien. Il n'y a donc rien
 * a sauver avant de supprimer, et pas de garde a poser comme pour la
 * frequentation.
 *
 * Idempotente par nature : une seconde execution ne trouve plus rien a retirer.
 */
class PurgeLeJournal extends Command
{
    protected $signature = 'journal:purger';

    protected $description = 'Retire les entrees du journal d activite qui depassent la duree de conservation.';

    public function handle(): int
    {
        $limite = now()->subDays(ActiviteJournalisee::JOURS_DE_CONSERVATION)->startOfDay();

        $retirees = ActiviteJournalisee::where('created_at', '<', $limite)->delete();

        $this->info("{$retirees} entree(s) de journal retiree(s).");

        return self::SUCCESS;
    }
}
