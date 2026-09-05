<?php

namespace App\Http\Middleware;

use App\Models\Visite;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnregistreVisite
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful() && ! $request->expectsJson()) {
            // Ni adresse IP ni type de navigateur : la mesure compte des pages
            // vues et des visiteurs distincts, rien de plus. `session_hash` est
            // l'empreinte d'un identifiant que le site a lui-meme tire au sort,
            // et non une donnee prise au visiteur — c'est ce qui permet de
            // distinguer deux visites sans reconnaitre personne.
            Visite::create([
                'chemin' => '/'.ltrim($request->path(), '/'),
                'session_hash' => hash('sha256', (string) $request->session()->getId()),
                'visitee_le' => now(),
            ]);
        }

        return $response;
    }
}
