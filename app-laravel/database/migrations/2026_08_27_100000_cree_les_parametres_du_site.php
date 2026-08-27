<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Parametres generaux du site.
 *
 * Une table cle / valeur plutot qu'une table a trente colonnes. Le motif se
 * defend ici pour une raison precise : l'ecran de configuration groupe des
 * reglages qui n'ont rien a voir entre eux — un numero de telephone, un port
 * SMTP, un fichier robots.txt — et dont la liste bougera. Une colonne par
 * reglage aurait impose une migration a chaque ajout, pour des donnees que
 * seul l'affichage consulte. C'est le raisonnement qui avait deja produit la
 * colonne `options` des en-tetes de section au lot 2.
 *
 * Le revers est assume : la base ne contraint plus les types. La contrainte
 * vit donc dans le composant, qui declare ses champs et leurs regles au meme
 * endroit — une seule description, lue par la validation comme par la vue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->text('valeur')->nullable();
            // Le groupe correspond a un onglet de l'ecran. Il sert a charger
            // un onglet sans lire toute la table, et a ranger les valeurs
            // semees.
            $table->string('groupe')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
