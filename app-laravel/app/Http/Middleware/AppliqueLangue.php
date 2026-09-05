<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LangueController;
use App\Models\Parametre;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * La langue vient de l'ADRESSE, et non plus de la session.
 *
 * Elle vivait en session : la meme adresse servait deux contenus. Un moteur de
 * recherche n'a pas de session — il ne voyait donc que le francais, et tout le
 * site anglais lui etait invisible. Un lien anglais partage s'ouvrait de meme
 * en francais chez le destinataire, ce qu'aucun visiteur ne pouvait
 * comprendre.
 *
 * `/services` sert le francais, `/en/services` l'anglais. Le segment est
 * facultatif et contraint a « en » : une seule declaration de route par page.
 *
 * Les pages anglaises sont enregistrees sous des noms prefixes « en. », et
 * GenerateurDUrlBilingue traduit les appels a route() selon la langue posee
 * ici. Sans lui, il aurait fallu passer la langue a chacun des quelque deux
 * cents appels a route() du site — et un seul oubli ramenait le visiteur
 * anglophone au francais au milieu de sa navigation.
 */
class AppliqueLangue
{
    public function handle(Request $request, Closure $suite): Response
    {
        // Trois sources, dans cet ordre, et l'ordre EST le raisonnement.
        //
        // L'adresse d'abord : sur une page publique elle fait autorite, c'est
        // tout l'objet du prefixe.
        //
        // La session ensuite : le BACKOFFICE n'a pas de version anglaise de
        // ses adresses, et n'en aura pas — il n'est pas indexe, et prefixer
        // cent routes d'administration n'apporterait rien. Sa langue se retient
        // donc comme avant. Ce point a failli partir en regression : retirer la
        // session sans le voir aurait fige tout le backoffice en francais.
        //
        // Le reglage enfin, pour un premier visiteur qui n'a ni l'un ni
        // l'autre.
        $code = $this->langueDeLAdresse($request)
            ?? session('langue')
            ?? $this->langueParDefaut();

        if (in_array($code, LangueController::LANGUES, true)) {
            app()->setLocale($code);
        }

        // Les liens d'une page anglaise restent en anglais sans qu'aucune vue
        // n'ait a le savoir : GenerateurDUrlBilingue traduit les appels a
        // route() selon la langue posee ci-dessus.
        return $suite($request);
    }

    /**
     * La langue de la route empruntee.
     *
     * Le signal est le NOM de la route et non le premier segment de l'adresse :
     * les pages anglaises sont enregistrees sous « en. », et ce prefixe ne
     * peut pas etre confondu avec le slug d'un article ou d'un bien qui
     * s'appellerait « en ».
     *
     * Rend null sur une page francaise et sur tout ce qui n'a pas de version
     * anglaise : le backoffice, les formulaires, le plan du site.
     */
    protected function langueDeLAdresse(Request $request): ?string
    {
        $nom = $request->route()?->getName();

        if (! is_string($nom)) {
            return null;
        }

        if (str_starts_with($nom, 'en.')) {
            return 'en';
        }

        // Une page publique SANS prefixe est francaise, et le reste meme si la
        // session dit autre chose : sans cette ligne, un editeur ayant bascule
        // son backoffice en anglais aurait vu le site public suivre, et
        // l'adresse ne voudrait plus rien dire.
        //
        // Le test est fait sur l'existence de la route jumelle plutot que sur
        // une liste de noms : une page ajoutee au groupe bilingue est prise en
        // compte sans que personne n'ait a l'inscrire ici.
        return app('router')->getRoutes()->hasNamedRoute('en.'.$nom) ? 'fr' : null;
    }

    /**
     * La langue d'une page sans prefixe, donc le francais — sauf si
     * l'administrateur en a decide autrement dans « Configuration ».
     *
     * Le repli reste « fr » si le reglage est absent ou fantaisiste : la table
     * des reglages peut ne pas exister lors d'une installation neuve, et une
     * page publique ne doit pas dependre de son etat.
     */
    protected function langueParDefaut(): string
    {
        try {
            return Schema::hasTable('parametres')
                ? (string) Parametre::lire('langue_par_defaut', 'fr')
                : 'fr';
        } catch (\Throwable) {
            return 'fr';
        }
    }
}
