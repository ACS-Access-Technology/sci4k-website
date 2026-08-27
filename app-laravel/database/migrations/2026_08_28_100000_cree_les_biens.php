<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le catalogue des biens immobiliers.
 *
 * Coeur du metier de l'agence, et derniere famille de contenu encore ecrite en
 * dur : les six biens du site vivaient dans un tableau JavaScript de
 * frontoffice/assets/main.js.
 *
 * TROIS CHOIX DE MODELISATION, chacun contre une facon de se tromper :
 *
 * 1. La SURFACE est stockee, la TRANCHE est calculee. Le site stockait la
 *    tranche (« s3 ») a cote du texte (« 310 m² ») : deux verites pour une
 *    seule information, et rien n'empechait de classer un 310 m² sous « moins
 *    de 100 m² ». Verifie sur les six biens reels — la tranche se deduit
 *    exactement de la surface dans les six cas.
 *
 * 2. Le NOMBRE DE PIECES est nullable. Un terrain n'en a pas ; le site lui
 *    posait 1, ce qui le faisait remonter dans le filtre « 1 a 2 pieces ».
 *    Un terrain nu n'est pas un logement d'une piece.
 *
 * 3. Le PRIX existe mais n'est pas affiche au public. La maquette du backoffice
 *    le demande, le site ne le montre nulle part — l'agence annonce ses prix de
 *    vive voix. La colonne le permet quand elle changera d'avis, sans que le
 *    site n'invente un chiffre en attendant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();

            // Reference interne, du type « SCI4K-0123 ». Nullable : les six
            // biens repris du site n'en portaient aucune.
            $table->string('reference')->nullable()->unique();
            $table->string('slug')->unique();

            /* ---------------------------------------------- textes */
            $table->string('titre_fr');
            $table->string('titre_en')->nullable();
            // « Villa moderne · F5 » — la ligne au-dessus du titre sur la carte.
            $table->string('sous_titre_fr')->nullable();
            $table->string('sous_titre_en')->nullable();
            $table->string('accroche_fr')->nullable();
            $table->string('accroche_en')->nullable();
            $table->text('description_fr')->nullable();
            $table->text('description_en')->nullable();

            /* ------------------------------------- caracteristiques */
            // Valeurs du referentiel : « villa », « cocody », « acd-disponible ».
            // Pas de cle etrangere : un referentiel supprime ne doit pas
            // emporter les biens qui s'y rattachaient, ni empecher de le
            // supprimer. La coherence est verifiee a la saisie.
            $table->string('type')->index();
            $table->string('offre')->index();          // vente | location
            $table->string('zone')->index();
            $table->string('statut_juridique')->nullable();
            $table->string('numero_titre')->nullable();
            $table->string('quartier')->nullable();

            $table->unsignedBigInteger('prix')->nullable();
            // « total » pour un prix de vente, « m2 » pour un terrain, « mois »
            // pour une location : la maquette montre « 25 M/m² » et « 5 M/mois ».
            $table->string('prix_unite')->default('total');

            $table->unsignedInteger('surface_habitable')->nullable();
            $table->unsignedInteger('surface_terrain')->nullable();
            $table->unsignedInteger('nombre_pieces')->nullable();
            $table->unsignedInteger('nombre_chambres')->nullable();
            $table->unsignedInteger('nombre_salles_eau')->nullable();

            // Liste libre et bilingue — « Cuisine américaine équipée », « Fibre
            // optique ». Les huit cases a cocher de la maquette n'auraient pas
            // su le dire, et c'est ce que le site affiche deja.
            $table->json('equipements')->nullable();

            /* ------------------------------------------ referencement */
            $table->string('meta_titre_fr')->nullable();
            $table->string('meta_titre_en')->nullable();
            $table->string('meta_description_fr')->nullable();
            $table->string('meta_description_en')->nullable();

            /* -------------------------------------------- publication */
            $table->string('statut')->default('brouillon')->index();
            $table->date('date_mise_en_ligne')->nullable();
            $table->boolean('en_avant')->default(false);
            $table->boolean('urgent')->default(false);

            // nullOnDelete : le depart d'un employe ne retire pas ses biens du
            // catalogue.
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('ordre')->default(0);
            $table->unsignedInteger('vues')->default(0);

            $table->timestamps();

            $table->index(['statut', 'ordre']);
        });

        Schema::create('photos_de_bien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('biens')->cascadeOnDelete();
            $table->string('fichier');
            $table->string('texte_alternatif_fr')->nullable();
            $table->string('texte_alternatif_en')->nullable();
            $table->unsignedInteger('ordre')->default(0);
            $table->timestamps();

            $table->index(['bien_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos_de_bien');
        Schema::dropIfExists('biens');
    }
};
