<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bien;
use App\Models\PageStatique;
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
 *
 * Ce controleur avait fini par refaire le defaut qu'il decrit. Ecrit quand
 * biens, presentation et contact etaient encore statiques, il a garde leurs
 * adresses en `.html` apres leur portage : le plan annonçait trois
 * redirections de plus, et les FICHES de biens n'y ont jamais figure. Les
 * chemins sont donc nommes par leur ROUTE et non ecrits a la main — une page
 * portee change alors d'adresse ici toute seule, et une route supprimee fait
 * tomber les tests au lieu de laisser une adresse morte dans le plan.
 */
class PlanDuSiteController extends Controller
{
    /**
     * Pages fixes du site, par nom de route, avec priorite et frequence.
     *
     * L'ordre est celui du menu, les pages legales fermant la liste avec la
     * priorite la plus basse.
     */
    protected const PAGES = [
        ['home', '1.0', 'weekly'],
        ['biens.index', '0.9', 'weekly'],
        ['services.index', '0.9', 'monthly'],
        ['presentation.index', '0.7', 'monthly'],
        ['contact.index', '0.7', 'monthly'],
        ['actualites.index', '0.8', 'weekly'],
        ['faq.index', '0.6', 'monthly'],
        ['mentions-legales.index', '0.3', 'yearly'],
        ['politique-confidentialite.index', '0.3', 'yearly'],
    ];

    public function __invoke(): Response
    {
        $entrees = [];
        $aujourdhui = now()->toDateString();

        // Une page legale non publiee retombe sur son fichier d'origine, mais
        // les mentions legales attendent encore des informations que seul le
        // client detient. Annoncer aux moteurs une page criblee de trous serait
        // pire que de ne pas l'annoncer : elle sort du plan tant qu'elle n'est
        // pas publiee.
        $legalesPubliees = PageStatique::where('publie', true)->pluck('slug')->all();

        foreach (self::PAGES as [$route, $priorite, $frequence]) {
            $slug = str_replace('.index', '', $route);

            if (in_array($slug, PageStatique::slugsEditables(), true)
                && ! in_array($slug, $legalesPubliees, true)) {
                continue;
            }

            $entrees[] = [
                'url' => route($route),
                'date' => $aujourdhui,
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

        // Les fiches de biens manquaient entierement, alors que ce sont les
        // pages que le visiteur cherche : un catalogue immobilier se trouve par
        // ses biens, pas par sa page de catalogue. Les biens VENDUS y figurent
        // aussi — leur fiche reste servie, marquee comme telle, et la retirer du
        // plan ferait disparaitre une adresse deja indexee.
        foreach (Bien::publies()->ordonnes()->get() as $bien) {
            $entrees[] = [
                'url' => route('biens.detail', $bien->slug),
                'date' => ($bien->updated_at ?? $bien->created_at)?->toDateString() ?? $aujourdhui,
                'frequence' => 'weekly',
                'priorite' => $bien->statut === Bien::VENDU ? '0.4' : '0.7',
            ];
        }

        return response()
            ->view('public.plan-du-site', ['entrees' => $entrees])
            ->header('Content-Type', 'application/xml');
    }
}
