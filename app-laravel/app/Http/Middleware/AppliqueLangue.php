<?php

namespace App\Http\Middleware;

use App\Http\Controllers\LangueController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppliqueLangue
{
    /** Applique la langue retenue en session, francais par defaut. */
    public function handle(Request $request, Closure $suite): Response
    {
        $code = session('langue', 'fr');

        if (in_array($code, LangueController::LANGUES, true)) {
            app()->setLocale($code);
        }

        return $suite($request);
    }
}
