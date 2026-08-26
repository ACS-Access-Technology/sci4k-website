<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * La FAQ recoit ses propres rubriques, au lieu d'emprunter les services.
 *
 * La table d'origine faisait pointer chaque question vers un service, au motif
 * que le titre de groupe affiche sur faq.html EST le nom du service. C'etait
 * vrai des six groupes repris du site, et faux comme regle : le cadrage du lot
 * dit « Question FAQ : groupe, question, reponse », pas « service ». La
 * coincidence entre les six groupes et les six services avait ete prise pour
 * une contrainte.
 *
 * Consequence pratique : on ne pouvait pas ouvrir une rubrique « Paiements »
 * sans creer un service du meme nom, lequel serait apparu comme tuile sur
 * /services et aurait reclame une photo, une accroche et une description.
 *
 * Les douze questions existantes sont reportees sur six rubriques calquees sur
 * les six services — memes noms, meme ordre — de sorte que la page publique ne
 * change pas d'aspect le jour de la migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubriques_faq', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('ordre')->default(0);
            $table->boolean('visible')->default(true);
            $table->string('nom_fr', 190);
            $table->string('nom_en', 190);
            $table->timestamps();
            $table->index(['ordre']);
        });

        // Une rubrique par service existant, dans le meme ordre. Sur une base
        // vierge — les tests — cette boucle ne fait rien.
        $services = DB::table('services')->orderBy('ordre')->orderBy('id')->get();

        foreach ($services as $service) {
            DB::table('rubriques_faq')->insert([
                'slug' => $service->slug,
                'nom_fr' => $service->nom_fr,
                'nom_en' => $service->nom_en,
                'ordre' => $service->ordre,
                'visible' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('questions_faq', function (Blueprint $table) {
            // Nullable le temps du report : la colonne ne peut pas etre exigee
            // avant d'avoir ete remplie.
            $table->foreignId('rubrique_id')->nullable()->after('id')->constrained('rubriques_faq');
        });

        // Report par slug plutot que par identifiant : les deux tables ont des
        // identifiants independants, et le slug est ce qui les relie.
        $rubriquesParSlug = DB::table('rubriques_faq')->pluck('id', 'slug');
        $slugParService = DB::table('services')->pluck('slug', 'id');

        foreach ($slugParService as $serviceId => $slug) {
            DB::table('questions_faq')
                ->where('service_id', $serviceId)
                ->update(['rubrique_id' => $rubriquesParSlug[$slug]]);
        }

        // Une question sans rubrique serait invisible sur la page publique, qui
        // n'affiche que des groupes : mieux vaut echouer ici que livrer une
        // question que personne ne verra jamais.
        $orphelines = DB::table('questions_faq')->whereNull('rubrique_id')->count();

        if ($orphelines > 0) {
            throw new RuntimeException("$orphelines question(s) de FAQ n'ont pas trouve de rubrique. Migration interrompue.");
        }

        // La contrainte d'abord, l'index ensuite : sous MySQL, l'index
        // composite (service_id, ordre) sert la cle etrangere, qui refuse
        // qu'on le retire tant qu'elle tient. SQLite, lui, laisse passer les
        // deux ordres — le defaut ne se voit que sur le moteur de production.
        Schema::table('questions_faq', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('questions_faq', function (Blueprint $table) {
            $table->dropIndex(['service_id', 'ordre']);
            $table->dropColumn('service_id');
        });

        Schema::table('questions_faq', function (Blueprint $table) {
            $table->foreignId('rubrique_id')->nullable(false)->change();
            $table->index(['rubrique_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::table('questions_faq', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('id')->constrained('services');
        });

        $slugParRubrique = DB::table('rubriques_faq')->pluck('slug', 'id');
        $servicesParSlug = DB::table('services')->pluck('id', 'slug');

        foreach ($slugParRubrique as $rubriqueId => $slug) {
            if (! isset($servicesParSlug[$slug])) {
                continue;
            }

            DB::table('questions_faq')
                ->where('rubrique_id', $rubriqueId)
                ->update(['service_id' => $servicesParSlug[$slug]]);
        }

        // Rendre a la colonne son caractere obligatoire et son index composite,
        // tels que la migration d'origine les posait. Sans cette restitution,
        // `down()` n'est pas un inverse fidele : rejouer `up()` apres un retour
        // en arriere echoue faute de trouver l'index qu'il cherche a retirer.
        Schema::table('questions_faq', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable(false)->change();
            $table->index(['service_id', 'ordre']);
        });

        // Meme ordre imposé qu'a l'aller : la contrainte avant l'index.
        Schema::table('questions_faq', function (Blueprint $table) {
            $table->dropForeign(['rubrique_id']);
        });

        Schema::table('questions_faq', function (Blueprint $table) {
            $table->dropIndex(['rubrique_id', 'ordre']);
            $table->dropColumn('rubrique_id');
        });

        Schema::dropIfExists('rubriques_faq');
    }
};
