<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\AppliqueLangue::class,
            \App\Http\Middleware\EnregistreVisite::class,
            // Un compte desactive perd sa session EN COURS, et pas seulement
            // le droit de se reconnecter. On desactive un compte parce qu'il
            // se passe quelque chose maintenant.
            \App\Http\Middleware\RefuseLesComptesDesactives::class,
        ]);

        // Les trois points d'ecriture ouverts au public sortent du controle
        // CSRF. C'etait deja l'intention declaree dans routes/web.php — « la
        // limitation de debit y remplace l'authentification », les formulaires
        // vivant dans des pages statiques qui ne traversent pas la session et
        // n'ont donc aucun jeton a presenter — mais l'exception n'avait jamais
        // ete posee. Les trois routes repondaient 419, et main.js avale
        // l'echec en silence : aucun message de contact, aucune inscription a
        // la lettre d'information et aucune demande de visite n'atteignait le
        // backoffice. Les tests n'en montraient rien, Laravel court-circuitant
        // le controle CSRF en environnement de test.
        //
        // Le retirer ne cede rien : CSRF protege une action qu'un compte
        // connecte accomplirait a son insu. Ici il n'y a ni compte ni session,
        // et n'importe qui peut deja poster directement. La vraie defense est
        // ailleurs, et elle est en place : throttle:5,1 sur chaque route, un
        // champ piege et des longueurs bornees dans chaque controleur.
        $middleware->validateCsrfTokens(except: [
            'messages',
            'newsletter',
            'visites',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
