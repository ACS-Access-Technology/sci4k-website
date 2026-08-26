<?php

namespace App\Models;

use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une des quatre valeurs affichees sur la page de presentation.
 *
 * Petit ensemble fige : on modifie ce qui existe, sans creation ni
 * suppression. Un tableau pagine avec recherche et filtres pour quatre lignes
 * couterait trois clics pour changer un mot.
 */
class Valeur extends Model
{
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'valeurs';

    protected $fillable = ['ordre', 'titre_fr', 'titre_en', 'texte_fr', 'texte_en'];

    protected $casts = ['ordre' => 'integer'];

    protected $attributes = ['ordre' => 0];

    /** Titre de la valeur. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Description de la valeur. */
    public function texte(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte', $langue);
    }
}
