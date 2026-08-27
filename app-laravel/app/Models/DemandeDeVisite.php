<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un rendez-vous demande depuis la fiche d'un bien.
 *
 * PAS de trait de journalisation, meme raison que pour les messages de
 * contact : le journal rend compte de ce que font les COMPTES du backoffice.
 * Une demande arrive du site public, sans personne connectee.
 */
class DemandeDeVisite extends Model
{
    use HasFactory;

    protected $table = 'demandes_de_visite';

    /**
     * Etroit, comme pour les messages : `statut` et `assigne_a` en sont
     * absents parce que le point d'entree PUBLIC ecrit sur ce modele. Un
     * visiteur ne doit pas pouvoir marquer sa propre demande « realisee » ni
     * se l'attribuer.
     */
    protected $fillable = ['nom', 'telephone', 'email', 'message', 'bien_id', 'bien_intitule', 'creneau_souhaite'];

    protected $casts = ['creneau_souhaite' => 'datetime'];

    public const A_CONFIRMER = 'a_confirmer';

    public const CONFIRMEE = 'confirmee';

    public const REALISEE = 'realisee';

    public const ANNULEE = 'annulee';

    /** @return array<string, string> */
    public static function statuts(): array
    {
        return [
            self::A_CONFIRMER => __('À confirmer'),
            self::CONFIRMEE => __('Confirmée'),
            self::REALISEE => __('Réalisée'),
            self::ANNULEE => __('Annulée'),
        ];
    }

    public static function statutConnu(string $statut): bool
    {
        return array_key_exists($statut, static::statuts());
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function assigne()
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function scopeRecentes(Builder $requete): Builder
    {
        return $requete->latest('created_at')->latest('id');
    }

    public function scopeAConfirmer(Builder $requete): Builder
    {
        return $requete->where('statut', self::A_CONFIRMER);
    }

    /**
     * Le bien concerne, tel qu'il doit s'afficher.
     *
     * Le titre recopie prend le relais quand le bien a ete retire du
     * catalogue : une demande de visite garde son sens apres la vente, et
     * afficher un vide effacerait ce dont il etait question.
     */
    public function bienLisible(string $langue = 'fr'): ?string
    {
        return $this->bien?->titre($langue) ?: $this->bien_intitule;
    }

    /** Initiales du demandeur, pour la vignette. */
    public function initiales(): string
    {
        return collect(preg_split('/\s+/u', trim((string) $this->nom)))
            ->filter()->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->implode('') ?: '?';
    }

    /**
     * Part des demandes qui se sont conclues par une visite.
     *
     * Rend null quand rien n'est encore sorti du statut « a confirmer » :
     * afficher « 0 % » laisserait croire a un echec, la ou il n'y a rien a
     * mesurer.
     */
    public static function tauxDeConcretisation(): ?float
    {
        $traitees = static::whereIn('statut', [self::REALISEE, self::ANNULEE])->count();

        if ($traitees === 0) {
            return null;
        }

        return round(static::where('statut', self::REALISEE)->count() / $traitees * 100);
    }
}
