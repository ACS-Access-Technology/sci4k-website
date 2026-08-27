<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Une entree d'un menu du site.
 *
 * Trois menus editables : la barre du haut, la colonne « Navigation » du pied
 * et les liens legaux du bas. Les deux autres colonnes du pied ne sont pas
 * ici : « Nos Services » se remplit depuis les services depuis le lot 2, et
 * « Nous contacter » depuis les coordonnees de la configuration. Les rendre
 * editables aurait cree une seconde source pour chacune.
 */
class EntreeDeMenu extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'entrees_de_menu';

    protected $fillable = ['menu', 'libelle_fr', 'libelle_en', 'cible', 'ordre', 'visible'];

    protected $casts = ['visible' => 'boolean'];

    /**
     * Les menus editables, et leur intitule.
     *
     * @return array<string, array{intitule: string, aide: string}>
     */
    public static function menus(): array
    {
        return [
            'principal' => [
                'intitule' => __('Menu principal'),
                'aide' => __('Barre de navigation en haut de chaque page'),
            ],
            'pied_navigation' => [
                'intitule' => __('Pied de page — colonne « Navigation »'),
                'aide' => __('Deuxième colonne du pied de page'),
            ],
            'pied_legal' => [
                'intitule' => __('Liens légaux du bas de page'),
                'aide' => __('Ligne du bas, à côté de la mention de copyright'),
            ],
        ];
    }

    public static function menuConnu(string $menu): bool
    {
        return array_key_exists($menu, static::menus());
    }

    public function scopeDuMenu(Builder $requete, string $menu): Builder
    {
        return $requete->where('menu', $menu);
    }

    /** Libelle affiche, dans la langue demandee. */
    public function libelle(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle', $langue);
    }

    /**
     * Une cible est-elle acceptable dans un href ?
     *
     * Ce controle est une PROTECTION, pas une commodite : la cible est saisie
     * en administration et ressort telle quelle dans un attribut href. Un
     * « javascript:… » y serait execute au clic du visiteur, sur toutes les
     * pages du site puisque le menu est partout.
     *
     * Trois formes acceptees, et rien d'autre :
     *   - un nom de route de l'application ;
     *   - un chemin interne, commençant par « / » mais pas par « // » — deux
     *     barres designent un autre domaine, ce qui n'est pas un chemin
     *     interne malgre les apparences ;
     *   - une adresse http ou https complete.
     */
    public static function cibleAcceptable(?string $cible): bool
    {
        $cible = trim((string) $cible);

        if ($cible === '') {
            return false;
        }

        if (Route::has($cible)) {
            return true;
        }

        if (str_starts_with($cible, '//')) {
            return false;
        }

        if (str_starts_with($cible, '/')) {
            return true;
        }

        return (bool) preg_match('#^https?://#i', $cible);
    }

    /**
     * L'adresse a mettre dans le href.
     *
     * Rend « # » plutot qu'une cible douteuse : une entree devenue invalide —
     * route renommee, donnee ancienne — doit degrader le lien, jamais le
     * rendre dangereux.
     */
    public function lien(): string
    {
        $cible = trim((string) $this->cible);

        if (Route::has($cible)) {
            return route($cible);
        }

        return static::cibleAcceptable($cible) ? $cible : '#';
    }

    /**
     * Cette entree designe-t-elle la page affichee ?
     *
     * La classe « active » etait posee en dur sur « Actualites » dans le
     * gabarit, si bien que ce lien s'affichait actif sur TOUTES les pages. La
     * comparaison porte sur le CHEMIN seul : l'adresse courante peut porter
     * une chaine de requete ou un fragment que la cible n'a pas.
     */
    public function estCourante(): bool
    {
        $cible = parse_url($this->lien(), PHP_URL_PATH) ?: '/';
        $courant = '/'.trim(request()->path(), '/');

        return rtrim($cible, '/') === rtrim($courant, '/')
            || ($cible === '/' && $courant === '/');
    }

    /** Le rang suivant DANS SON MENU. */
    public static function rangSuivantDans(string $menu): int
    {
        return ((int) static::query()->where('menu', $menu)->max('ordre')) + 1;
    }

    /**
     * Renumerote un menu dans l'ordre recu.
     *
     * Seuls les identifiants appartenant REELLEMENT a ce menu sont retenus :
     * ceux-ci viennent du navigateur, et l'un d'eux pourrait designer une
     * entree d'un autre menu.
     */
    public static function reordonnerDans(string $menu, array $idsDansLOrdre): void
    {
        $idsDansLOrdre = array_values(array_unique($idsDansLOrdre));

        $legitimes = static::query()
            ->where('menu', $menu)
            ->whereIn('id', $idsDansLOrdre)
            ->pluck('id')
            ->all();

        $rang = 0;

        DB::transaction(function () use ($idsDansLOrdre, $legitimes, &$rang) {
            foreach ($idsDansLOrdre as $id) {
                if (! in_array($id, $legitimes)) {
                    continue;
                }

                static::query()->whereKey($id)->update(['ordre' => ++$rang]);
            }
        });
    }
}
