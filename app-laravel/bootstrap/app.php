<?php

use App\Http\Middleware\AppliqueLangue;
use App\Http\Middleware\EnregistreVisite;
use App\Http\Middleware\FermeLeSitePublic;
use App\Http\Middleware\PoseLesEnTetesDeSecurite;
use App\Http\Middleware\RefuseLesComptesDesactives;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        // Les proxys de confiance ne se declarent PAS ici, bien que ce soit
        // l'endroit prevu. Ce fichier n'a acces qu'a env(), et
        // LoadEnvironmentVariables ne lit plus .env des que la configuration
        // est mise en cache — ce que fait tout deploiement de production.
        // La declaration aurait donc fonctionne en developpement et serait
        // restee inerte precisement la ou elle sert. Elle vit dans
        // AppServiceProvider, qui lit config().

        // Les en-tetes de securite sont poses sur TOUTE reponse du groupe web,
        // publique comme administrative. En tete de liste : ils doivent valoir
        // meme quand un middleware suivant interrompt la chaine — une
        // redirection vers la connexion, une page de maintenance, un refus.
        $middleware->web(prepend: [
            PoseLesEnTetesDeSecurite::class,
        ]);

        $middleware->web(append: [
            AppliqueLangue::class,
            // La case « Activer le mode maintenance » de l'ecran Configuration
            // existait depuis le debut et ne fermait rien. Voir le middleware
            // pour les trois chemins qui restent ouverts, site ferme.
            FermeLeSitePublic::class,
            EnregistreVisite::class,
            // Un compte desactive perd sa session EN COURS, et pas seulement
            // le droit de se reconnecter. On desactive un compte parce qu'il
            // se passe quelque chose maintenant.
            RefuseLesComptesDesactives::class,
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
        // Sentry ne s'accroche pas tout seul : sans cette ligne, aucune
        // exception ne lui parvient. Et rien ne le signalerait — un rapport qui
        // n'est pas envoye ne casse rien, il manque, ce qui ne se decouvre que
        // le jour ou l'on cherche une panne et qu'il n'y a rien a lire.
        //
        // Inerte tant que SENTRY_LARAVEL_DSN est vide, comme la traduction
        // automatique l'est sans sa cle. Et discret par construction :
        // send_default_pii reste a false, de sorte que ni adresse IP, ni
        // en-tetes, ni corps de requete ne quittent le serveur — la politique
        // de confidentialite promet en gras qu'aucune IP n'est conservee, et
        // l'envoyer a un tiers reviendrait au meme mensonge par un autre
        // chemin.
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // La page « introuvable » doit repondre dans la langue de l'adresse.
        //
        // AppliqueLangue ne peut pas s'en charger : il lit le NOM de la route
        // en cours, et une adresse introuvable n'en a aucune. Le middleware du
        // groupe « web » ne tourne d'ailleurs pas du tout ici, l'exception
        // etant levee par le routeur avant qu'il n'y arrive. Sans cette ligne,
        // /en/whatever rendait une page en francais a un visiteur anglais.
        //
        // Le segment se lit donc sur l'adresse elle-meme — le seul
        // renseignement disponible a ce stade. La vue est ensuite resolue par
        // Laravel comme d'habitude.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            $segments = explode('/', trim($request->path(), '/'));
            app()->setLocale($segments[0] === 'en' ? 'en' : 'fr');

            return null;
        });
    })->create();
