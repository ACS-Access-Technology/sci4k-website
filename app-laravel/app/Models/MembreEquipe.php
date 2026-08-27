<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Membre de l'equipe, affiche sur la page de presentation.
 *
 * `nom` est un nom propre : une seule colonne, comme pour les temoignages.
 */
class MembreEquipe extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'membres_equipe';

    protected $fillable = ['ordre', 'visible', 'nom', 'photo', 'linkedin', 'email', 'etiquette_fr', 'etiquette_en', 'fonction_fr', 'fonction_en', 'biographie_fr', 'biographie_en'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Etiquette du bandeau, par exemple « Direction ». */
    public function etiquette(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('etiquette', $langue);
    }

    /** Intitule du poste. */
    public function fonction(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('fonction', $langue);
    }

    /** Presentation courte. */
    public function biographie(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('biographie', $langue);
    }
}
