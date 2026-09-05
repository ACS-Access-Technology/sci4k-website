<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une tache prioritaire du tableau de bord.
 *
 * Elle appartient a son auteur : deux editeurs n'ont pas les memes priorites,
 * et une liste commune serait raturee par l'un pendant que l'autre la lit.
 *
 * Aucun champ traduisible : c'est une note de travail, ecrite dans la langue
 * de celui qui la pose, et que le site public n'affiche jamais.
 */
class Tache extends Model
{
    use CollectionOrdonnable;
    use HasFactory;

    protected $table = 'taches';

    protected $fillable = ['user_id', 'texte', 'echeance', 'terminee', 'ordre'];

    protected $casts = [
        'echeance' => 'date',
        'terminee' => 'boolean',
        'ordre' => 'integer',
    ];

    protected $attributes = ['terminee' => false, 'ordre' => 0];

    /** @return BelongsTo<User, $this> */
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Comment presenter l'echeance, et avec quelle urgence.
     *
     * Une date brute ne dit rien d'utile en un coup d'oeil : « 12/09 » demande
     * un calcul, « dans 3 jours » se lit. Le ton suit le delai, de sorte que ce
     * qui presse se voie sans etre lu.
     *
     * @return array{texte: string, ton: string}|null
     */
    public function echeanceLisible(): ?array
    {
        if (! $this->echeance) {
            return null;
        }

        $jours = (int) now()->startOfDay()->diffInDays($this->echeance->startOfDay(), false);

        return match (true) {
            $jours < 0 => ['texte' => __('en retard'), 'ton' => 'urgent'],
            $jours === 0 => ['texte' => __("aujourd'hui"), 'ton' => 'urgent'],
            $jours === 1 => ['texte' => __('demain'), 'ton' => 'proche'],
            $jours <= 7 => ['texte' => trans_choice(':nombre jour|:nombre jours', $jours, ['nombre' => $jours]), 'ton' => 'proche'],
            default => ['texte' => $this->echeance->isoFormat('D MMM'), 'ton' => 'lointain'],
        };
    }
}
