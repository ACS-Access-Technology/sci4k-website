<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $fillable = ['slug', 'nom_fr', 'nom_en', 'ordre'];

    /** Nom dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $langue === 'en' ? $this->nom_en : $this->nom_fr;
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }
}
