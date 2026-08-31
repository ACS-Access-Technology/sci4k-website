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
            Visite::create([
                'chemin' => '/'.ltrim($request->path(), '/'),
                'session_hash' => hash('sha256', (string) $request->session()->getId()),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'visitee_le' => now(),
            ]);
        }

        return $response;
    }
}
