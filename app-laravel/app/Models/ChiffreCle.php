<?php

namespace App\Models;

use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un des trois chiffres du bandeau d'accueil.
 *
 * `valeur` est le nombre que le compteur anime jusqu'a lui (data-target).
 */
class ChiffreCle extends Model
{
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'chiffres_cles';

    protected $fillable = ['ordre', 'visible', 'valeur', 'suffixe', 'intitule_fr', 'intitule_en', 'note_interne'];

    protected $casts = ['ordre' => 'integer', 'visible' => 'boolean', 'valeur' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true, 'valeur' => 0, 'suffixe' => ''];

    /** Libelle affiche sous le nombre. */
    /** Le nombre tel que le site l'affiche, suffixe compris. */
    public function affichage(): string
    {
        return $this->valeur.$this->suffixe;
    }

    public function intitule(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('intitule', $langue);
    }
}
