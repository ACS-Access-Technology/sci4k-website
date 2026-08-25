<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('categorie_id')->constrained('categories');
            $table->date('date_publication');
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');

            $table->string('titre_fr');
            $table->string('titre_en');
            $table->text('resume_fr');
            $table->text('resume_en');
            $table->longText('contenu_fr');
            $table->longText('contenu_en');

            $table->string('image_source')->nullable();

            $table->string('meta_titre_fr')->nullable();
            $table->string('meta_titre_en')->nullable();
            $table->string('meta_description_fr', 200)->nullable();
            $table->string('meta_description_en', 200)->nullable();

            $table->timestamps();

            $table->index(['statut', 'date_publication']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
