<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * La derniere reussite et le dernier echec d'une tache d'entretien.
 *
 * Ecrite par TachesDEntretien::noter() a la fin de chaque execution, quel que
 * soit le declencheur : planificateur, route des plateformes sans cron, ou
 * commande lancee a la main.
 */
class ExecutionDEntretien extends Model
{
    protected $table = 'executions_d_entretien';

    public $timestamps = false;

    protected $fillable = ['commande', 'derniere_reussite', 'dernier_echec', 'suivi_depuis'];

    protected $casts = [
        'derniere_reussite' => 'datetime',
        'dernier_echec' => 'datetime',
        'suivi_depuis' => 'datetime',
    ];
}
