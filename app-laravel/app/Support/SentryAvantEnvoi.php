<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;
use Throwable;

/**
 * Le dernier filtre avant qu'un rapport d'erreur ne parte chez Sentry.
 *
 * send_default_pii=false ne suffit pas, contrairement a ce qu'on croirait :
 * mesure faite (tests/Feature/SentryContenuEnvoyeTest.php), le SDK envoyait
 * encore, pour un formulaire public dont l'enregistrement echoue :
 *
 * - l'adresse complete, chaine de requete comprise — le jeton de
 *   desinscription, l'adresse electronique d'un lien de reinitialisation ;
 * - le navigateur (User-Agent), la page precedente (Referer), et l'adresse IP
 *   par l'en-tete X-Envoy-External-Address, que sa liste ne connait pas ;
 * - le message de l'exception SQL, que Laravel ecrit AVEC les valeurs
 *   inserees : l'adresse et le telephone du visiteur ;
 * - les arguments de chaque fonction de la pile, ces memes valeurs comprises.
 *
 * Le corps de la requete, lui, est coupe par max_request_body_size=never, et
 * les arguments a la source par zend.exception_ignore_args (docker/php.ini).
 * Ce filtre-ci ferme le reste, et redouble ces deux-la pour un environnement
 * qui ne les aurait pas.
 *
 * Ce qui reste suffit a comprendre une panne : le type d'exception, le fichier
 * et la ligne, la requete SQL avec ses « ? », la route (/biens/{slug}), et
 * l'identifiant interne du compte d'administration connecte.
 */
class SentryAvantEnvoi
{
    /** Les seuls en-tetes transmis : aucun ne designe le visiteur. */
    private const ENTETES_TRANSMIS = ['host', 'accept', 'content-type'];

    public static function filtrer(Event $evenement, ?EventHint $indice = null): Event
    {
        self::filtrerLaRequete($evenement);
        self::filtrerLesExceptions($evenement, $indice?->exception);
        self::filtrerLUtilisateur($evenement);

        return $evenement;
    }

    /**
     * L'adresse devient celle de la ROUTE — /newsletter/desinscription/{jeton}
     * et non le jeton lui-meme —, sans chaine de requete. Plus de corps, de
     * cookies ni d'en-tetes personnels.
     */
    private static function filtrerLaRequete(Event $evenement): void
    {
        $requete = $evenement->getRequest();

        if ($requete === []) {
            return;
        }

        $modele = request()->route()?->uri();
        $chemin = $modele !== null ? '/'.ltrim($modele, '/') : (string) parse_url((string) ($requete['url'] ?? ''), PHP_URL_PATH);
        $origine = parse_url((string) ($requete['url'] ?? ''), PHP_URL_SCHEME).'://'.parse_url((string) ($requete['url'] ?? ''), PHP_URL_HOST);

        $entetes = array_filter(
            (array) ($requete['headers'] ?? []),
            fn ($nom) => in_array(strtolower((string) $nom), self::ENTETES_TRANSMIS, true),
            ARRAY_FILTER_USE_KEY,
        );

        $evenement->setRequest(array_filter([
            'url' => $origine.$chemin,
            'method' => $requete['method'] ?? null,
            'headers' => $entetes,
        ]));
    }

    /**
     * Les messages SQL perdent leurs valeurs, et la pile ses arguments.
     */
    private static function filtrerLesExceptions(Event $evenement, ?Throwable $exception): void
    {
        $requetesSql = [];
        for ($courante = $exception; $courante !== null; $courante = $courante->getPrevious()) {
            if ($courante instanceof QueryException) {
                $requetesSql[] = $courante;
            }
        }

        foreach ($evenement->getExceptions() as $exceptionEnvoyee) {
            if (is_a($exceptionEnvoyee->getType(), QueryException::class, true)) {
                $requete = array_shift($requetesSql);
                $cause = $requete?->getPrevious()?->getMessage() ?? 'Erreur SQL';

                // getSql() rend la requete avec ses « ? », sans les valeurs.
                $exceptionEnvoyee->setValue(self::masquerLesLitteraux($cause).($requete ? ' (SQL: '.$requete->getSql().')' : ''));
            } elseif (is_a($exceptionEnvoyee->getType(), \PDOException::class, true)) {
                $exceptionEnvoyee->setValue(self::masquerLesLitteraux($exceptionEnvoyee->getValue()));
            }

            foreach ($exceptionEnvoyee->getStacktrace()?->getFrames() ?? [] as $cadre) {
                $cadre->setVars([]);
            }
        }
    }

    /**
     * « Duplicate entry 'x@y.ci' for key … » : MySQL cite la valeur fautive
     * entre apostrophes. Le nom de la contrainte, lui, reste lisible.
     */
    private static function masquerLesLitteraux(string $message): string
    {
        return (string) preg_replace("/'[^']*'/", "'[Filtre]'", $message);
    }

    /** L'identifiant interne du compte, et rien d'autre. */
    private static function filtrerLUtilisateur(Event $evenement): void
    {
        $utilisateur = $evenement->getUser();

        if ($utilisateur === null) {
            return;
        }

        $evenement->setUser($utilisateur->getId() !== null ? UserDataBag::createFromUserIdentifier($utilisateur->getId()) : null);
    }
}
