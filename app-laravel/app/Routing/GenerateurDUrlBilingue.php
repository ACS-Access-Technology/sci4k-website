<?php

namespace App\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\UrlGenerator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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

    /**
     * Une adresse SAISIE — cible d'une entree de menu, d'un bouton d'encart,
     * reglage — rendue dans la langue en cours.
     *
     * route() ne couvre que ce que le code ecrit. Les menus, eux, stockent
     * souvent un chemin : « / », « /biens.html », « /contact.html ». Rendu tel
     * quel sur une page anglaise, ce chemin ramenait le visiteur au francais —
     * par un detour de redirection, pour les anciennes adresses en .html. Aucune
     * donnee n'est reecrite : c'est l'affichage qui retrouve la route.
     *
     * Le chemin est confronte aux routes de l'application :
     *
     * - une redirection declaree (/biens.html -> /biens) est SUIVIE : le lien
     *   mene directement a la page moderne, sans le detour ;
     * - une page qui a une version dans chaque langue est rendue par route(),
     *   donc dans la langue en cours. Un chemin deja prefixe (« /en/faq ») est
     *   ramene au francais sur une page francaise, pour la meme raison ;
     * - tout le reste passe inchange : adresse externe, mailto, ancre, fichier
     *   servi directement par le serveur, page sans version anglaise. Au pire,
     *   le lien se comporte comme avant.
     *
     * La chaine de requete et l'ancre sont conservees.
     */
    public function adresseInterne(string $cible): string
    {
        $cible = trim($cible);

        if ($cible !== '' && $this->routes->hasNamedRoute($cible)) {
            return $this->route($cible);
        }

        if (! str_starts_with($cible, '/') || str_starts_with($cible, '//')) {
            return $cible;
        }

        $chemin = (string) (parse_url($cible, PHP_URL_PATH) ?: '/');
        $suite = (string) substr($cible, strlen($chemin));

        // Quelques sauts au plus : une redirection qui menerait a une autre ne
        // doit pas tourner en rond.
        for ($saut = 0; $saut < 3; $saut++) {
            $route = $this->routeDuChemin($chemin);

            if ($route === null) {
                return $cible;
            }

            $destination = $route->defaults['destination'] ?? null;

            if (! is_string($destination) || ! str_starts_with($destination, '/')) {
                break;
            }

            $chemin = $destination;
            $cible = $destination.$suite;
        }

        $nom = $route->getName();

        if (! is_string($nom) || isset($route->defaults['destination'])) {
            return $cible;
        }

        $nomFrancais = str_starts_with($nom, self::PREFIXE) ? substr($nom, strlen(self::PREFIXE)) : $nom;

        if (! $this->routes->hasNamedRoute(self::PREFIXE.$nomFrancais)) {
            return $cible;
        }

        return $this->route($nomFrancais, $route->parameters()).$suite;
    }

    /**
     * Point d'entree pour les modeles et les vues, qui ne recoivent le
     * generateur que sous le contrat de Laravel.
     */
    public static function localiser(?string $cible): string
    {
        $generateur = app('url');

        return $generateur instanceof self
            ? $generateur->adresseInterne((string) $cible)
            : (string) $cible;
    }

    /** La route GET que sert ce chemin, ou null. */
    protected function routeDuChemin(string $chemin): ?Route
    {
        try {
            return $this->routes->match(Request::create($chemin, 'GET'));
        } catch (HttpExceptionInterface) {
            return null;
        }
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
