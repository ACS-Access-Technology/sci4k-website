<?php

namespace App\Support\Sauvegarde;

use Illuminate\Contracts\Filesystem\Filesystem;
use Throwable;

/**
 * Controle de la configuration AVANT d'activer la sauvegarde.
 *
 * Une configuration « qui a l'air complete » ne prouve rien : c'est la
 * connexion reelle au stockage qui dit si la cle ecrit, lit, et — surtout —
 * si elle ne peut PAS supprimer. Cette derniere propriete est la promesse du
 * dispositif (une erreur de code ou un secret vole ne doit pas effacer
 * l'historique) ; elle se verifie aupres du fournisseur, pas dans la
 * documentation.
 *
 * L'essai de suppression ne vise QUE l'objet de controle que cette classe vient
 * de creer, sous « _essais/ » : si la suppression reussit, la cle est trop
 * puissante et le controle echoue ; si elle est refusee, l'objet reste (quelques
 * octets, a faire expirer par une regle de cycle de vie sur « _essais/ »).
 *
 * Aucune valeur secrete n'est jamais renvoyee : les messages ne nomment que
 * les variables.
 */
final class VerificationConfiguration
{
    public const OK = 'ok';

    public const AVERTISSEMENT = 'avertissement';

    public const ERREUR = 'erreur';

    /**
     * @param  array<string, mixed>  $configuration  config('sauvegarde')
     * @param  array<string, mixed>  $disque  config('filesystems.disks.sauvegardes')
     */
    public function __construct(
        private readonly array $configuration,
        private readonly array $disque,
        private readonly ?Filesystem $stockage,
    ) {}

    /** @return list<array{0: string, 1: string}> niveau, message */
    public function controler(bool $avecStockage = true): array
    {
        $r = [];

        $r[] = $this->configuration['active']
            ? [self::OK, 'SAUVEGARDE_ACTIVE est vrai : la sauvegarde est planifiee.']
            : [self::OK, "SAUVEGARDE_ACTIVE est faux : rien n'est planifie (etat attendu tant que l'activation n'est pas decidee)."];

        try {
            FluxChiffre::cle((string) ($this->configuration['cle'] ?? ''));
            $r[] = [self::OK, 'SAUVEGARDE_CLE : 32 octets en base64.'];
        } catch (Throwable) {
            $r[] = [self::ERREUR, 'SAUVEGARDE_CLE absente ou invalide (32 octets en base64 attendus).'];
        }

        foreach ([
            'SAUVEGARDE_S3_BUCKET' => $this->disque['bucket'] ?? null,
            'SAUVEGARDE_S3_ENDPOINT' => $this->disque['endpoint'] ?? null,
            'SAUVEGARDE_S3_KEY' => $this->disque['key'] ?? null,
            'SAUVEGARDE_S3_SECRET' => $this->disque['secret'] ?? null,
        ] as $variable => $valeur) {
            $r[] = $valeur ? [self::OK, "$variable renseignee."] : [self::ERREUR, "$variable manquante."];
        }

        if (! empty($this->configuration['url_surveillance'])) {
            $r[] = [self::OK, 'SAUVEGARDE_PING_URL renseignee (a verifier cote service : periode d\'un jour, notification par courriel).'];
        } elseif (! empty($this->configuration['sans_surveillance'])) {
            $r[] = [self::AVERTISSEMENT, 'SAUVEGARDE_SANS_SURVEILLANCE est vrai : AUCUNE alerte si la sauvegarde cesse. Essais seulement.'];
        } else {
            $r[] = [self::ERREUR, 'SAUVEGARDE_PING_URL manquante : sans surveillance externe, un arret passerait inapercu.'];
        }

        if ($avecStockage && $this->stockage !== null && ! $this->contientUneErreur($r)) {
            array_push($r, ...$this->controlerLeStockage($this->stockage));
        }

        return $r;
    }

    /** @param  list<array{0: string, 1: string}>  $resultats */
    public function contientUneErreur(array $resultats): bool
    {
        return in_array(self::ERREUR, array_column($resultats, 0), true);
    }

    /** @return list<array{0: string, 1: string}> */
    private function controlerLeStockage(Filesystem $stockage): array
    {
        $r = [];
        $prefixe = trim((string) ($this->configuration['prefixe'] ?? 'sci4k'), '/');
        $objet = "$prefixe/_essais/controle-".bin2hex(random_bytes(6)).'.txt';
        $contenu = 'controle '.gmdate('c');

        try {
            $stockage->put($objet, $contenu);
            $r[] = [self::OK, 'Ecriture : acceptee.'];
        } catch (Throwable $e) {
            return [...$r, [self::ERREUR, 'Ecriture refusee ou stockage injoignable : '.$this->court($e)]];
        }

        try {
            $r[] = $stockage->get($objet) === $contenu
                ? [self::OK, 'Lecture : le contenu relu est identique.']
                : [self::ERREUR, 'Lecture : le contenu relu differe de ce qui a ete ecrit.'];
        } catch (Throwable $e) {
            $r[] = [self::ERREUR, 'Lecture refusee : '.$this->court($e)];
        }

        try {
            $trouve = in_array($objet, $stockage->files("$prefixe/_essais"), true);
            $r[] = $trouve ? [self::OK, 'Liste : l\'objet de controle est visible.'] : [self::AVERTISSEMENT, 'Liste : l\'objet de controle n\'apparait pas (droit de lister manquant ?).'];
        } catch (Throwable $e) {
            $r[] = [self::AVERTISSEMENT, 'Liste refusee : '.$this->court($e)];
        }

        // La promesse du dispositif : la cle ne doit PAS pouvoir supprimer.
        try {
            $stockage->delete($objet);
            $existeEncore = $stockage->exists($objet);

            $r[] = $existeEncore
                ? [self::OK, 'Suppression : sans effet sur l\'objet (refusee ou masquee) — a confirmer dans la console du fournisseur.']
                : [self::ERREUR, 'LA CLE PEUT SUPPRIMER : retirer la capacite de suppression (B2 : deleteFiles) avant d\'activer.'];
        } catch (Throwable) {
            $r[] = [self::OK, 'Suppression : REFUSEE par le fournisseur, comme attendu. L\'objet de controle reste sous _essais/ (a faire expirer par une regle de cycle de vie).'];
        }

        return $r;
    }

    private function court(Throwable $e): string
    {
        return class_basename($e).' — '.mb_substr($e->getMessage(), 0, 160);
    }
}
