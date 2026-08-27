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

    protected $fillable = ['slug', 'ordre', 'visible', 'etiquette_fr', 'etiquette_en', 'titre_fr', 'titre_en', 'texte_fr', 'texte_en', 'libelle_bouton_fr', 'libelle_bouton_en', 'cible_bouton', 'image_source'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

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
}
