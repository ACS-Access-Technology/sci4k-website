<?php

namespace App\Models;

use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    use TraduitParColonnes;

    protected $fillable = [
        'slug', 'categorie_id', 'date_publication', 'statut',
        'titre_fr', 'titre_en', 'resume_fr', 'resume_en',
        'contenu_fr', 'contenu_en',
        'image_source',
        'meta_titre_fr', 'meta_titre_en',
        'meta_description_fr', 'meta_description_en',
    ];

    protected $casts = ['date_publication' => 'date'];

    /** Articles visibles du public, du plus recent au plus ancien. */
    public function scopePublies(Builder $requete): Builder
    {
        return $requete->where('statut', 'publie')
            ->orderByDesc('date_publication');
    }

    /** Titre dans la langue demandee, francais par defaut. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Resume dans la langue demandee, francais par defaut. */
    public function resume(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('resume', $langue);
    }

    /** Contenu dans la langue demandee, francais par defaut. */
    public function contenu(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('contenu', $langue);
    }

    /**
     * Titre destine aux moteurs, dans la langue demandee.
     *
     * Replie sur le titre de l'article quand la meta n'est pas renseignee : le
     * trait ne replie que d'une langue vers l'autre, pas d'un champ vers un
     * autre.
     */
    public function metaTitre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('meta_titre', $langue) ?: $this->titre($langue);
    }

    /** Description destinee aux moteurs, repliee sur le resume si absente. */
    public function metaDescription(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('meta_description', $langue) ?: $this->resume($langue);
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
