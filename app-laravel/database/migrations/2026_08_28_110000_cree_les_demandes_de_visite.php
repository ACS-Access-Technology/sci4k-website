<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rendez-vous demandes depuis les fiches de biens.
 *
 * Reporte au lot 3 lors du cadrage, et c'etait juste : chaque ligne de la
 * maquette porte une reference de bien, qui n'existait pas encore. Elle existe
 * desormais.
 *
 * `bien_id` est nullable et se detache a la suppression : une demande de visite
 * garde son sens quand le bien a ete vendu puis retire du catalogue — c'est
 * meme la trace de ce qui a mene a la vente. Le titre du bien est recopie pour
 * que la ligne reste lisible dans ce cas, comme l'intitule du journal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_de_visite', function (Blueprint $table) {
            $table->id();

            $table->string('nom');
            $table->string('telephone');
            $table->string('email')->nullable();
            $table->text('message')->nullable();

            $table->foreignId('bien_id')->nullable()->constrained('biens')->nullOnDelete();
            $table->string('bien_intitule')->nullable();

            // Le creneau souhaite par le visiteur. Nullable : il peut demander
            // a etre rappele sans proposer d'heure.
            $table->dateTime('creneau_souhaite')->nullable();

            $table->string('statut')->default('a_confirmer')->index();
            $table->foreignId('assigne_a')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_de_visite');
    }
};
