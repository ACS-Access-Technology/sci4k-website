<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auteur d'un article.
 *
 * La maquette des utilisateurs decrit le role Redacteur : « Cree et modifie SES
 * PROPRES contenus, publication soumise a validation ». Sans colonne d'auteur,
 * cette phrase aurait ete decorative — un redacteur aurait modifie les articles
 * de tout le monde, et le panneau des roles aurait menti sur ce qu'il autorise.
 *
 * Nullable, et volontairement : les douze articles importes du site n'ont pas
 * d'auteur connu. Leur en inventer un aurait attribue a quelqu'un un texte
 * qu'il n'a pas ecrit.
 *
 * nullOnDelete plutot que cascade : supprimer un compte ne doit pas emporter
 * les articles qu'il a publies. Le site perdrait du contenu en ligne parce
 * qu'un employe est parti.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('auteur_id')->nullable()->after('categorie_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auteur_id');
        });
    }
};
