<?php

namespace App\Models;

use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    /**
     * Prefixe des couvertures televersees, tel qu'il figure dans
     * `image_source`. Il distingue un fichier depose par l'administration,
     * qu'on peut effacer, d'une couverture du site statique, qu'on ne doit
     * jamais toucher.
     */
    public const DOSSIER_COUVERTURES = 'storage/actualites';

    protected $fillable = [
        'slug', 'categorie_id', 'auteur_id', 'date_publication', 'statut',
        'titre_fr', 'titre_en', 'resume_fr', 'resume_en',
        'contenu_fr', 'contenu_en',
        'image_source',
        'meta_titre_fr', 'meta_titre_en',
        'meta_description_fr', 'meta_description_en',
        'vues',
        'commentaires_ouverts',
    ];

    protected $casts = [
        'date_publication' => 'date',
        'vues' => 'integer',
        'commentaires_ouverts' => 'boolean',
    ];

    /**
     * La base pose deja ce defaut, mais pas l'objet : un article tout juste
     * cree renvoyait null plutot que 0 tant qu'on ne l'avait pas relu.
     */
    protected $attributes = ['vues' => 0, 'commentaires_ouverts' => true];

    /** Les trois etats possibles d'un article, dans l'ordre du cycle de vie. */
    public const STATUTS = ['brouillon', 'publie', 'archive'];

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

    /**
     * Adresse de la couverture, ou null si l'article n'en a pas.
     *
     * Deux origines cohabitent dans `image_source`, et c'est voulu :
     *
     *   - `images/actualites/article-1.jpg` pour les douze articles repris du
     *     site, dont les fichiers vivent dans frontoffice/ et sont deposes
     *     dans public/ par tools/sync-frontoffice.sh ;
     *   - `storage/actualites/…` pour les couvertures televersees depuis
     *     l'administration.
     *
     * Les vues n'ont ainsi qu'un seul point d'appel, et le repli est teste une
     * fois pour toutes plutot que reecrit dans chaque gabarit.
     */
    public function urlCouverture(): ?string
    {
        if (! $this->image_source) {
            return null;
        }

        return asset($this->image_source);
    }

    /** Le fichier de couverture a-t-il ete televerse depuis l'administration ? */
    public function couvertureTeleversee(): bool
    {
        return str_starts_with((string) $this->image_source, self::DOSSIER_COUVERTURES.'/');
    }

    /** Tous les commentaires, quel que soit leur statut. */
    public function commentaires()
    {
        return $this->hasMany(Commentaire::class);
    }

    /** Les commentaires en ligne, reponses comprises. */
    public function commentairesPublies()
    {
        return $this->commentaires()->publies();
    }

    public function categorie()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    /**
     * Le compte qui a redige l'article.
     *
     * Nullable : les douze articles importes du site n'ont pas d'auteur connu,
     * et leur en inventer un aurait attribue a quelqu'un un texte qu'il n'a pas
     * ecrit. Nul aussi quand l'auteur a quitte l'entreprise — supprimer son
     * compte ne retire pas ses articles du site.
     */
    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /** Les articles rediges par ce compte. */
    public function scopeDeLAuteur($requete, int $id)
    {
        return $requete->where('auteur_id', $id);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
