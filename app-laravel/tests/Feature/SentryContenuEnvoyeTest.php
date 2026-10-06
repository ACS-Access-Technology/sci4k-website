<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Sentry\Event;
use Sentry\Laravel\Http\SetRequestMiddleware;
use Sentry\Laravel\ServiceProvider as SentryServiceProvider;
use Sentry\SentrySdk;
use Sentry\Serializer\PayloadSerializer;
use Sentry\State\HubInterface;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;

/**
 * Ce qui part REELLEMENT chez Sentry quand une page plante.
 *
 * SentryDiscretTest verifie les reglages ; ce fichier-ci intercepte l'envoi
 * lui-meme, tel que le SDK le serialiserait pour le reseau. C'est la seule
 * facon de le savoir : send_default_pii=false ne couvre PAS tout. Le SDK joint
 * par defaut le corps de la requete, sa chaine de requete et ses en-tetes non
 * listes comme sensibles, et PHP place dans chaque trace d'exception les
 * arguments des fonctions — la valeur qu'on tentait d'enregistrer comprise.
 *
 * Le scenario reproduit le pire cas plausible : un formulaire public dont
 * l'enregistrement echoue, avec tout ce qu'un visiteur peut y avoir mis.
 */
beforeEach(function () {
    $this->transport = new class implements TransportInterface
    {
        /** @var list<Event> */
        public array $evenements = [];

        public function send(Event $event): Result
        {
            $this->evenements[] = $event;

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };

    // Un DSN factice : rien ne sort, le transport ci-dessus recoit l'envoi.
    config([
        'sentry.dsn' => 'https://cle-factice@sentry.exemple.invalid/1',
        'sentry.transport' => $this->transport,
    ]);
    app()->forgetInstance(HubInterface::class);
    SentrySdk::setCurrentHub(app(HubInterface::class));

    // Ce que le paquet fait AU DEMARRAGE quand un DSN est present — ici posé
    // apres coup : le middleware qui lui donne la requete en cours, et les
    // ecouteurs des fils d'Ariane (requetes SQL, journaux...). Sans eux,
    // l'envoi serait plus pauvre qu'en production, et le test trop optimiste.
    app(Kernel::class)->pushMiddleware(SetRequestMiddleware::class);
    $fournisseur = app()->getProvider(SentryServiceProvider::class);
    (new ReflectionMethod($fournisseur, 'bindEvents'))->invoke($fournisseur);

    // Une route qui echoue comme echouerait un enregistrement : l'adresse du
    // visiteur passe en argument, et se retrouve dans le message SQL.
    Route::middleware('web')->post('/essai-sentry/desinscription/{jeton}', function () {
        DB::insert('insert into table_absente (email, telephone) values (?, ?)', [
            request('email'), request('telephone'),
        ]);
    });
});

afterEach(function () {
    app()->forgetInstance(HubInterface::class);
    SentrySdk::setCurrentHub(app(HubInterface::class));
});

/**
 * Ce que le SDK enverrait sur le reseau, octet pour octet — moins les cadres
 * de pile qui viennent de CE fichier. Sentry joint a chaque cadre les lignes
 * de code qui l'entourent, et le code de ce test contient justement les
 * donnees factices du visiteur : sans cette exclusion, il se denoncerait
 * lui-meme. En production, aucun cadre ne vient de ce fichier.
 */
function charge(Event $evenement): string
{
    $enveloppe = (new PayloadSerializer(SentrySdk::getCurrentHub()->getClient()->getOptions()))->serialize($evenement);
    $lignes = array_values(array_filter(explode("\n", $enveloppe)));
    $contenu = json_decode(end($lignes), true);

    foreach ($contenu['exception']['values'] as &$exception) {
        $exception['stacktrace']['frames'] = array_values(array_filter(
            $exception['stacktrace']['frames'] ?? [],
            fn ($cadre) => ($cadre['abs_path'] ?? null) !== __FILE__,
        ));
    }

    return (string) json_encode($contenu, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

const DONNEES_DU_VISITEUR = [
    'email' => 'visiteur.prive@exemple.ci',
    'telephone' => '0707070707',
    'message' => 'Mon projet confidentiel',
];

function envoyerLePireCas($test): string
{
    $test->withHeaders([
        'X-Forwarded-For' => '203.0.113.9',
        'X-Envoy-External-Address' => '203.0.113.9',
        'User-Agent' => 'Mozilla/5.0 NavigateurDuVisiteur',
        'Referer' => 'https://site.exemple/reset-password/jeton-de-reinitialisation',
        // Un navigateur l'envoie toujours ; sans lui, le SDK ne lit pas le
        // corps, et le test manquerait la fuite la plus directe.
        'Content-Length' => (string) strlen(http_build_query(DONNEES_DU_VISITEUR)),
    ])->post('/essai-sentry/desinscription/jeton-secret-123?email=visiteur.prive%40exemple.ci', DONNEES_DU_VISITEUR)
        ->assertServerError();

    expect($test->transport->evenements)->toHaveCount(1);

    return charge($test->transport->evenements[0]);
}

it('signale bien la panne a Sentry', function () {
    $charge = json_decode(envoyerLePireCas($this), true);

    // Ce qui sert a comprendre la panne est la.
    $types = array_column($charge['exception']['values'], 'type');

    expect($types)->toContain('Illuminate\Database\QueryException')
        ->and(json_encode($charge))->toContain('table_absente')
        ->and($charge['environment'])->toBe('testing');
});

it('n envoie rien de ce que le visiteur a saisi ou de ce qui l identifie', function () {
    $charge = envoyerLePireCas($this);

    foreach ([
        'visiteur.prive', '0707070707', 'Mon projet confidentiel', // le formulaire
        'jeton-secret-123', 'jeton-de-reinitialisation',            // les jetons des adresses
        '203.0.113.9',                                               // l'adresse IP
        'NavigateurDuVisiteur',                                      // le navigateur
    ] as $donnee) {
        expect($charge)->not->toContain($donnee);
    }
});
