<?php

namespace App\Support;

use App\Models\ExecutionDEntretien;
use Carbon\CarbonInterface;
use Throwable;

/**
 * Les commandes d'entretien, et la seule liste qui en fasse foi.
 *
 * Elles sont declenchees de deux façons selon l'hebergement. Dans le conteneur
 * (Railway), le planificateur integre — « artisan schedule:work », lance et
 * relance par tools/demarrer-conteneur.sh — suit la planification de
 * routes/console.php. Sur une plateforme sans processus permanent — Vercel —
 * une route HTTP gardee les execute.
 *
 * LA LISTE EST ICI, ET NON RECOPIEE AUX DEUX ENDROITS : une tache ajoutee a un
 * seul des deux ne tournerait que sur la moitie des deploiements, et rien ne le
 * signalerait.
 *
 * La route ne passe pas par schedule:run, volontairement : celui-ci n'execute
 * que les taches dues a la minute courante. Un appel decale d'une minute, ou un
 * fuseau qui diverge, et l'entretien ne tourne jamais — sans erreur, sans
 * trace.
 */
class TachesDEntretien
{
    /** @var list<string> */
    public const COMMANDES = [
        'frequentation:agreger',
        'journal:purger',
    ];

    /**
     * L'heure de chaque commande. 3 h 10 : le jour a change depuis longtemps,
     * et l'heure creuse evite que la purge ne croise le trafic ; une minute
     * decalee plutot que l'heure ronde, ou se pressent toutes les taches de
     * tous les heberges. La purge du journal une demi-heure apres, pour que
     * les deux entretiens ne se disputent pas la base.
     *
     * @var array<string, string>
     */
    public const HEURES = [
        'frequentation:agreger' => '03:10',
        'journal:purger' => '03:40',
    ];

    /**
     * Au-dela, l'entretien est declare en retard. Une journee, plus deux
     * heures de marge : un redemarrage pile a 3 h 10 fait manquer UNE passe,
     * rattrapee la nuit suivante par les commandes elles-memes — ce n'est pas
     * une panne.
     */
    public const RETARD_ADMIS_EN_HEURES = 26;

    /** L'identifiant du moniteur Sentry de chaque commande. */
    public static function moniteur(string $commande): string
    {
        return 'sci4k-'.str_replace(':', '-', $commande);
    }

    /**
     * Note la fin d'une execution. Appelee a la fin de chaque commande, par
     * AppServiceProvider, quel que soit le declencheur.
     *
     * Une panne d'ecriture ici ne doit pas faire echouer l'entretien qui vient
     * de reussir : elle est signalee, et le tableau de bord montrera un retard.
     */
    public static function noter(string $commande, int $codeDeSortie): void
    {
        if (! in_array($commande, self::COMMANDES, true)) {
            return;
        }

        try {
            ExecutionDEntretien::updateOrCreate(
                ['commande' => $commande],
                [$codeDeSortie === 0 ? 'derniere_reussite' : 'dernier_echec' => now()],
            );
        } catch (Throwable $erreur) {
            report($erreur);
        }
    }

    /**
     * L'etat de chaque commande, pour le tableau de bord.
     *
     * @return list<array{commande: string, derniere_reussite: ?CarbonInterface, dernier_echec: ?CarbonInterface, en_retard: bool, en_echec: bool}>
     */
    public static function etat(): array
    {
        $executions = ExecutionDEntretien::whereIn('commande', self::COMMANDES)->get()->keyBy('commande');
        $limite = now()->subHours(self::RETARD_ADMIS_EN_HEURES);

        return array_map(function (string $commande) use ($executions, $limite) {
            $execution = $executions->get($commande);
            $reussite = $execution?->derniere_reussite;
            $echec = $execution?->dernier_echec;

            // Le delai court depuis la derniere reussite, ou, s'il n'y en a
            // jamais eu, depuis le debut du suivi. Sans ligne du tout, rien ne
            // dit que l'entretien ait jamais tourne : c'est une alerte.
            $reference = $reussite ?? $execution?->suivi_depuis;

            return [
                'commande' => $commande,
                'derniere_reussite' => $reussite,
                'dernier_echec' => $echec,
                'en_retard' => $reference === null || $reference->lt($limite),
                // Un echec posterieur a la derniere reussite : la derniere
                // tentative a echoue, meme si le retard n'est pas encore la.
                'en_echec' => $echec !== null && ($reussite === null || $echec->gt($reussite)),
            ];
        }, self::COMMANDES);
    }
}
