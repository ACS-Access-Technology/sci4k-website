<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Les taches prioritaires du tableau de bord.
 *
 * La maquette les montre avec une case a cocher, une echeance et un bouton
 * d'ajout : ce sont de vraies taches saisies, pas des constats deduits de
 * l'etat du site. Les deux se completent — « 3 articles en brouillon » se
 * deduit, « rappeler le notaire jeudi » se saisit.
 *
 * Elles appartiennent a leur auteur : deux editeurs n'ont pas les memes
 * priorites, et une liste commune serait raturee par l'un pendant que l'autre
 * la lit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('texte', 255);
            $table->date('echeance')->nullable();
            $table->boolean('terminee')->default(false);
            $table->unsignedInteger('ordre')->default(0);

            $table->timestamps();
            $table->index(['user_id', 'terminee', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taches');
    }
};
