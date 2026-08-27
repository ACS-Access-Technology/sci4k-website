<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Etat d'un compte du backoffice, et date de sa derniere connexion.
 *
 * La maquette users-list.html montre trois etats — Actif, Inactif, Invitation
 * envoyee — et une colonne « Derniere connexion ». Aucun des deux n'existait :
 * un compte cree l'etait pour toujours, et rien ne disait s'il servait encore.
 *
 * `statut` plutot qu'un simple booleen « actif » : « invite » n'est pas un
 * compte desactive, c'est un compte qui n'a jamais servi. Les confondre aurait
 * empeche de distinguer une invitation en attente d'un depart d'employe.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('statut')->default('actif')->after('email');
            $table->timestamp('derniere_connexion_a')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['statut', 'derniere_connexion_a']);
        });
    }
};
