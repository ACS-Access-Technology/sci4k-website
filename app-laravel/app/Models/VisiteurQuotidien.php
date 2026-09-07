<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Les visiteurs distincts d'un jour.
 *
 * Ce comptage NE s'additionne PAS d'un jour a l'autre : quelqu'un qui revient
 * le lendemain compte une fois chaque jour, mais reste une seule personne sur
 * la semaine. La somme de ces lignes est donc un cumul de visites quotidiennes,
 * pas un nombre de personnes — et l'ecran doit le dire la ou il l'emploie.
 *
 * `jour` reste une chaine Y-m-d, pour la meme raison que dans
 * [[PageQuotidienne]] : le cast date ecrirait un horodatage complet.
 */
class VisiteurQuotidien extends Model
{
    protected $table = 'visiteurs_quotidiens';

    public $timestamps = false;

    protected $fillable = ['jour', 'visiteurs'];

    protected $casts = ['visiteurs' => 'integer'];
}
