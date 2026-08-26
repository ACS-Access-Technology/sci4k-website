<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Temoignage affiche sur l'accueil.
 *
 * `auteur` et `initiales` n'ont pas de colonne par langue : ce sont des noms
 * propres, identiques en francais et en anglais. Leur donner deux colonnes
 * aurait invite a inventer un second nom pour la meme personne.
 */
class Temoignage extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'temoignages';

    protected $fillable = ['ordre', 'visible', 'auteur', 'initiales', 'note', 'citation_fr', 'citation_en', 'role_fr', 'role_en'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer', 'note' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true, 'note' => 5];

    /** Texte du temoignage. */
    public function citation(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('citation', $langue);
    }

    /** Fonction ou quartier de l'auteur. */
    public function role(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('role', $langue);
    }
}
