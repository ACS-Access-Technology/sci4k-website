<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Les pages vues d'un jour, pour un chemin.
 *
 * Ce comptage s'additionne : le total d'une periode est la somme de ses jours,
 * et le classement des pages la somme de leurs lignes. C'est ce qui permet de
 * jeter le detail sans rien perdre de ce que l'ecran montre.
 */
class PageQuotidienne extends Model
{
    protected $table = 'pages_quotidiennes';

    public $timestamps = false;

    protected $fillable = ['jour', 'chemin', 'pages_vues'];

    /**
     * `jour` reste une chaine Y-m-d, sans cast date.
     *
     * Le cast `date` ne gouverne que la serialisation en tableau : a l'ecriture
     * c'est getDateFormat() qui decide, et il vaut Y-m-d H:i:s. La colonne
     * recevait donc « 2026-09-06 00:00:00 ». SQLite, au typage souple, le
     * gardait tel quel la ou MySQL l'aurait tronque — le meme code donnait deux
     * resultats selon le moteur.
     */
    protected $casts = [
        'pages_vues' => 'integer',
    ];
}
