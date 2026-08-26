<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Partenaire affiche sur l'accueil.
 *
 * Aucun champ traduisible : un nom d'organisation, un logo et une adresse.
 * `site` est facultatif — deux des sept partenaires repris du site n'en ont
 * pas, et leur carte n'est alors pas un lien.
 */
class Partenaire extends Model
{
    use CollectionOrdonnable;
    use HasFactory;

    protected $table = 'partenaires';

    protected $fillable = ['ordre', 'visible', 'nom', 'logo', 'site'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Le logo doit-il etre presente comme un lien ? */
    public function aUnSite(): bool
    {
        return filled($this->site);
    }
}
