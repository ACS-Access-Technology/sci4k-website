<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Valeurs des listes deroulantes du site public.
 *
 * Les quatre filtres de la page /biens sont ecrits en dur dans le HTML —
 * « Villa & Duplex », « Cocody & Riviera », « 1 a 2 pieces »… Ils alimenteront
 * aussi les listes de la fiche bien au lot 3, et c'est la raison d'etre de
 * cette table : un vocabulaire unique des deux cotes. Deux listes ecrites
 * separement divergent des la premiere valeur ajoutee d'un seul cote, et un
 * bien devient alors introuvable par le filtre cense le trouver.
 *
 * Les familles vivent dans une seule table plutot que dans cinq. Elles ont la
 * meme forme — un libelle bilingue, une valeur technique, un rang, une
 * visibilite — et cinq tables auraient impose cinq modeles et cinq ecrans pour
 * le meme comportement.
 *
 * L'ordre est propre a CHAQUE famille : l'index le dit, et le composant
 * renumerote famille par famille. Un rang global aurait melange les quatre
 * listes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referentiels', function (Blueprint $table) {
            $table->id();
            $table->string('famille')->index();
            // Valeur technique employee par les filtres et les fiches. Elle ne
            // change pas quand on renomme le libelle : renommer « Villa »
            // n'est alors pas une migration de donnees.
            $table->string('valeur');
            $table->string('libelle_fr');
            $table->string('libelle_en')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            // Une meme valeur technique ne peut pas exister deux fois dans une
            // famille : deux « villa » rendraient le filtre non deterministe.
            $table->unique(['famille', 'valeur']);
            $table->index(['famille', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referentiels');
    }
};
