<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entrees des menus du site : barre du haut et colonnes du pied.
 *
 * La barre de navigation etait ecrite DEUX FOIS dans le gabarit — une fois
 * pour l'ecran large, une fois pour le menu telephone — avec les memes sept
 * entrees recopiees a la main. Ajouter une page obligeait a penser aux deux,
 * et en oublier une ne se voyait qu'en reduisant la fenetre.
 *
 * La cible est une chaine, et volontairement pas une cle etrangere vers une
 * table de pages : les menus pointent aussi bien vers des routes de
 * l'application que vers des fichiers encore statiques (/biens.html) ou, un
 * jour, vers un site exterieur. Sa forme est contrainte par le modele, pas par
 * la base — un « javascript: » dans un href est une injection, et la base ne
 * sait pas le voir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrees_de_menu', function (Blueprint $table) {
            $table->id();
            $table->string('menu')->index();
            $table->string('libelle_fr');
            $table->string('libelle_en')->nullable();
            $table->string('cible');
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['menu', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrees_de_menu');
    }
};
