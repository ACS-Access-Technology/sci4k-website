<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_faq', function (Blueprint $table) {
            $table->id();

            // Le titre de groupe affiche sur faq.html EST le nom du service :
            // la question pointe donc le service, et non une categorie ou un
            // groupe invente pour l'occasion.
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('question_fr', 500);
            $table->string('question_en', 500);
            $table->text('reponse_fr');
            $table->text('reponse_en');

            $table->timestamps();
            $table->index(['service_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_faq');
    }
};
