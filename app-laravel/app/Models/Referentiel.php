<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Une valeur de liste deroulante du site public.
 *
 * Types de bien, zones, tranches de pieces, tranches de surface, statuts
 * juridiques : cinq familles dans une seule table, parce qu'elles ont
 * exactement la meme forme et le meme comportement.
 *
 * Deux familles de la maquette ne sont PAS ici : les categories d'articles et
 * les rubriques de FAQ. Elles ont deja leur table et leur ecran depuis les
 * lots 1 et 2a. Les redeclarer aurait cree deux sources pour la meme
 * information — le defaut exact releve deux fois au lot 2 avec les themes et
 * les langues, ou un mecanisme herite et un mecanisme ajoute ne se parlaient
 * pas. L'ecran les affiche donc en lecture, et renvoie vers leur propre page.
 */
class Referentiel extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'referentiels';

    protected $fillable = ['famille', 'valeur', 'libelle_fr', 'libelle_en', 'ordre', 'visible'];

    protected $casts = ['visible' => 'boolean'];

    /**
     * Les familles portees par cette table, et leur intitule.
     *
     * @return array<string, array{intitule: string, aide: string}>
     */
    public static function familles(): array
    {
        return [
            'types_de_bien' => [
                'intitule' => __('Types de bien'),
                'aide' => __('Filtre « type » de la page /biens'),
            ],
            'zones' => [
                'intitule' => __('Zones et communes'),
                'aide' => __('Filtre « zone » de la page /biens'),
            ],
            'tranches_pieces' => [
                'intitule' => __('Tranches de pièces'),
                'aide' => __('Filtre « pièces » de la page /biens'),
            ],
            'tranches_surface' => [
                'intitule' => __('Tranches de surface'),
                'aide' => __('Filtre « surface » de la page /biens'),
            ],
            'statuts_juridiques' => [
                'intitule' => __('Statuts juridiques'),
                'aide' => __("Liste déroulante de la fiche d'un bien"),
            ],
        ];
    }

    /** Cette famille est-elle portee par cette table ? */
    public static function familleConnue(string $famille): bool
    {
        return array_key_exists($famille, static::familles());
    }

    public function scopeDeLaFamille(Builder $requete, string $famille): Builder
    {
        return $requete->where('famille', $famille);
    }

    /** Libelle affiche, dans la langue demandee. */
    public function libelle(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle', $langue);
    }

    /**
     * Le rang suivant DANS SA FAMILLE.
     *
     * Le trait en fournit un global, inutilisable ici : il rendrait le rang
     * suivant toutes familles confondues, et la premiere zone ajoutee
     * porterait le rang qui suit le dernier statut juridique.
     */
    public static function rangSuivantDans(string $famille): int
    {
        return ((int) static::query()->where('famille', $famille)->max('ordre')) + 1;
    }

    /**
     * Renumerote une famille dans l'ordre recu.
     *
     * Meme raison : reordonner() du trait reecrit la table entiere. Les
     * identifiants viennent du navigateur, donc seuls ceux qui appartiennent
     * REELLEMENT a cette famille sont retenus — sans quoi un identifiant
     * emprunte a une autre famille s'y verrait deplacer.
     */
    public static function reordonnerDans(string $famille, array $idsDansLOrdre): void
    {
        $idsDansLOrdre = array_values(array_unique($idsDansLOrdre));

        $legitimes = static::query()
            ->where('famille', $famille)
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
