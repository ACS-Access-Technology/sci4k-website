<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    /** Texte de remplacement, lu par les lecteurs d'ecran. */
    public function texteAlternatif(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte_alternatif', $langue);
    }
}
