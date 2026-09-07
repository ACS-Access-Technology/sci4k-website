<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visite extends Model
{
    /**
     * Combien de jours de detail sont conserves.
     *
     * Au-dela, seuls les agregats quotidiens subsistent. Les periodes que
     * l'ecran de frequentation propose jusqu'a ce seuil restent donc exactes au
     * visiteur pres ; la seule qui le depasse lit les agregats, et le dit.
     */
    public const JOURS_DE_DETAIL = 90;

    protected $table = 'visites';

    public $timestamps = false;

    protected $fillable = ['chemin', 'session_hash', 'visitee_le'];

    protected $casts = ['visitee_le' => 'datetime'];
}
