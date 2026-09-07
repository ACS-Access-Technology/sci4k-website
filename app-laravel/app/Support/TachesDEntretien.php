<?php

namespace App\Support;

/**
 * Les commandes d'entretien, et la seule liste qui en fasse foi.
 *
 * Elles sont declenchees de deux façons selon l'hebergement. Sur un serveur
 * ordinaire, une ligne de crontab appelle « artisan schedule:run » chaque
 * minute et la planification de routes/console.php decide. Sur une plateforme
 * sans acces en ligne de commande — Vercel — une route HTTP gardee les
 * execute.
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
}
