<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Une commune du bandeau defilant de la page d'accueil.
 *
 * Pas de traduction : « Bingerville » s'ecrit pareil dans les deux langues.
 */
class CommuneDuBandeau extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use JournaliseSesChangements;

    protected $table = 'communes_du_bandeau';

    protected $fillable = ['nom', 'ordre', 'visible'];

    protected $casts = ['visible' => 'boolean'];

    /** Slug de l'en-tete de section qui porte les reglages du bandeau. */
    public const SECTION = 'home.marquee';
}
