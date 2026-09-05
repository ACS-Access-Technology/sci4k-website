<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages_statiques', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('titre_fr', 190);
            $table->string('titre_en', 190)->default('');
            $table->longText('contenu_fr')->nullable();
            $table->longText('contenu_en')->nullable();
            $table->boolean('publie')->default(true);
            $table->timestamps();
        });

        foreach ([
            ['contact', 'Contact', 'Contact'],
            ['mentions-legales', 'Mentions légales', 'Legal notices'],
            ['politique-confidentialite', 'Politique de confidentialité', 'Privacy policy'],
        ] as [$slug, $titreFr, $titreEn]) {
            DB::table('pages_statiques')->insert([
                'slug' => $slug, 'titre_fr' => $titreFr, 'titre_en' => $titreEn,
                'contenu_fr' => '', 'contenu_en' => '', 'publie' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages_statiques');
    }
};
