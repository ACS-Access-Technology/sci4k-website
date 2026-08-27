<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('categorie_id')->constrained('categories');

            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('nom_fr');
            $table->string('nom_en');
            $table->string('accroche_fr');
            $table->string('accroche_en');
            $table->text('description_fr');
            $table->text('description_en');

            // Trois atouts au maximum, comme les etiquettes des tuiles du site.
            // Nullables : un service peut n'en avoir qu'un.
            foreach (['1', '2', '3'] as $n) {
                $table->string('atout'.$n.'_fr')->nullable();
                $table->string('atout'.$n.'_en')->nullable();
            }

            $table->string('libelle_bouton_fr')->nullable();
            $table->string('libelle_bouton_en')->nullable();

            // Icone : le trace SVG de la tuile, repris tel quel du site.
            $table->text('icone_svg')->nullable();
            // Visuel de fond de la tuile, chemin relatif comme image_source.
            $table->string('image_source')->nullable();

            $table->timestamps();
            $table->index(['visible', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
