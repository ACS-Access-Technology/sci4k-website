<?php

namespace App\Models;

use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une des quatre etapes de la methode, affichee sur /services.
 *
 * Ecrites en dur dans la vue au lot 2a : cette table les rend editables sans
 * qu'il faille reporter la page, qui est deja servie depuis Laravel.
 */
class EtapeProcessus extends Model
{
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'etapes_processus';

    protected $fillable = ['ordre', 'visible', 'titre_fr', 'titre_en', 'texte_fr', 'texte_en'];

    protected $casts = ['ordre' => 'integer', 'visible' => 'boolean'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Titre de l'etape. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Description de l'etape. */
    public function texte(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte', $langue);
    }
}
