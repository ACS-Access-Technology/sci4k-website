<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire `icone_svg` des valeurs.
 *
 * La colonne avait ete ajoutee par symetrie avec les services, sans que la
 * maquette la demande : la carte d'une valeur affiche un NUMERO — « 01 », « 02 »
 * — et jamais un pictogramme, sur le site statique comme sur la page portee.
 * Le champ etait pourtant propose a l'editeur, avec une aide qui promettait un
 * pictogramme ; l'y laisser revenait a faire saisir un contenu que rien
 * n'affiche.
 *
 * Le retrait ferme aussi un risque dormant. Les icones de service sont rendues
 * sans echappement — `{!! $service->icone_svg !!}` — mais leur formulaire les
 * contraint a la liste deja en base (`Rule::in`), si bien qu'aucun trace neuf
 * n'y entre. Celle des valeurs, elle, acceptait 4000 caracteres libres. Le jour
 * ou quelqu'un aurait affiche l'icone d'une valeur en copiant le motif voisin,
 * un SVG saisi en administration se serait execute chez chaque visiteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('valeurs', function (Blueprint $table) {
            $table->dropColumn('icone_svg');
        });
    }

    public function down(): void
    {
        Schema::table('valeurs', function (Blueprint $table) {
            $table->text('icone_svg')->nullable()->after('texte_en');
        });
    }
};
