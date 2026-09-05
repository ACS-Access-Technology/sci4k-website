<?php

namespace App\Http\Middleware;

use App\Livewire\Admin\Configuration;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le mode maintenance, tel que l'ecran « Configuration » le propose.
 *
 * La case existait depuis le debut, avec pour aide « Le site public affiche une
 * page d'attente », et personne ne la lisait : la cocher ne fermait rien. C'est
 * le pire genre de reglage — celui qui laisse croire qu'on a agi.
 *
 * TROIS chemins restent ouverts quand le site est ferme, et chacun pour une
 * raison precise :
 *
 * - Le backoffice et la connexion, sans quoi l'administrateur qui vient de
 *   cocher la case s'enfermerait dehors et n'aurait plus aucun moyen de
 *   decocher.
 * - Un compte deja connecte, qui doit pouvoir relire le site pendant les
 *   travaux : c'est souvent la raison meme de les avoir declares.
 * - robots.txt et le plan du site, pour que les moteurs lisent le refus
 *   d'indexation au lieu d'enregistrer une page d'attente a la place de
 *   l'accueil.
 *
 * La reponse porte un 503 et un en-tete `Retry-After`. Une page d'attente
 * servie en 200 dit aux moteurs « voici le contenu de ce site », et ils la
 * gardent.
 */
class FermeLeSitePublic
{
    /** Ce qui reste joignable, site ferme. */
    protected const CHEMINS_OUVERTS = [
        'admin', 'admin/*', 'dashboard', 'dashboard/*',
        'login', 'logout', 'forgot-password', 'reset-password/*',
        'livewire/*', 'robots.txt', 'sitemap.xml', 'up',
    ];

    public function handle(Request $requete, Closure $suivant): Response
    {
        if (! $this->siteFerme() || $requete->is(...self::CHEMINS_OUVERTS) || auth()->check()) {
            return $suivant($requete);
        }

        // Les coordonnees sont passees ICI, et non par un composer de vue.
        // Blade evalue le corps d'une @section pendant le rendu de la vue
        // ENFANT, avant que la mise en page ne rende — les variables posees sur
        // la mise en page n'y sont donc pas encore.
        return response()
            ->view('public.maintenance', [
                'telephonePublic' => Parametre::lire('telephone', '+225 07 06 16 50 29'),
                'emailPublic' => Parametre::lire('email_public', 'contact@sci4k.com'),
                // Les textes de la page, editables depuis « Configuration →
                // Général », sous la case qui ferme le site.
                'textes' => ReglageDeSection::where('slug', Configuration::SECTION_MAINTENANCE)->first(),
            ], 503)
            ->header('Retry-After', '3600');
    }

    protected function siteFerme(): bool
    {
        try {
            return Schema::hasTable('parametres') && Parametre::actif('mode_maintenance', false);
        } catch (\Throwable) {
            // Une base injoignable ne doit pas fermer le site : le defaut est
            // toujours « ouvert ».
            return false;
        }
    }
}
