<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LangueController extends Controller
{
    /** Les seules langues servies par le site. */
    public const LANGUES = ['fr', 'en'];

    /**
     * Bascule vers l'autre langue, en changeant d'ADRESSE.
     *
     * La langue vivait en session, et cette route l'y ecrivait. Elle vit
     * desormais dans l'adresse : basculer, c'est aller a la meme page sous son
     * autre forme. La session n'a plus rien a retenir.
     *
     * La route reste, et reste nommee : les pages statiques encore servies
     * depuis public/ pointent dessus, et main.js l'appelle. Le jour ou elles
     * auront toutes ete portees, elle pourra partir.
     */
    public function basculer(string $code, Request $requete): RedirectResponse
    {
        abort_unless(in_array($code, self::LANGUES, true), 404);

        // La session sert encore, pour le BACKOFFICE : ses adresses n'ont pas
        // de version anglaise, et n'en auront pas — il n'est pas indexe, et
        // prefixer cent routes d'administration n'apporterait rien. Sur les
        // pages publiques, l'adresse fait autorite et la session est ignoree.
        session(['langue' => $code]);

        return redirect($this->memePageDansLaLangue($code, $requete));
    }

    /**
     * La page d'ou vient le visiteur, traduite.
     *
     * On travaille sur le CHEMIN et non sur l'adresse entiere : un renvoi
     * construit a partir d'une valeur d'en-tete enverrait le visiteur ou
     * l'auteur du lien l'a voulu, y compris hors du site.
     */
    public static function traduireLeChemin(string $chemin, string $vers): string
    {
        $chemin = '/'.trim(parse_url($chemin, PHP_URL_PATH) ?? '', '/');

        // Le prefixe existant est retire, quelle qu'ait ete la langue de
        // depart : on repart toujours du chemin francais.
        foreach (self::LANGUES as $langue) {
            if ($chemin === '/'.$langue) {
                $chemin = '/';
            } elseif (str_starts_with($chemin, '/'.$langue.'/')) {
                $chemin = substr($chemin, strlen($langue) + 1);
            }
        }

        // Le francais n'a pas de prefixe : c'est le chemin nu.
        if ($vers !== 'en') {
            return url($chemin);
        }

        return url($chemin === '/' ? '/en' : '/en'.$chemin);
    }

    /**
     * D'ou l'on vient, sans jamais retomber sur une adresse de bascule.
     *
     * `back()` seul ne suffisait pas : la session enregistre /langue/* comme
     * URL precedente — requete GET, route existante, pas d'en-tete AJAX. Deux
     * bascules consecutives se renvoyaient l'une a l'autre.
     */
    protected function memePageDansLaLangue(string $code, Request $requete): string
    {
        $precedente = url()->previous();
        $chemin = parse_url($precedente, PHP_URL_PATH) ?? '/';

        if (str_contains($chemin, '/langue/')) {
            $chemin = '/';
        }

        return self::traduireLeChemin($chemin, $code);
    }
}
