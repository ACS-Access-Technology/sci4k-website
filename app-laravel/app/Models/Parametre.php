<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Once;

/**
 * Un reglage general du site, sous forme de cle et de valeur.
 *
 * Les valeurs sont lues a chaque rendu de page publique — le nom du site, le
 * telephone, les liens sociaux. Les relire une par une aurait fait une
 * requete par reglage ; elles sont donc chargees en un bloc et gardees en
 * cache, vide des qu'un reglage change.
 *
 * Certaines cles portent un secret — le mot de passe SMTP. Celles-la sont
 * CHIFFREES en base et ne ressortent jamais telles quelles vers le
 * navigateur : l'ecran affiche un champ vide et ne remplace la valeur que si
 * l'editeur en saisit une nouvelle. Un mot de passe reaffiche dans un
 * formulaire se retrouve dans le HTML de la page, donc dans le cache du
 * navigateur et dans toute capture d'ecran de la configuration.
 */
class Parametre extends Model
{
    protected $table = 'parametres';

    protected $fillable = ['cle', 'valeur', 'groupe'];

    /** Cle du cache portant l'ensemble des reglages. */
    public const CACHE = 'parametres.tous';

    /**
     * Les cles dont la valeur est chiffree en base.
     *
     * @var list<string>
     */
    public const SECRETES = ['smtp_mot_de_passe'];

    /**
     * L'adresse de contact publique, quand l'ecran Configuration n'en porte
     * aucune.
     *
     * C'est la boite Google Workspace que l'agence releve reellement. Elle
     * etait recopiee en quatre endroits, sous la forme contact@sci4k.com —
     * une adresse qui n'existe pas : les courriels des visiteurs revenaient
     * en erreur. Une seule constante, pour que cela ne puisse plus diverger.
     */
    public const EMAIL_PUBLIC_PAR_DEFAUT = 'info@acsgroupe.ci';

    protected static function booted(): void
    {
        // Le cache est vide a l'ecriture comme a l'effacement : un reglage
        // enregistre doit se voir sur le site sans attendre une expiration.
        // La memoire de requete aussi : un ecran qui enregistre puis relit
        // dans la meme requete doit lire la nouvelle valeur.
        static::saved(fn () => self::oublier());
        static::deleted(fn () => self::oublier());
    }

    protected static function oublier(): void
    {
        Cache::forget(self::CACHE);
        Once::flush();
    }

    /**
     * La table des reglages existe-t-elle ? Une fois par requete.
     *
     * La question se pose parce que le site doit rester servable sur une base
     * qui n'a pas encore recu ses migrations. Elle etait posee avant CHAQUE
     * lecture d'un reglage : une trentaine de fois par page, et sur MySQL
     * chacune interroge information_schema.
     */
    public static function tableDisponible(): bool
    {
        return once(fn () => Schema::hasTable('parametres'));
    }

    /** Ce reglage porte-t-il un secret ? */
    public static function estSecrete(string $cle): bool
    {
        return in_array($cle, self::SECRETES, true);
    }

    /**
     * Tous les reglages, par cle. Charges une seule fois par requete.
     *
     * @return array<string, string|null>
     */
    public static function tous(): array
    {
        // Le cache seul ne suffisait pas : avec le pilote « database » de la
        // production, chaque lecture EST une requete SQL — une trentaine par
        // page. once() garde le resultat pour la requete en cours ; le
        // middleware OublieLaMemoireDeLaRequete le vide a la suivante.
        return once(fn () => Cache::rememberForever(self::CACHE, fn () => static::query()
            ->pluck('valeur', 'cle')
            ->all()));
    }

    /**
     * La valeur d'un reglage, ou son defaut.
     *
     * Une cle secrete est dechiffree ici. Le dechiffrement peut echouer si la
     * cle d'application a change depuis l'enregistrement — on rend alors le
     * defaut plutot que de laisser remonter une exception qui casserait
     * l'ecran entier pour un seul champ.
     */
    public static function lire(string $cle, mixed $defaut = null): mixed
    {
        $valeur = self::tous()[$cle] ?? null;

        if ($valeur === null || $valeur === '') {
            return $defaut;
        }

        if (self::estSecrete($cle)) {
            try {
                return Crypt::decryptString($valeur);
            } catch (\Throwable) {
                return $defaut;
            }
        }

        return $valeur;
    }

    /** Le reglage vaut-il vrai ? Les cases a cocher sont stockees en « 1 ». */
    public static function actif(string $cle, bool $defaut = false): bool
    {
        $valeur = self::lire($cle);

        return $valeur === null ? $defaut : in_array((string) $valeur, ['1', 'true', 'oui'], true);
    }

    /** Enregistre un reglage, en chiffrant si la cle est secrete. */
    public static function poser(string $cle, mixed $valeur, string $groupe = 'general'): void
    {
        if (self::estSecrete($cle) && $valeur !== null && $valeur !== '') {
            $valeur = Crypt::encryptString((string) $valeur);
        }

        static::updateOrCreate(
            ['cle' => $cle],
            ['valeur' => $valeur === null ? null : (string) $valeur, 'groupe' => $groupe],
        );
    }
}
