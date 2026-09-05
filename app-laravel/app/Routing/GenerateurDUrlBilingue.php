<?php

namespace App\Routing;

use Illuminate\Routing\UrlGenerator;

/**
 * `route('services.index')` rend l'adresse de la langue en cours.
 *
 * Les pages publiques sont enregistrees deux fois : nues pour le francais,
 * sous « /en » et avec des noms prefixes « en. » pour l'anglais. Sans cette
 * classe, chaque vue aurait du savoir dans quelle langue elle est rendue et
 * choisir son nom de route — soit quelque deux cents appels a corriger, et
 * autant d'occasions d'en oublier un. Un lien oublie ramenait le visiteur
 * anglophone au francais au milieu de sa navigation, sans erreur nulle part.
 *
 * La traduction est prudente sur trois points :
 *
 * - Elle n'agit QUE si la route prefixee existe. Le backoffice n'a pas de
 *   version anglaise : ses appels passent inchanges.
 * - Elle ne double jamais le prefixe : `route('en.home')` ecrit a la main
 *   reste `en.home`.
 * - Elle ne s'applique pas au francais, qui EST la forme nue.
 *
 * Le nom demande reste donc toujours valable : au pire, la traduction ne se
 * produit pas et le lien mene a la version francaise, ce qu'il faisait avant.
 */
class GenerateurDUrlBilingue extends UrlGenerator
{
    /** Le prefixe des noms de route de la version anglaise. */
    public const PREFIXE = 'en.';

    public function route($name, $parameters = [], $absolute = true)
    {
        return parent::route($this->nomSelonLaLangue($name), $parameters, $absolute);
    }

    protected function nomSelonLaLangue(mixed $nom): mixed
    {
        if (! is_string($nom) || app()->getLocale() !== 'en' || str_starts_with($nom, self::PREFIXE)) {
            return $nom;
        }

        $anglais = self::PREFIXE.$nom;

        return $this->routes->hasNamedRoute($anglais) ? $anglais : $nom;
    }
}
