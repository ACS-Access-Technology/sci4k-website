<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LangueController extends Controller
{
    /** Les seules langues servies par le site. */
    public const LANGUES = ['fr', 'en'];

    public function basculer(string $code): RedirectResponse
    {
        abort_unless(in_array($code, self::LANGUES, true), 404);

        session(['langue' => $code]);

        return redirect($this->pageDeRetour());
    }

    /**
     * La page vers laquelle renvoyer, jamais une adresse de bascule.
     *
     * `back()` seul ne suffit pas : la session enregistre /langue/* comme URL
     * precedente — requete GET, route existante, pas d'en-tete AJAX. Deux
     * bascules consecutives se renvoyaient donc l'une a l'autre.
     *
     * La boucle n'etait pas seulement inesthetique : main.js appelle cette
     * route par `fetch`, qui suit les redirections, et CHAQUE saut reecrit la
     * langue en session. Un visiteur basculant deux fois depuis une page
     * statique terminait sur la langue opposee a celle qu'il avait demandee,
     * apres une vingtaine de requetes, sans qu'aucun signal ne parte.
     */
    protected function pageDeRetour(): string
    {
        $precedente = url()->previous();

        if (str_contains(parse_url($precedente, PHP_URL_PATH) ?? '', '/langue/')) {
            return url('/');
        }

        return $precedente;
    }
}
