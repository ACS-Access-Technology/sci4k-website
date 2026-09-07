<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les comptages quotidiens, pour que le detail des visites cesse de s'accumuler.
 *
 * La table `visites` recevait une ligne par page vue et rien ne l'en empechait.
 * Ces agregats permettent d'en purger le detail au-dela de quatre-vingt-dix
 * jours sans rien perdre de ce que l'ecran de frequentation affiche.
 *
 * DEUX TABLES, ET NON UNE : les visiteurs d'un jour ne s'obtiennent pas en
 * additionnant les visiteurs de chaque page. Un visiteur qui consulte trois
 * pages compterait trois fois. Le comptage par page et le comptage par jour
 * repondent donc a deux questions differentes, et vivent separement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages_quotidiennes', function (Blueprint $table) {
            $table->id();
            $table->date('jour')->index();
            $table->string('chemin', 255);
            $table->unsignedInteger('pages_vues');

            // La cle unique porte l'idempotence : reagreger un jour deja traite
            // met a jour la ligne au lieu d'en ajouter une seconde.
            $table->unique(['jour', 'chemin']);
        });

        Schema::create('visiteurs_quotidiens', function (Blueprint $table) {
            $table->id();
            $table->date('jour')->unique();
            $table->unsignedInteger('visiteurs');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visiteurs_quotidiens');
        Schema::dropIfExists('pages_quotidiennes');
    }
};
