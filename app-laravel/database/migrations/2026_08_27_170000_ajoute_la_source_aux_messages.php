<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D'ou vient un message : formulaire de contact, ou question de la FAQ.
 *
 * Les deux formulaires composaient un message WhatsApp et ne laissaient AUCUNE
 * trace. Le contact a ete branche sur la base ; la question de FAQ ne l'etait
 * pas, et disparaissait donc de la meme facon — une question posee par un
 * prospect, perdue sans que personne ne sache qu'elle avait ete posee.
 *
 * Une colonne plutot que deux tables : une question de FAQ EST un message de
 * contact — meme expediteur, meme reponse attendue, meme ecran pour y repondre.
 * Seule son origine differe, et c'est ce que cette colonne dit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages_de_contact', function (Blueprint $table) {
            $table->string('source')->default('contact')->after('statut')->index();
        });
    }

    public function down(): void
    {
        Schema::table('messages_de_contact', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
