<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Response;

/**
 * Plan du site, rendu depuis la base.
 *
 * Le fichier fige de frontoffice/ annonçait encore /services.html, /faq.html
 * et /actualites.html, qui ne repondent plus que par une redirection 301. Un
 * plan de site qui ne liste que des redirections est exactement ce que le
 * cadrage range dans « pas reversible a bon compte : les adresses des pages
 * publiques, deja indexees ».
 *
 * Plus grave a terme : le fichier ne connaissait aucun article. Les douze
 * articles repris du site, et tous ceux que l'administration publiera, etaient
 * invisibles pour les moteurs. Un fichier fige ne pouvait pas suivre — d'ou
 * cette route, qui lit ce qui est reellement servi.
 */
class PlanDuSiteController extends Controller
{
    /**
     * Pages servies telles quelles, avec leur priorite et leur frequence.
     *
     * Les quatre premieres restent statiques jusqu'a leur portage ; les trois
     * suivantes sont rendues par Laravel. Les mentions legales ferment la
     * liste, avec la priorite la plus basse.
     */
    protected const PAGES = [
        ['/', '1.0', 'weekly'],
        ['/biens.html', '0.9', 'weekly'],
        ['/presentation.html', '0.7', 'monthly'],
        ['/contact.html', '0.7', 'monthly'],
        ['/services', '0.9', 'monthly'],
        ['/faq', '0.6', 'monthly'],
        ['/actualites', '0.8', 'weekly'],
        ['/mentions-legales.html', '0.3', 'yearly'],
        ['/politique-confidentialite.html', '0.3', 'yearly'],
    ];

    public function __invoke(): Response
    {
        $entrees = [];

        foreach (self::PAGES as [$chemin, $priorite, $frequence]) {
            $entrees[] = [
                'url' => url($chemin),
                'date' => now()->toDateString(),
                'frequence' => $frequence,
                'priorite' => $priorite,
            ];
        }

        // Chaque article publie a sa propre adresse depuis le lot 1. La date de
        // derniere modification vient de la base : c'est la seule facon pour un
        // moteur de savoir qu'un article a ete corrige.
        foreach (Article::publies()->get() as $article) {
            $entrees[] = [
                'url' => route('actualites.detail', $article),
                'date' => ($article->updated_at ?? $article->date_publication)->toDateString(),
                'frequence' => 'monthly',
                'priorite' => '0.6',
            ];
        }

        return response()
            ->view('public.plan-du-site', ['entrees' => $entrees])
            ->header('Content-Type', 'application/xml');
    }
}
