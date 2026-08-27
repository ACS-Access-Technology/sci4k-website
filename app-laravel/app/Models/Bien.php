<?php

namespace App\Models;

use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un bien du catalogue.
 */
class Bien extends Model
{
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'biens';

    protected $fillable = [
        'reference', 'slug',
        'titre_fr', 'titre_en', 'sous_titre_fr', 'sous_titre_en',
        'accroche_fr', 'accroche_en', 'description_fr', 'description_en',
        'type', 'offre', 'zone', 'statut_juridique', 'numero_titre', 'quartier',
        'prix', 'prix_unite',
        'surface_habitable', 'surface_terrain',
        'nombre_pieces', 'nombre_chambres', 'nombre_salles_eau',
        'equipements',
        'meta_titre_fr', 'meta_titre_en', 'meta_description_fr', 'meta_description_en',
        'statut', 'date_mise_en_ligne', 'en_avant', 'urgent', 'auteur_id', 'ordre',
    ];

    protected $casts = [
        'equipements' => 'array',
        'date_mise_en_ligne' => 'date',
        'en_avant' => 'boolean',
        'urgent' => 'boolean',
    ];

    /* --------------------------------------------------- vocabulaire */

    public const VENTE = 'vente';

    public const LOCATION = 'location';

    public const PUBLIE = 'publie';

    public const BROUILLON = 'brouillon';

    public const VENDU = 'vendu';

    public const ARCHIVE = 'archive';

    /** @return array<string, string> */
    public static function offres(): array
    {
        return [self::VENTE => __('Vente'), self::LOCATION => __('Location')];
    }

    /** @return array<string, string> */
    public static function statuts(): array
    {
        return [
            self::PUBLIE => __('Publié'),
            self::BROUILLON => __('Brouillon'),
            self::VENDU => __('Vendu'),
            self::ARCHIVE => __('Archivé'),
        ];
    }

    /** @return array<string, string> */
    public static function unitesDePrix(): array
    {
        return [
            'total' => __('au total'),
            'm2' => __('le m²'),
            'mois' => __('par mois'),
        ];
    }

    /* --------------------------------------------------- relations */

    public function photos()
    {
        return $this->hasMany(PhotoDeBien::class)->orderBy('ordre')->orderBy('id');
    }

    public function auteur()
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /* --------------------------------------------------- portees */

    /** Ce que le visiteur voit. Un bien vendu reste visible, mais marque. */
    public function scopePublies(Builder $requete): Builder
    {
        return $requete->whereIn('statut', [self::PUBLIE, self::VENDU]);
    }

    public function scopeOrdonnes(Builder $requete): Builder
    {
        return $requete->orderByDesc('en_avant')->orderBy('ordre')->orderByDesc('id');
    }

    /* --------------------------------------------------- textes */

    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    public function sousTitre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('sous_titre', $langue);
    }

    public function accroche(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('accroche', $langue);
    }

    public function description(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('description', $langue);
    }

    /**
     * Les equipements dans la langue demandee.
     *
     * @return list<string>
     */
    public function equipements(string $langue = 'fr'): array
    {
        $listes = $this->equipements ?? [];

        return $listes[$langue] ?? $listes['fr'] ?? [];
    }

    /* --------------------------------------------------- tranches */

    /**
     * Bornes des tranches de surface, telles que le site les emploie.
     *
     * @return array<string, array{0: int|null, 1: int|null}>
     */
    public static function bornesDeSurface(): array
    {
        return [
            's1' => [null, 99],
            's2' => [100, 250],
            's3' => [251, 500],
            's4' => [501, null],
        ];
    }

    /**
     * La tranche de surface, CALCULEE et non stockee.
     *
     * Le site gardait la tranche a cote du texte : deux verites pour une seule
     * information, et rien n'empechait de classer un 310 m² sous « moins de
     * 100 m² ». La deduire garantit qu'elles ne peuvent pas diverger, et
     * qu'elargir une tranche reclasse les biens d'eux-memes.
     */
    public function trancheDeSurface(): ?string
    {
        $surface = $this->surface_habitable ?? $this->surface_terrain;

        if ($surface === null) {
            return null;
        }

        foreach (static::bornesDeSurface() as $cle => [$min, $max]) {
            if (($min === null || $surface >= $min) && ($max === null || $surface <= $max)) {
                return $cle;
            }
        }

        return null;
    }

    /**
     * La tranche de pieces, elle aussi calculee.
     *
     * Le filtre du site compare un NOMBRE et non une chaine : « 1 » signifie
     * deux pieces au plus, « 3 » de trois a quatre, « 5 » cinq et plus.
     */
    public function trancheDePieces(): ?string
    {
        if ($this->nombre_pieces === null) {
            return null;
        }

        return match (true) {
            $this->nombre_pieces <= 2 => '1',
            $this->nombre_pieces <= 4 => '3',
            default => '5',
        };
    }

    /**
     * Restreint aux biens d'une tranche de pieces.
     *
     * Un bien SANS nombre de pieces — un terrain — n'appartient a aucune
     * tranche : il est donc exclu des qu'on filtre la-dessus. Le site lui
     * posait 1, ce qui le faisait remonter dans « 1 a 2 pieces » ; un terrain
     * nu n'est pas un logement d'une piece.
     */
    public function scopeDeLaTrancheDePieces(Builder $requete, string $tranche): Builder
    {
        return match ($tranche) {
            '1' => $requete->whereNotNull('nombre_pieces')->where('nombre_pieces', '<=', 2),
            '3' => $requete->whereBetween('nombre_pieces', [3, 4]),
            '5' => $requete->where('nombre_pieces', '>=', 5),
            default => $requete,
        };
    }

    /** Restreint aux biens d'une tranche de surface. */
    public function scopeDeLaTrancheDeSurface(Builder $requete, string $tranche): Builder
    {
        $bornes = static::bornesDeSurface()[$tranche] ?? null;

        if (! $bornes) {
            return $requete;
        }

        [$min, $max] = $bornes;

        // La surface habitable prime, celle du terrain la remplace pour un
        // terrain nu — c'est la seule qu'il possede.
        $colonne = 'COALESCE(surface_habitable, surface_terrain)';

        return $requete
            ->when($min !== null, fn ($r) => $r->whereRaw("$colonne >= ?", [$min]))
            ->when($max !== null, fn ($r) => $r->whereRaw("$colonne <= ?", [$max]))
            ->whereRaw("$colonne IS NOT NULL");
    }

    /* --------------------------------------------------- affichage */

    /** Le bien est-il encore a prendre ? */
    public function estVendu(): bool
    {
        return $this->statut === self::VENDU;
    }

    /**
     * Prix formate, ou null quand il n'y en a pas.
     *
     * Le site n'affiche AUCUN prix aujourd'hui : l'agence les annonce de vive
     * voix. La methode existe pour le backoffice et pour le jour ou le site
     * changera d'avis — elle ne rend rien tant qu'aucun prix n'est saisi,
     * plutot qu'un « 0 FCFA » qui serait faux.
     */
    public function prixFormate(): ?string
    {
        if (! $this->prix) {
            return null;
        }

        $montant = number_format($this->prix, 0, ',', ' ').' FCFA';

        return match ($this->prix_unite) {
            'm2' => $montant.' / m²',
            'mois' => $montant.' / '.__('mois'),
            default => $montant,
        };
    }

    /** Nom lisible dans le journal des activites. */
    public function intituleJournal(): string
    {
        return $this->titre_fr ?: ('#'.$this->getKey());
    }
}
