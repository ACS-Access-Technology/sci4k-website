<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La photo de profil d'un compte du backoffice.
 *
 * Le chemin est stocke tel qu'il sera rendu — « storage/comptes/… » — comme
 * pour les couvertures d'article et les visuels de service. Les vues n'ont
 * ainsi qu'un seul point d'appel, et le repli sur les initiales est teste une
 * fois pour toutes plutot que reecrit dans chaque gabarit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('photo')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('photo');
        });
    }
};
