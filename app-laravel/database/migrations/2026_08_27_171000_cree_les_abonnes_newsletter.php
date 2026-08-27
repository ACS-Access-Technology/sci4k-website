<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adresses inscrites a la lettre d'information.
 *
 * Le champ d'inscription figure au pied des HUIT pages du site. Il ouvrait le
 * logiciel de courrier du visiteur par un lien « mailto: » — c'est-a-dire RIEN
 * sur la plupart des telephones, ou aucun compte n'y est configure. L'agence
 * perdait donc des adresses sans jamais savoir combien.
 *
 * `desinscrit_a` plutot qu'une suppression : garder la trace du retrait est ce
 * qui empeche de reinscrire par erreur quelqu'un qui est parti, et c'est aussi
 * ce qu'on doit pouvoir montrer si l'interesse le demande.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnes_newsletter', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('desinscrit_a')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnes_newsletter');
    }
};
