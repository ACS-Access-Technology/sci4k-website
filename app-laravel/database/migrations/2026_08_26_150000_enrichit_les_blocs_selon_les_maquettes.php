<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Les champs que les maquettes du backoffice demandent en plus.
 *
 * Le premier jet des trois petits ensembles ne portait que le texte visible.
 * Les maquettes en attendent davantage, et chaque ajout repond a un besoin
 * qu'on voit sur l'ecran :
 *
 *   - un suffixe pour les chiffres, parce que « 98 » et « 98 % » ne sont pas
 *     le meme nombre et que le second ne s'ecrit pas dans le champ du premier ;
 *   - une note interne, qui explique d'ou vient un chiffre sans s'afficher sur
 *     le site — « Depuis la creation en 2015 » ;
 *   - une visibilite par element, pour retirer une valeur ou une etape du site
 *     sans la perdre ;
 *   - une icone par valeur, la maquette en montrant une distincte sur chacune ;
 *   - des options par bloc, rangees en JSON sur l'en-tete de section auquel
 *     elles se rapportent. Une colonne par option aurait fait une migration a
 *     chaque reglage nouveau, pour des donnees que seul l'affichage consulte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chiffres_cles', function (Blueprint $table) {
            $table->string('suffixe', 16)->default('')->after('valeur');
            $table->string('note_interne', 255)->nullable()->after('intitule_en');
            $table->boolean('visible')->default(true)->after('ordre');
        });

        Schema::table('valeurs', function (Blueprint $table) {
            $table->text('icone_svg')->nullable()->after('texte_en');
            $table->boolean('visible')->default(true)->after('ordre');
        });

        Schema::table('etapes_processus', function (Blueprint $table) {
            $table->boolean('visible')->default(true)->after('ordre');
        });

        Schema::table('reglages_de_section', function (Blueprint $table) {
            $table->json('options')->nullable()->after('chapo_en');
        });
    }

    public function down(): void
    {
        Schema::table('chiffres_cles', function (Blueprint $table) {
            $table->dropColumn(['suffixe', 'note_interne', 'visible']);
        });

        Schema::table('valeurs', function (Blueprint $table) {
            $table->dropColumn(['icone_svg', 'visible']);
        });

        Schema::table('etapes_processus', function (Blueprint $table) {
            $table->dropColumn('visible');
        });

        Schema::table('reglages_de_section', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
