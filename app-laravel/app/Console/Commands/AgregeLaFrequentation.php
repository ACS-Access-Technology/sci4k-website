<?php

namespace App\Console\Commands;

use App\Models\PageQuotidienne;
use App\Models\Visite;
use App\Models\VisiteurQuotidien;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Agrege les visites des jours clos, puis purge le detail devenu inutile.
 *
 * La table des visites recevait une ligne par page vue et rien ne l'en
 * empechait. Cette commande la borne : elle compte, puis elle jette ce qu'elle
 * a compte.
 *
 * DEUX PROPRIETES portent toute la fiabilite.
 *
 * Elle est IDEMPOTENTE : les comptages passent par updateOrCreate sur une cle
 * unique, donc la rejouer sur un jour deja traite met la ligne a jour au lieu
 * d'en ajouter une seconde.
 *
 * Elle RATTRAPE : elle traite tous les jours clos qui n'ont pas encore
 * d'agregat, et non le seul jour d'hier. Un cron muet pendant une semaine se
 * rattrape a l'execution suivante.
 *
 * Et une garde : la purge ne s'execute QUE si l'agregation a reussi, et ne
 * touche QUE les jours dont l'agregat existe. Sans elle, une agregation tombee
 * en cours de route transformerait cette commande en destructeur silencieux —
 * elle supprimerait des visites sans les avoir comptees, un jour apres l'autre,
 * sans que rien ne le signale.
 */
class AgregeLaFrequentation extends Command
{
    protected $signature = 'frequentation:agreger';

    protected $description = 'Agrege les visites des jours clos, puis purge le detail au-dela de la retention.';

    public function handle(): int
    {
        try {
            $jours = $this->agreger();
        } catch (Throwable $erreur) {
            report($erreur);
            $this->error("L'agregation a echoue : aucune visite n'a ete purgee.");
            $this->error($erreur->getMessage());

            return self::FAILURE;
        }

        $purgees = $this->purger();

        $this->info("Frequentation agregee sur {$jours} jour(s), {$purgees} visite(s) purgee(s).");

        return self::SUCCESS;
    }

    /**
     * Compte les jours clos, et repond combien de jours ont ete traites.
     *
     * Le jour en cours est exclu : l'agreger donnerait un comptage partiel que
     * la prochaine execution devrait corriger, et le detail de la journee est
     * de toute facon encore la.
     */
    protected function agreger(): int
    {
        $veille = now()->startOfDay();

        // Le query builder plutot que le modele : ces lignes sont des
        // comptages, pas des visites. Les couler dans Visite leur donnerait un
        // type qui ment, avec des colonnes que le modele ne possede pas.
        $pages = DB::table('visites')
            ->selectRaw('DATE(visitee_le) AS jour, chemin, COUNT(*) AS pages_vues')
            ->where('visitee_le', '<', $veille)
            ->groupBy('jour', 'chemin')
            ->get();

        foreach ($pages as $comptage) {
            PageQuotidienne::updateOrCreate(
                ['jour' => $comptage->jour, 'chemin' => $comptage->chemin],
                ['pages_vues' => $comptage->pages_vues],
            );
        }

        $visiteurs = DB::table('visites')
            ->selectRaw('DATE(visitee_le) AS jour, COUNT(DISTINCT session_hash) AS visiteurs')
            ->where('visitee_le', '<', $veille)
            ->groupBy('jour')
            ->get();

        foreach ($visiteurs as $comptage) {
            VisiteurQuotidien::updateOrCreate(
                ['jour' => $comptage->jour],
                ['visiteurs' => $comptage->visiteurs],
            );
        }

        return $visiteurs->count();
    }

    /**
     * Supprime le detail au-dela de la retention, et repond combien de lignes.
     *
     * La condition d'existence est la garde : un jour dont l'agregat manque
     * survit, quel que soit son age. Elle couvre le cas d'une agregation
     * partielle, la ou le code de retour ci-dessus couvre celui d'une
     * agregation entierement tombee.
     */
    protected function purger(): int
    {
        $limite = now()->subDays(Visite::JOURS_DE_DETAIL)->startOfDay();

        return Visite::query()
            ->where('visitee_le', '<', $limite)
            ->whereRaw('EXISTS (SELECT 1 FROM visiteurs_quotidiens WHERE visiteurs_quotidiens.jour = DATE('.DB::getTablePrefix().'visites.visitee_le))')
            ->delete();
    }
}
