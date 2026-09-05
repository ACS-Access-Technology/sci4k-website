<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LangueController;
use App\Models\Parametre;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AppliqueLangue
{
    /**
     * Applique la langue retenue en session.
     *
     * A defaut de session — donc pour tout premier visiteur — la langue vient
     * du reglage « Configuration → Général ». Il y etait propose depuis le
     * debut et n'etait lu nulle part : le francais etait ecrit en dur ici, et
     * l'administrateur qui basculait le reglage sur l'anglais ne voyait rien
     * changer.
     *
     * Le repli reste « fr » si le reglage est absent ou fantaisiste : la table
     * des reglages peut ne pas exister lors d'une installation neuve, et une
     * page publique ne doit pas dependre de son etat.
     */
    public function handle(Request $request, Closure $suite): Response
    {
        $code = session('langue') ?? $this->langueParDefaut();

        if (in_array($code, LangueController::LANGUES, true)) {
            app()->setLocale($code);
        }

        return $suite($request);
    }

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
