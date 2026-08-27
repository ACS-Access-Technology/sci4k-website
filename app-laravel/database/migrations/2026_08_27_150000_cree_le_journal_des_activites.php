<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des actions faites depuis l'administration.
 *
 * Le tableau de bord affichait deja une « activite recente », mais il la
 * DEVINAIT : il classait les contenus par `updated_at` et montrait les six
 * derniers. Cette approximation ne pouvait pas dire ce qui s'etait passe —
 * creation, modification, publication — ni QUI l'avait fait, et elle perdait
 * toute trace d'un element supprime. Un enregistrement sans changement reel
 * suffisait aussi a faire remonter un contenu en tete.
 *
 * L'intitule est RECOPIE dans le journal plutot que lu depuis le contenu :
 * une ligne doit rester lisible apres la suppression de ce qu'elle decrit,
 * et continuer de dire quel titre portait l'article efface.
 *
 * Le sujet est une relation polymorphe SANS contrainte de cle etrangere : il
 * designe une ligne qui peut avoir disparu, et c'est meme le cas le plus
 * interessant du journal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_activites', function (Blueprint $table) {
            $table->id();

            // L'auteur de l'action. Nul pour une action faite hors session —
            // un import en ligne de commande, par exemple — et nul aussi quand
            // le compte a ete supprime depuis.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('auteur_nom')->nullable();

            $table->string('action');
            $table->string('sujet_type');
            $table->unsignedBigInteger('sujet_id')->nullable();
            $table->string('sujet_intitule');

            $table->timestamps();

            $table->index('created_at');
            $table->index(['sujet_type', 'sujet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_activites');
    }
};
