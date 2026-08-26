<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Rubrique de la FAQ : le titre sous lequel un groupe de questions s'affiche.
 *
 * La question pointait auparavant un service, les six groupes du site
 * correspondant aux six metiers. Cette coincidence avait ete prise pour une
 * regle, et interdisait d'ouvrir une rubrique transverse — « Paiements »,
 * « Juridique » — sans creer un service du meme nom, avec sa tuile et sa photo.
 *
 * Une rubrique ne porte donc que ce qu'un titre de groupe demande : un nom
 * dans les deux langues, un rang, et de quoi la masquer.
 */
class RubriqueFaq extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $table = 'rubriques_faq';

    protected $fillable = ['slug', 'ordre', 'visible', 'nom_fr', 'nom_en'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Nom de la rubrique dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionFaq::class, 'rubrique_id');
    }

    /**
     * Un slug derive du nom, encore libre.
     *
     * La colonne est unique. Un editeur qui saisit deux fois « Paiements »
     * n'attend pas une erreur de base de donnees mais deux rubriques : le
     * second recoit donc « paiements-2 ». Le repli sur « rubrique » couvre un
     * nom qui ne laisserait aucun caractere apres translitteration, un nom
     * entierement compose d'ideogrammes par exemple.
     */
    public static function slugLibrePour(string $nom): string
    {
        $base = Str::slug($nom) ?: 'rubrique';
        $slug = $base;
        $suffixe = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.++$suffixe;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
