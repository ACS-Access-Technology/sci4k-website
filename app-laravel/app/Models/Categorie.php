<?php

namespace App\Models;

use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Categorie extends Model
{
    use HasFactory;
    use TraduitParColonnes;

    protected $fillable = ['slug', 'nom_fr', 'nom_en', 'ordre'];

    /**
     * Le slug se deduit du nom francais quand il n'est pas fourni.
     *
     * La colonne est unique et non nulle, mais le site public ne s'en sert
     * nulle part : le filtre de /actualites compare les NOMS traduits. Le
     * demander a l'editeur reviendrait a lui faire saisir une valeur
     * technique que personne ne lit. Les seeders, eux, continuent de le poser
     * explicitement — c'est leur cle d'identite — et ce crochet ne touche
     * jamais a un slug deja rempli.
     */
    protected static function booted(): void
    {
        static::saving(function (self $categorie) {
            if (trim((string) $categorie->slug) !== '') {
                return;
            }

            $base = Str::slug($categorie->nom_fr) ?: 'categorie';
            $slug = $base;
            $suffixe = 1;

            // Deux categories peuvent porter le meme nom : sans ce suffixe,
            // la seconde heurterait l'index unique et l'enregistrement
            // echouerait sur une erreur SQL brute.
            //
            // `whereKeyNot` n'est pose que sur une categorie deja en base :
            // sur une creation la cle est nulle, et « id <> NULL » ne
            // ramenerait aucune ligne — le doublon passerait au travers.
            $occupe = fn (string $candidat) => static::where('slug', $candidat)
                ->when($categorie->exists, fn ($r) => $r->whereKeyNot($categorie->getKey()))
                ->exists();

            while ($occupe($slug)) {
                $slug = $base.'-'.(++$suffixe);
            }

            $categorie->slug = $slug;
        });
    }

    /** Nom dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    /** @return HasMany<Article, $this> */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'categorie_id');
    }

    /** @return HasMany<Service, $this> */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'categorie_id');
    }

    /**
     * Nombre de contenus qui dependent de cette categorie.
     *
     * Les deux tables la referencent par cle etrangere contrainte : la
     * supprimer alors qu'elle sert encore ferait remonter une erreur SQL
     * brute au lieu d'une explication.
     */
    public function nombreDeContenus(): int
    {
        return $this->articles()->count() + $this->services()->count();
    }
}
