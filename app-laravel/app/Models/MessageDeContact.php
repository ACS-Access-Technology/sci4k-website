<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un message recu par le formulaire de contact du site.
 *
 * PAS de trait de journalisation : le journal des activites rend compte de ce
 * que font les COMPTES du backoffice. Un message arrive du site public, sans
 * personne connectee, et inscrirait une ligne « Import ou tache automatique »
 * a chaque visiteur — le journal se remplirait de bruit et cesserait de servir.
 * Cet ecran-ci est deja le registre des messages.
 */
class MessageDeContact extends Model
{
    use HasFactory;

    protected $table = 'messages_de_contact';

    /**
     * Volontairement ETROIT : il ne contient que ce que le visiteur saisit.
     *
     * `statut`, `assigne_a` et `repondu_a` en sont absents parce que le point
     * d'entree public ecrit sur ce modele. Les y mettre aurait suffi qu'une
     * regle de validation soit relachee un jour pour qu'un inconnu classe ses
     * propres messages comme « traites » ou se les assigne. L'administration
     * les ecrit par affectation directe, ce qui nomme explicitement qui en a
     * le droit.
     */
    protected $fillable = ['nom', 'email', 'telephone', 'sujet', 'message', 'source'];

    protected $casts = ['repondu_a' => 'datetime'];

    /** Le formulaire d'ou vient le message. */
    public const DE_CONTACT = 'contact';

    public const DE_FAQ = 'faq';

    /**
     * @return array<string, string>
     */
    public static function sources(): array
    {
        return [
            self::DE_CONTACT => __('Formulaire de contact'),
            self::DE_FAQ => __('Question posée depuis la FAQ'),
        ];
    }

    public const NOUVEAU = 'nouveau';

    public const EN_COURS = 'en_cours';

    public const TRAITE = 'traite';

    public const ARCHIVE = 'archive';

    /**
     * Les etats d'un message, et leur intitule.
     *
     * @return array<string, string>
     */
    public static function statuts(): array
    {
        return [
            self::NOUVEAU => __('Nouveau'),
            self::EN_COURS => __('En cours'),
            self::TRAITE => __('Traité'),
            self::ARCHIVE => __('Archivé'),
        ];
    }

    public static function statutConnu(string $statut): bool
    {
        return array_key_exists($statut, static::statuts());
    }

    public function assigne()
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function scopeRecents(Builder $requete): Builder
    {
        return $requete->latest('created_at')->latest('id');
    }

    /** Ceux qui n'ont pas encore ete ouverts. */
    public function scopeNonLus(Builder $requete): Builder
    {
        return $requete->where('statut', self::NOUVEAU);
    }

    /** Intitule affiche : le sujet, ou le debut du message a defaut. */
    public function intitule(): string
    {
        if (is_string($this->sujet) && trim($this->sujet) !== '') {
            return trim($this->sujet);
        }

        return Str::limit(trim($this->message), 60);
    }

    /** Initiales de l'expediteur, pour la vignette. */
    public function initiales(): string
    {
        return collect(preg_split('/\s+/u', trim((string) $this->nom)))
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->implode('') ?: '?';
    }

    /**
     * Delai moyen de reponse, en heures, sur les messages deja traites.
     *
     * Rend null quand aucun message n'a encore recu de reponse : afficher
     * « 0 h » laisserait croire a une reponse instantanee, la ou il n'y a
     * simplement rien a mesurer.
     */
    public static function delaiMoyenDeReponse(): ?float
    {
        $repondus = static::query()->whereNotNull('repondu_a')->get(['created_at', 'repondu_a']);

        if ($repondus->isEmpty()) {
            return null;
        }

        return round($repondus->avg(fn ($m) => $m->created_at->diffInMinutes($m->repondu_a)) / 60, 1);
    }
}
