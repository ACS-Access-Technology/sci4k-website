<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages recus par le formulaire de contact du site.
 *
 * Jusqu'ici le formulaire composait un message WhatsApp et rien n'etait
 * conserve : la maquette montrait pourtant un ecran « Messages de contact »
 * qui n'aurait eu aucune donnee a afficher. Le client a tranche pour les deux
 * canaux — la conversation WhatsApp s'ouvre comme avant, ET le message est
 * enregistre, si bien qu'aucune demande ne se perd si le telephone change de
 * main.
 *
 * Le « bien concerne » de la maquette n'est PAS ici : il designe une fiche de
 * bien, et les biens sont le lot 3. Une colonne qui ne pourrait designer que
 * du vide aurait fait un champ menteur de plus.
 *
 * Aucune adresse IP n'est conservee. Elle ne servirait qu'a la lutte contre
 * les envois automatises, deja traitee par la limitation de debit et le champ
 * piege, et c'est une donnee personnelle de plus a declarer dans une politique
 * de confidentialite qui est justement en cours de reecriture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages_de_contact', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();
            $table->string('sujet')->nullable();
            $table->text('message');

            $table->string('statut')->default('nouveau')->index();

            // Le collaborateur charge de repondre. nullOnDelete : le depart
            // d'un employe ne doit pas effacer les demandes qu'il suivait.
            $table->foreignId('assigne_a')->nullable()->constrained('users')->nullOnDelete();

            // Sert au delai moyen de reponse affiche par l'ecran. Nul tant que
            // personne n'a repondu.
            $table->timestamp('repondu_a')->nullable();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages_de_contact');
    }
};
