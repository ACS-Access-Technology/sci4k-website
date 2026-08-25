<?php

namespace App\Models;

use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    use TraduitParColonnes;

    protected $fillable = ['slug', 'nom_fr', 'nom_en', 'ordre'];

    /** Nom dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }
}
