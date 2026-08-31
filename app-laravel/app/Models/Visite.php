<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visite extends Model
{
    protected $table = 'visites';

    public $timestamps = false;

    protected $fillable = ['chemin', 'session_hash', 'user_agent', 'visitee_le'];

    protected $casts = ['visitee_le' => 'datetime'];
}
