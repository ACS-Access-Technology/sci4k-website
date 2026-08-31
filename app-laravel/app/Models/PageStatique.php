<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageStatique extends Model
{
    protected $table = 'pages_statiques';

    protected $fillable = ['slug', 'titre_fr', 'titre_en', 'contenu_fr', 'contenu_en', 'publie'];

    protected $casts = ['publie' => 'boolean'];

    public function titre(string $langue = 'fr'): string
    {
        return $this->{'titre_'.$langue} ?: $this->titre_fr;
    }

    public function contenu(string $langue = 'fr'): string
    {
        return $this->{'contenu_'.$langue} ?: $this->contenu_fr;
    }
}
