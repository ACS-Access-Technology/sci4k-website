<?php

namespace App\Models;

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
    use TraduitParColonnes;

    protected $table = 'chiffres_cles';

    protected $fillable = ['ordre', 'valeur', 'intitule_fr', 'intitule_en'];

    protected $casts = ['ordre' => 'integer', 'valeur' => 'integer'];

    protected $attributes = ['ordre' => 0, 'valeur' => 0];

    /** Libelle affiche sous le nombre. */
    public function intitule(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('intitule', $langue);
    }
}
