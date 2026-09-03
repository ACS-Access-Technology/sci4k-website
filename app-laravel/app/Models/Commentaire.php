<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un commentaire depose sous un article.
 *
 * Il parait immediatement — le client l'a voulu ainsi — mais il passe d'abord
 * par un filtre qui met de cote ce qui ressemble a du courrier indesirable.
 * C'est la « moderation independante » demandee : elle travaille sans qu'un
 * humain soit devant l'ecran, et l'ecran de moderation ne sert plus qu'a
 * trancher les cas que la machine n'a pas su juger.
 */
class Commentaire extends Model
{
    use HasFactory;

    protected $table = 'commentaires';

    protected $fillable = [
        'article_id', 'parent_id', 'auteur', 'email', 'message',
        'statut', 'motif_de_mise_en_attente', 'adresse_ip',
    ];

    /* --------------------------------------------------------- statuts */

    public const PUBLIE = 'publie';

    public const EN_ATTENTE = 'en_attente';

    public const REJETE = 'rejete';

    /** @return array<string, string> */
    public static function statuts(): array
    {
        return [
            self::PUBLIE => __('Publié'),
            self::EN_ATTENTE => __('En attente'),
            self::REJETE => __('Rejeté'),
        ];
    }

    public function statutLisible(): string
    {
        return self::statuts()[$this->statut] ?? $this->statut;
    }

    /* ------------------------------------------------------- relations */

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    /** Le commentaire auquel celui-ci repond, s'il en est une reponse. */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** Les reponses, sur un seul niveau. */
    public function reponses()
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    /* --------------------------------------------------------- filtres */

    public function scopePublies($requete)
    {
        return $requete->where('statut', self::PUBLIE);
    }

    public function scopeAModerer($requete)
    {
        return $requete->where('statut', self::EN_ATTENTE);
    }

    /* ------------------------------------------------------ moderation */

    /**
     * Nombre maximal de liens toleres dans un message.
     *
     * Zero : un lecteur qui reagit a un article de conseil immobilier n'a
     * aucune raison d'y coller une adresse. Le courrier indesirable, lui, n'a
     * que ca a offrir — c'est le signal le plus sur et le moins couteux.
     */
    public const LIENS_TOLERES = 0;

    /**
     * Ce message doit-il attendre une lecture humaine ?
     *
     * Rend le MOTIF quand c'est le cas, null sinon. Un motif plutot qu'un
     * booleen : « en attente » sans raison ne dit pas a l'editeur s'il doit
     * s'inquieter ou approuver d'un clic.
     */
    public static function motifDeMiseEnAttente(string $message, string $auteur): ?string
    {
        $liens = preg_match_all('#https?://|www\.#i', $message);

        if ($liens > self::LIENS_TOLERES) {
            return __('Contient un lien');
        }

        // Un message tout en majuscules, au-dela de quelques mots, n'est
        // presque jamais un vrai commentaire.
        $lettres = preg_replace('/[^\p{L}]/u', '', $message);

        if (Str::length($lettres) > 20 && $lettres === Str::upper($lettres)) {
            return __('Écrit tout en majuscules');
        }

        // Un nom d'auteur qui contient une adresse : le champ sert de panneau
        // publicitaire, pas d'identite.
        if (preg_match('#https?://|www\.|\.[a-z]{2,}/#i', $auteur)) {
            return __('Adresse dans le nom');
        }

        return null;
    }

    /**
     * Depuis combien de temps, en clair.
     *
     * Une date exacte sous un commentaire vieillit mal : « il y a 3 jours » se
     * lit sans calcul, et reste vrai demain.
     */
    public function depuis(): string
    {
        return $this->created_at?->diffForHumans() ?? '';
    }
}
