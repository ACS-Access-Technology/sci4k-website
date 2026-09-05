<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Image de fond d'une section du site.
 *
 * Le slug reprend la variable CSS (--img-{slug}) : c'est lui qui relie une
 * image a l'endroit qui l'affiche. Le nom du FICHIER, lui, ne se deduit pas du
 * slug — leçon du lot 2a, ou le service « gestion » s'appuyait sur
 * gestion-location.jpg.
 */
class ImageDeFond extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'images_de_fond';

    protected $fillable = ['ordre', 'visible', 'slug', 'fichier', 'texte_alternatif_fr', 'texte_alternatif_en'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /**
     * Les emplacements servis par une balise <img>, et non par un fond CSS.
     *
     * La table s'appelle « images de fond » parce qu'elle n'a d'abord servi
     * qu'a des variables --img-{slug}. Ces deux-la sont des illustrations
     * posees dans le HTML de la page Presentation : elles vivent au meme
     * endroit parce que l'editeur les cherche au meme endroit, mais elles
     * n'ont ni les memes dimensions utiles ni le voile sombre applique aux
     * fonds. L'ecran d'edition le dit, plutot que d'afficher a tous la meme
     * consigne dont la moitie serait fausse.
     */
    public const VISUELS_EN_LIGNE = [
        'presentation-apercu',
        'presentation-directeur',
    ];

    public function estVisuelEnLigne(): bool
    {
        return in_array($this->slug, self::VISUELS_EN_LIGNE, true);
    }

    /**
     * Les images visibles portant ces slugs, indexees par slug.
     *
     * Rend une collection VIDE si la table n'existe pas encore — meme raison
     * qu'AppServiceProvider : le site doit rester servable pendant une
     * migration. Une page sans illustration se lit ; une exception non.
     *
     * @param  list<string>  $slugs
     *                               Le type de retour est celui d'Eloquent et non celui du support : c'est
     *                               ce que `get()->keyBy()` produit reellement, et l'annoncer autrement
     *                               obligeait a une conversion que personne ne faisait.
     * @return EloquentCollection<string, static>
     */
    public static function parSlugs(array $slugs): EloquentCollection
    {
        try {
            // Pas de garde sur « fichier » : la colonne est NOT NULL. Un
            // chemin vide reste possible, et c'est le gabarit qui retombe
            // alors sur le fichier d'origine.
            return static::query()
                ->whereIn('slug', $slugs)
                ->where('visible', true)
                ->get()
                ->keyBy('slug');
        } catch (\Throwable) {
            // Une collection VIDE du meme type : `collect()` en rend une du
            // support, que la signature refuse.
            return new EloquentCollection;
        }
    }

    /** Texte de remplacement, lu par les lecteurs d'ecran. */
    public function texteAlternatif(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte_alternatif', $langue);
    }
}
