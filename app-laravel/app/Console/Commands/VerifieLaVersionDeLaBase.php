<?php

namespace App\Console\Commands;

use App\Support\MoteurDeBase;
use Illuminate\Console\Command;
use Illuminate\Database\MySqlConnection;
use Illuminate\Support\Facades\DB;

/**
 * Compare le serveur MySQL joint a la version que le projet supporte.
 *
 * Deux usages :
 *
 *   - la CI, apres ses tests sur MySQL : un echec dit que l'image testee n'est
 *     plus celle que le projet declare, et la fusion s'arrete ;
 *   - le demarrage du conteneur, ou un ecart ne fait qu'AVERTIR dans les
 *     journaux. Un site ne doit pas refuser de demarrer parce que sa base a
 *     change de version mineure — mais l'ecart doit se voir, la ou celui qu'a
 *     releve l'audit etait reste invisible.
 *
 * Sur SQLite (developpement, tests), il n'y a rien a comparer : la commande le
 * dit et reussit.
 */
class VerifieLaVersionDeLaBase extends Command
{
    protected $signature = 'base:verifier-version';

    protected $description = 'Compare la version du serveur MySQL a celle que le projet supporte.';

    public function handle(): int
    {
        $connexion = DB::connection();

        if (! $connexion instanceof MySqlConnection) {
            $this->line("Pilote « {$connexion->getDriverName()} » : aucune version MySQL à comparer.");

            return self::SUCCESS;
        }

        $version = $connexion->getServerVersion();

        // MariaDB passe par le meme pilote mais n'est ni teste ni supporte.
        if ($connexion->isMaria() || ! MoteurDeBase::mysqlSupporte($version)) {
            $this->error(sprintf(
                '%s %s : le projet supporte et teste MySQL %s. Voir docs/MISE_EN_LIGNE.md.',
                $connexion->getDriverTitle(),
                $version,
                MoteurDeBase::MYSQL_SUPPORTE,
            ));

            return self::FAILURE;
        }

        $this->info(sprintf('MySQL %s : version supportée (%s).', $version, MoteurDeBase::MYSQL_SUPPORTE));

        return self::SUCCESS;
    }
}
