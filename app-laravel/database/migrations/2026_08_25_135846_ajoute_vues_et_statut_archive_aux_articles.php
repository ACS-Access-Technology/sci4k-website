<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Compteur de vues et troisieme statut « archive ».
 *
 * L'archivage rejoint l'enumeration plutot que de devenir une colonne a part :
 * scopePublies filtre deja sur statut = 'publie' et la page de detail refuse
 * tout ce qui ne l'est pas, si bien qu'un article archive quitte le site sans
 * qu'aucune de ces deux regles ne bouge. Un booleen distinct aurait cree un
 * second axe a croiser avec le premier, et « un brouillon archive » n'aurait
 * pas de sens.
 *
 * La modification passe par ->change() et non par du SQL propre a MySQL :
 * SQLite applique lui aussi l'enumeration, par une contrainte CHECK, ce qu'un
 * premier jet de cette migration avait suppose faux. Verifie par les tests,
 * qui refusaient la valeur « archive » en memoire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('vues')->default(0)->after('statut');
            $table->enum('statut', ['brouillon', 'publie', 'archive'])->default('brouillon')->change();
        });
    }

    public function down(): void
    {
        // Les articles archives redeviennent des brouillons : sans cela, la
        // contrainte reduite les rendrait invalides et la migration echouerait
        // sur une base contenant des archives.
        DB::table('articles')->where('statut', 'archive')->update(['statut' => 'brouillon']);

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('vues');
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon')->change();
        });
    }
};
