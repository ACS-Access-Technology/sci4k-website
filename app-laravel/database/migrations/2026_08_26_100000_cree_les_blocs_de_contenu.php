<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Les neuf familles de blocs restantes du lot 2.
 *
 * Une seule migration plutot que neuf : les tables n'ont aucune dependance
 * entre elles, et les creer d'un bloc evite neuf fichiers qui diraient tous la
 * meme chose. Elles suivent toutes les conventions posees au lot 1 puis au lot
 * 2a — une colonne par langue, `ordre` ecrit par le glisser-deposer, `visible`
 * pour retirer du site sans effacer.
 *
 * Trois familles n'ont ni `ordre` variable ni `visible` : les reglages de
 * section, qui sont ceux du site et ne se creent pas ; et les petits ensembles
 * figes (valeurs, chiffres cles, etapes), edites d'un bloc.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------- reglages de section (23)
        // Une table indexee par section plutot que deux cas particuliers : le
        // cadrage n'annonçait que l'en-tete du processus et la banderole, mais
        // les vingt-trois sections du site portent le meme triplet.
        Schema::create('reglages_de_section', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('etiquette_fr', 190)->default('');
            $table->string('etiquette_en', 190)->default('');
            $table->string('titre_fr', 255)->default('');
            $table->string('titre_en', 255)->default('');
            $table->text('chapo_fr')->nullable();
            $table->text('chapo_en')->nullable();
            $table->timestamps();
        });

        // ------------------------------------------------------ temoignages
        Schema::create('temoignages', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            // Nom propre : identique dans les deux langues, donc une seule
            // colonne. Le traduire aurait invente un second nom pour la meme
            // personne.
            $table->string('auteur', 190);
            $table->string('initiales', 8)->default('');
            $table->unsignedTinyInteger('note')->default(5);

            $table->text('citation_fr');
            $table->text('citation_en');
            $table->string('role_fr', 190)->default('');
            $table->string('role_en', 190)->default('');

            $table->timestamps();
            $table->index(['ordre']);
        });

        // ------------------------------------------------------ partenaires
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('nom', 190);
            $table->string('logo', 255)->nullable();
            // Deux des sept partenaires repris du site n'ont pas de site
            // officiel : leur carte n'est pas un lien.
            $table->string('site', 255)->nullable();

            $table->timestamps();
            $table->index(['ordre']);
        });

        // --------------------------------------------------- membres d'equipe
        Schema::create('membres_equipe', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('nom', 190);
            $table->string('photo', 255)->nullable();
            $table->string('linkedin', 255)->nullable();
            $table->string('email', 190)->nullable();

            $table->string('etiquette_fr', 190)->default('');
            $table->string('etiquette_en', 190)->default('');
            $table->string('fonction_fr', 190)->default('');
            $table->string('fonction_en', 190)->default('');
            $table->text('biographie_fr')->nullable();
            $table->text('biographie_en')->nullable();

            $table->timestamps();
            $table->index(['ordre']);
        });

        // ----------------------------------------------------------- encarts
        Schema::create('encarts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            $table->string('etiquette_fr', 190)->default('');
            $table->string('etiquette_en', 190)->default('');
            $table->string('titre_fr', 255)->default('');
            $table->string('titre_en', 255)->default('');
            $table->text('texte_fr')->nullable();
            $table->text('texte_en')->nullable();
            $table->string('libelle_bouton_fr', 120)->default('');
            $table->string('libelle_bouton_en', 120)->default('');
            $table->string('cible_bouton', 255)->default('');
            $table->string('image_source', 255)->nullable();

            $table->timestamps();
            $table->index(['ordre']);
        });

        // --------------------------------------------------- images de fond
        Schema::create('images_de_fond', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);

            // Le slug reprend la variable CSS (--img-{slug}) : c'est lui qui
            // relie une image a l'endroit du site qui l'affiche.
            $table->string('slug')->unique();
            $table->string('fichier', 255);
            $table->string('texte_alternatif_fr', 255)->default('');
            $table->string('texte_alternatif_en', 255)->default('');

            $table->timestamps();
            $table->index(['ordre']);
        });

        // ------------------------------------ petits ensembles figes (3 x n)
        // Ni `visible` ni creation : on modifie ce qui existe. Le rang sert
        // l'affichage, pas un reordonnancement — la maquette values-list.html
        // decrit exactement cela.
        Schema::create('valeurs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->string('titre_fr', 190)->default('');
            $table->string('titre_en', 190)->default('');
            $table->text('texte_fr')->nullable();
            $table->text('texte_en')->nullable();
            $table->timestamps();
        });

        Schema::create('chiffres_cles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->unsignedInteger('valeur')->default(0);
            $table->string('intitule_fr', 190)->default('');
            $table->string('intitule_en', 190)->default('');
            $table->timestamps();
        });

        Schema::create('etapes_processus', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ordre')->default(0);
            $table->string('titre_fr', 190)->default('');
            $table->string('titre_en', 190)->default('');
            $table->text('texte_fr')->nullable();
            $table->text('texte_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'etapes_processus', 'chiffres_cles', 'valeurs',
            'images_de_fond', 'encarts', 'membres_equipe',
            'partenaires', 'temoignages', 'reglages_de_section',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
