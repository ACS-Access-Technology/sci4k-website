<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La derniere execution de chaque tache d'entretien.
 *
 * Le planificateur tournait sans laisser de trace : s'il s'arretait, la
 * frequentation cessait d'etre agregee et le journal d'etre purge, et personne
 * ne le voyait. Le tableau de bord lit cette table pour le dire.
 *
 * Une ligne par commande, mise a jour sur place : on ne garde que la derniere
 * reussite et le dernier echec — de quoi repondre a « l'entretien tourne-t-il
 * encore ? », sans ouvrir une nouvelle table qui croitrait chaque nuit.
 *
 * `suivi_depuis` : le moment ou le suivi a commence. Une installation neuve,
 * ou le deploiement qui ajoute cette table, n'a encore jamais vu passer
 * l'entretien — ce n'est pas une panne tant que la premiere nuit n'est pas
 * passee. Le delai d'alerte court a partir de la.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executions_d_entretien', function (Blueprint $table) {
            $table->id();
            $table->string('commande', 100)->unique();
            $table->timestamp('derniere_reussite')->nullable();
            $table->timestamp('dernier_echec')->nullable();
            $table->timestamp('suivi_depuis')->nullable();
        });

        // La liste ecrite en toutes lettres, et non lue dans TachesDEntretien :
        // une migration doit produire demain ce qu'elle produit aujourd'hui.
        foreach (['frequentation:agreger', 'journal:purger'] as $commande) {
            DB::table('executions_d_entretien')->insert(['commande' => $commande, 'suivi_depuis' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('executions_d_entretien');
    }
};
