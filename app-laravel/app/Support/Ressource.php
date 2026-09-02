<?php

namespace App\Support;

/**
 * Adresse d'une ressource de public/, avec son empreinte de version.
 *
 * Les feuilles et scripts du site etaient servis a URL constante :
 * assets/style.css, sans rien derriere. Un navigateur qui les a deja vus les
 * ressert donc depuis son cache, parfois pendant des jours. Consequence
 * mesuree : une correction CSS livree et deployee restait invisible, et rien
 * ne le signalait — ni au developpeur, ni au visiteur.
 *
 * Le probleme ne touche pas que le poste de developpement. Apres une mise en
 * ligne, un visiteur deja venu garde l'ancienne apparence jusqu'a expiration
 * de son cache.
 *
 * Ces fichiers ne passent pas par Vite — ils viennent de
 * maquettes-frontoffice/ et sont deposes par tools/sync-frontoffice.sh — ils
 * n'ont donc pas le manifeste versionne dont beneficie le backoffice. La date
 * de modification du fichier fait office de version : elle change a chaque
 * synchronisation, et seulement alors.
 */
class Ressource
{
    /**
     * Empreintes deja calculees, pour ne pas interroger le disque deux fois
     * quand une meme page reclame la meme ressource.
     *
     * @var array<string, string>
     */
    private static array $empreintes = [];

    public static function url(string $chemin): string
    {
        return asset($chemin).self::version($chemin);
    }

    private static function version(string $chemin): string
    {
        if (array_key_exists($chemin, self::$empreintes)) {
            return self::$empreintes[$chemin];
        }

        $absolu = public_path($chemin);

        // Un fichier absent ne doit pas faire echouer le rendu : la page se
        // sert alors sans empreinte, exactement comme avant.
        $version = is_file($absolu) ? @filemtime($absolu) : false;

        return self::$empreintes[$chemin] = $version ? '?v='.$version : '';
    }

    /** Utile aux tests, le cache etant statique. */
    public static function oublierLesEmpreintes(): void
    {
        self::$empreintes = [];
    }
}
