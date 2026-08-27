<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Communes du bandeau defilant de l'accueil.
 *
 * Une table plutot qu'une sixieme famille de `referentiels`, malgre la
 * ressemblance : la liste du bandeau et le filtre des zones n'ont NI le meme
 * decoupage NI le meme role. Le bandeau nomme sept communes une a une —
 * Cocody, Riviera, Angre… — la ou le filtre en regroupe cinq, « Cocody &
 * Riviera » comptant pour une seule entree de recherche. Les confondre aurait
 * force l'un des deux ecrans a mentir sur ce qu'il regle.
 *
 * Pas de colonne par langue : ce sont des noms propres de communes
 * d'Abidjan. Deux colonnes auraient invite a inventer une traduction de
 * « Bingerville ». Meme raisonnement qu'au lot 2 pour l'auteur d'un temoignage
 * et le nom d'un membre de l'equipe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communes_du_bandeau', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index('ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communes_du_bandeau');
    }
};
