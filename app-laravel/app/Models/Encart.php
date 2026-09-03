<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Encart d'appel a l'action — la banderole de l'accueil, aujourd'hui.
 *
 * Le slug designe l'endroit du site qui l'affiche, comme pour les services.
 */
class Encart extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'encarts';

    protected $fillable = ['slug', 'ordre', 'visible', 'etiquette_fr', 'etiquette_en', 'titre_fr', 'titre_en', 'texte_fr', 'texte_en', 'libelle_bouton_fr', 'libelle_bouton_en', 'cible_bouton', 'image_source', 'diffusion_de', 'diffusion_a', 'impressions'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer', 'diffusion_de' => 'datetime', 'diffusion_a' => 'datetime', 'impressions' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (self $modele) {
            foreach (['diffusion_de', 'diffusion_a'] as $champ) {
                $brut = $modele->getAttributes()[$champ] ?? null;
                if (is_string($brut) && $brut === '') {
                    $modele->setAttribute($champ, null);
                }
            }
        });
    }

    public function estDiffuse(): bool
    {
        return $this->visible
            && (! $this->diffusion_de || $this->diffusion_de->isPast())
            && (! $this->diffusion_a || $this->diffusion_a->isFuture());
    }

    /** Etiquette au-dessus du titre. */
    public function etiquette(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('etiquette', $langue);
    }

    /** Titre de l'encart. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Corps de l'encart. */
    public function texte(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte', $langue);
    }

    /** Libelle du bouton d'appel a l'action. */
    public function libelleBouton(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle_bouton', $langue);
    }

    /**
     * Adresse du visuel, ou null si l'encart n'en a pas.
     *
     * Meme forme que Article::urlCouverture() et Service::urlImage() : deux
     * origines cohabitent dans `image_source` — un chemin `images/…` pour les
     * visuels repris du site statique, `storage/encarts/…` pour ceux
     * televerses depuis l'administration — et les vues n'ont qu'un point
     * d'appel.
     *
     * Il manquait, et avec lui tout rendu : le formulaire acceptait une image,
     * la stockait et l'affichait en apercu, mais aucune page publique ne la
     * lisait. L'accueil montrait un visuel FIGE par une classe CSS, la page
     * Services n'en montrait aucun.
     */
    public function urlImage(): ?string
    {
        if (! $this->image_source) {
            return null;
        }

        return asset($this->image_source);
    }
}
