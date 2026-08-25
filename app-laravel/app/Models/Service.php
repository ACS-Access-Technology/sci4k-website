<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    protected $fillable = [
        'slug', 'categorie_id', 'ordre', 'visible',
        'nom_fr', 'nom_en', 'accroche_fr', 'accroche_en',
        'description_fr', 'description_en',
        'atout1_fr', 'atout1_en', 'atout2_fr', 'atout2_en', 'atout3_fr', 'atout3_en',
        'libelle_bouton_fr', 'libelle_bouton_en',
        'icone_svg', 'image_source',
    ];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Nom du service dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    /** Phrase courte affichee sur la tuile. */
    public function accroche(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('accroche', $langue);
    }

    /** Texte long affiche dans la fiche du service. */
    public function description(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('description', $langue);
    }

    /** Libelle du bouton d'appel a l'action, repli sur une valeur traduite. */
    public function libelleBouton(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle_bouton', $langue) ?: __('En savoir plus');
    }

    /**
     * Les atouts, dans l'ordre, sans les emplacements laisses vides.
     *
     * Trois colonnes plutot qu'une table dediee : le site en affiche
     * exactement trois depuis toujours, et une table pour trois valeurs fixes
     * couterait une jointure a chaque affichage pour aucun gain.
     */
    public function atouts(string $langue = 'fr'): array
    {
        return array_values(array_filter([
            $this->texteDansLaLangue('atout1', $langue),
            $this->texteDansLaLangue('atout2', $langue),
            $this->texteDansLaLangue('atout3', $langue),
        ], fn ($a) => $a !== ''));
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function questionsFaq()
    {
        return $this->hasMany(QuestionFaq::class, 'service_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
