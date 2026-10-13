<?php

namespace App\Support\Sauvegarde;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;

/**
 * La sauvegarde quotidienne : exporter, chiffrer, deposer HORS du serveur,
 * puis RELIRE ce qui a ete depose.
 *
 * Etapes, dans l'ordre, chacune capable de tout arreter :
 *  1. export SQL compresse (DumpSql) ;
 *  2. archive des fichiers televerses (ArchiveFichiers) ;
 *  3. chiffrement de chaque fichier (FluxChiffre) ;
 *  4. depot sur le disque « sauvegardes » (stockage objet compatible S3) ;
 *  5. VERIFICATION : l'objet depose est retelecharge, dechiffre, decompresse,
 *     et l'export doit se terminer par sa ligne de fin. Une commande qui « a
 *     reussi » ne prouve pas qu'une sauvegarde existe ; c'est cette relecture
 *     qui le prouve ;
 *  6. manifeste, puis signal de bonne sante.
 *
 * L'ALERTE EST EXTERNE. Si le conteneur est arrete, le planificateur aussi, et
 * personne ne dirait rien. Chaque reussite appelle donc une adresse de
 * surveillance (SAUVEGARDE_PING_URL — un service de type « dead man's switch »)
 * qui previent par courriel quand le signal n'arrive plus. Un echec envoie le
 * signal d'echec, avec un message qui ne contient jamais de secret.
 *
 * RETENTION. L'application ne supprime RIEN : le jeton qu'elle detient n'en a
 * pas le droit, ce qui protege les sauvegardes d'un bogue ou d'un compte
 * compromis. L'expiration (14 jours pour quotidien/, 56 pour hebdomadaire/) se
 * regle par une regle de cycle de vie du bucket, cote fournisseur.
 */
final class SauvegardeExterne
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly Filesystem $disque,
        private readonly string $cle,
        private readonly string $dossierFichiers,
        private readonly string $prefixe = 'sci4k',
        private readonly ?string $urlSurveillance = null,
        private readonly ?string $dossierTemporaire = null,
        private readonly int $tentatives = 3,
        private readonly int $attenteEnSecondes = 5,
    ) {}

    /** Construit la sauvegarde avec la configuration de l'application. */
    public static function depuisLaConfiguration(): self
    {
        $cle = (string) config('sauvegarde.cle');

        if ($cle === '' || ! config('sauvegarde.bucket')) {
            throw new RuntimeException('Sauvegarde non configuree : SAUVEGARDE_CLE et SAUVEGARDE_S3_BUCKET sont requis.');
        }

        // Une sauvegarde sans alerte echoue en silence : si le conteneur
        // s'arrete, personne ne le sait. La surveillance est donc exigee, sauf
        // choix explicite (essais locaux).
        if (! config('sauvegarde.url_surveillance') && ! config('sauvegarde.sans_surveillance')) {
            throw new RuntimeException('Sauvegarde refusee : SAUVEGARDE_PING_URL est requis (surveillance externe). SAUVEGARDE_SANS_SURVEILLANCE=true le desactive, pour des essais seulement.');
        }

        return new self(
            pdo: DB::connection()->getPdo(),
            disque: Storage::disk('sauvegardes'),
            cle: $cle,
            dossierFichiers: (string) config('filesystems.disks.public.root'),
            prefixe: (string) config('sauvegarde.prefixe'),
            urlSurveillance: config('sauvegarde.url_surveillance') ?: null,
        );
    }

    /**
     * @return array{base: string, fichiers: ?string, tables: int, lignes: int, octets: int, verifie: bool}
     */
    public function executer(bool $avecFichiers = true, ?CarbonImmutable $maintenant = null): array
    {
        $maintenant ??= CarbonImmutable::now('UTC');
        $dossier = $this->dossierTemporaire();

        try {
            $this->signaler('start');
            $resultat = $this->deroule($dossier, $avecFichiers, $maintenant);
            $this->signaler('');
            Log::info('Sauvegarde externe reussie', $resultat);

            return $resultat;
        } catch (Throwable $e) {
            // Le message ne doit jamais contenir de secret : on ne journalise
            // que la classe et le texte de l'exception, jamais la configuration.
            $message = class_basename($e).': '.mb_substr($e->getMessage(), 0, 300);
            Log::error('Sauvegarde externe ECHOUEE', ['erreur' => $message]);
            $this->signaler('fail', $message);

            throw $e;
        } finally {
            self::vider($dossier);
        }
    }

    /** @return array{base: string, fichiers: ?string, tables: int, lignes: int, octets: int, verifie: bool} */
    private function deroule(string $dossier, bool $avecFichiers, CarbonImmutable $maintenant): array
    {
        $cle = FluxChiffre::cle($this->cle);
        $jour = $maintenant->format('Y-m-d');
        $horodatage = $maintenant->format('Ymd-His');

        // 1. base
        $sqlGz = "$dossier/base.sql.gz";
        $comptes = (new DumpSql($this->pdo))->ecrire($sqlGz);

        if ($comptes === []) {
            throw new RuntimeException("L'export ne contient aucune table.");
        }

        $objets = ['base' => ["base-$horodatage.sql.gz.enc", $sqlGz]];

        // 2. fichiers
        if ($avecFichiers) {
            $tarGz = "$dossier/fichiers.tar.gz";
            ArchiveFichiers::creer($this->dossierFichiers, $tarGz);
            $objets['fichiers'] = ["fichiers-$horodatage.tar.gz.enc", $tarGz];
        }

        // 3-5. chiffrer, deposer, verifier
        $manifeste = [
            'date_utc' => $maintenant->toIso8601String(),
            'application' => 'sci4k',
            'format' => FluxChiffre::MAGIE,
            'objets' => [],
        ];
        $octets = 0;

        foreach ($objets as $genre => [$nom, $source]) {
            $chiffre = "$source.enc";
            FluxChiffre::chiffrer($source, $chiffre, $cle);

            $empreinteClair = hash_file('sha256', $source);
            $empreinte = hash_file('sha256', $chiffre);
            $taille = filesize($chiffre);
            $octets += $taille;

            foreach ($this->destinations($nom, $maintenant) as $chemin) {
                $this->deposer($chiffre, $chemin, $taille);
            }

            $this->verifierLeDepot($genre, $this->destinations($nom, $maintenant)[0], $cle, $dossier, $empreinteClair);

            $manifeste['objets'][$genre] = ['nom' => $nom, 'octets' => $taille, 'sha256' => $empreinte, 'sha256_clair' => $empreinteClair];
        }

        $json = json_encode($manifeste, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->disque->put("{$this->prefixe}/quotidien/$jour/manifeste.json", $json);

        if ($maintenant->isSunday()) {
            $this->disque->put("{$this->prefixe}/hebdomadaire/{$maintenant->format('o-\\WW')}/manifeste.json", $json);
        }

        return [
            'base' => $objets['base'][0],
            'fichiers' => $objets['fichiers'][0] ?? null,
            'tables' => count($comptes),
            'lignes' => array_sum($comptes),
            'octets' => $octets,
            'verifie' => true,
        ];
    }

    /** @return list<string> quotidien/ toujours ; hebdomadaire/ le dimanche en plus */
    private function destinations(string $nom, CarbonImmutable $maintenant): array
    {
        $liste = ["{$this->prefixe}/quotidien/{$maintenant->format('Y-m-d')}/$nom"];

        if ($maintenant->isSunday()) {
            $liste[] = "{$this->prefixe}/hebdomadaire/{$maintenant->format('o-\\WW')}/$nom";
        }

        return $liste;
    }

    private function deposer(string $fichier, string $chemin, int $taille): void
    {
        // Un reseau qui flanche une nuit ne doit pas faire perdre la sauvegarde :
        // trois essais, avec une attente croissante. Chaque essai REPART du
        // debut du fichier (un flux partiellement lu n'est pas reutilisable).
        $this->avecReprises(function () use ($fichier, $chemin, $taille) {
            $flux = fopen($fichier, 'rb');

            try {
                if ($this->disque->writeStream($chemin, $flux) === false) {
                    throw new RuntimeException("Depot refuse : $chemin");
                }
            } finally {
                if (is_resource($flux)) {
                    fclose($flux);
                }
            }

            $distant = $this->disque->size($chemin);

            if ($distant !== $taille) {
                throw new RuntimeException("Depot incomplet : $chemin ($distant octets au lieu de $taille).");
            }
        }, "depot de $chemin");
    }

    /**
     * Rejoue $operation jusqu'a trois fois. La derniere erreur est relancee
     * telle quelle : elle seule explique l'echec.
     */
    private function avecReprises(callable $operation, string $libelle): void
    {
        $tentatives = max(1, $this->tentatives);

        for ($essai = 1; ; $essai++) {
            try {
                $operation();

                return;
            } catch (Throwable $e) {
                if ($essai >= $tentatives) {
                    throw $e;
                }

                Log::warning("Sauvegarde externe : $libelle, essai $essai sur $tentatives echoue, nouvel essai.", [
                    'erreur' => class_basename($e).': '.mb_substr($e->getMessage(), 0, 200),
                ]);

                if ($this->attenteEnSecondes > 0) {
                    sleep($this->attenteEnSecondes * $essai);
                }
            }
        }
    }

    /**
     * Retelecharge l'objet, le dechiffre et lit tout le contenu compresse. Pour
     * la base, la derniere ligne doit etre celle de fin d'export.
     */
    private function verifierLeDepot(string $genre, string $chemin, string $cle, string $dossier, string $empreinteClair): void
    {
        $local = "$dossier/verification-$genre.enc";
        $clair = "$dossier/verification-$genre.gz";

        $this->avecReprises(function () use ($chemin, $local) {
            $flux = $this->disque->readStream($chemin);

            if (! is_resource($flux)) {
                throw new RuntimeException("Relecture impossible : $chemin");
            }

            file_put_contents($local, $flux);
            fclose($flux);
        }, "relecture de $chemin");

        FluxChiffre::dechiffrer($local, $clair, $cle);

        // Octet pour octet : ce qui a ete relu et dechiffre est exactement ce qui
        // a ete exporte, pas seulement « quelque chose de lisible ».
        if (! hash_equals($empreinteClair, hash_file('sha256', $clair))) {
            throw new RuntimeException("Le contenu relu differe de l'export ($chemin) : empreintes differentes.");
        }

        $gz = gzopen($clair, 'rb');
        $derniere = '';

        while (! gzeof($gz)) {
            $ligne = gzgets($gz, 1048576);

            if ($ligne !== false && trim($ligne) !== '') {
                $derniere = trim($ligne);
            }
        }

        gzclose($gz);

        if ($genre === 'base' && $derniere !== DumpSql::FIN) {
            throw new RuntimeException("L'export relu est incomplet : la ligne de fin manque ($chemin).");
        }

        @unlink($local);
        @unlink($clair);
    }

    private function signaler(string $etat, string $message = ''): void
    {
        if (! $this->urlSurveillance) {
            return;
        }

        try {
            $url = rtrim($this->urlSurveillance, '/').($etat !== '' ? '/'.$etat : '');
            Http::timeout(10)->withBody($message, 'text/plain')->post($url);
        } catch (Throwable) {
            // Le signal est une aide : son echec ne doit pas faire echouer la sauvegarde.
        }
    }

    private function dossierTemporaire(): string
    {
        $racine = $this->dossierTemporaire ?? sys_get_temp_dir();
        $dossier = rtrim($racine, '/\\').DIRECTORY_SEPARATOR.'sci4k-sauvegarde-'.bin2hex(random_bytes(6));

        if (! mkdir($dossier, 0700, true) && ! is_dir($dossier)) {
            throw new RuntimeException("Dossier temporaire impossible a creer : $dossier");
        }

        return $dossier;
    }

    private static function vider(string $dossier): void
    {
        if (! is_dir($dossier)) {
            return;
        }

        foreach (glob($dossier.'/*') ?: [] as $f) {
            @unlink($f);
        }

        @rmdir($dossier);
    }
}
