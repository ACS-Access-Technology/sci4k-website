<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les biens illustrent desormais les activites de l'agence.
 *
 * LE DEFAUT CORRIGE. Le site annoncait six activites et n'en montrait qu'une.
 * Le catalogue est range par type de bien et par offre — des categories qui
 * disent « quel genre de batiment », jamais « laquelle de nos six activites ».
 * Un visiteur lisant « Administration de biens » n'avait donc rien a regarder.
 *
 * POURQUOI UN RATTACHEMENT EXPLICITE, et non une regle deduite des colonnes
 * existantes : aucune regle automatique ne tient. « type = terrain » donne bien
 * le Foncier, et « offre = location » la Gestion, mais Achat et Vente
 * retomberaient sur EXACTEMENT la meme liste, tandis que Construction et
 * Administration de biens resteraient vides — les deux activites qu'on nous a
 * justement reproche de ne pas montrer. Deduire, c'etait reproduire le defaut.
 *
 * LA REALISATION. Ce qu'on veut montrer sous Construction ou Administration
 * n'est pas un bien a vendre : c'est un immeuble qu'on a bati, un immeuble
 * qu'on gere. Ces references n'ont ni prix ni visite a proposer, et n'ont rien
 * a faire dans le catalogue. Elles restent pourtant des biens — memes photos,
 * meme description bilingue, meme fiche — d'ou un simple caractere porte par
 * le bien plutot qu'un modele de plus a entretenir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bien_service', function (Blueprint $table) {
            $table->foreignId('bien_id')->constrained('biens')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();

            // Un bien n'illustre une activite qu'une fois. Sans la contrainte,
            // un double clic dans le backoffice afficherait le meme bien deux
            // fois dans la meme modale.
            $table->primary(['bien_id', 'service_id']);
        });

        if (! Schema::hasColumn('biens', 'est_une_realisation')) {
            Schema::table('biens', function (Blueprint $table) {
                $table->boolean('est_une_realisation')->default(false)->after('statut');

                // Le catalogue ecarte les realisations a CHAQUE affichage :
                // la colonne se retrouve dans la clause where de la requete la
                // plus frequentee du site.
                $table->index(['est_une_realisation', 'statut']);
            });
        }

        $this->rattacheCeQuiEstCertain();
    }

    /**
     * Un point de depart, pas une verite.
     *
     * Trois activites se deduisent sans ambiguite de ce que les biens portent
     * deja. Les trois autres sont laissees VIDES a dessein : « Achat » et
     * « Vente » designent le meme bien vu de deux cotes, et rien en base ne dit
     * qu'un immeuble a ete bati ou est administre par l'agence. Les remplir
     * aurait ete inventer. Tout se recoche de toute facon dans le backoffice.
     */
    private function rattacheCeQuiEstCertain(): void
    {
        $service = fn (string $slug) => DB::table('services')->where('slug', $slug)->value('id');

        $regles = [
            'foncier' => fn ($q) => $q->where('type', 'terrain'),
            'vente' => fn ($q) => $q->where('offre', 'vente'),
            // « gestion » et non « gestion-location » : le slug est plus court
            // que le nom affiche, verifie en base.
            'gestion' => fn ($q) => $q->where('offre', 'location'),
        ];

        foreach ($regles as $slug => $filtre) {
            $identifiant = $service($slug);

            // Le slug depend des donnees, pas du schema : une base ou les
            // services ont ete renommes ne doit pas faire echouer la migration.
            if (! $identifiant) {
                continue;
            }

            $biens = $filtre(DB::table('biens')->where('est_une_realisation', false))->pluck('id');

            foreach ($biens as $bienId) {
                DB::table('bien_service')->insertOrIgnore([
                    'bien_id' => $bienId,
                    'service_id' => $identifiant,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bien_service');

        if (Schema::hasColumn('biens', 'est_une_realisation')) {
            Schema::table('biens', function (Blueprint $table) {
                $table->dropIndex(['est_une_realisation', 'statut']);
                $table->dropColumn('est_une_realisation');
            });
        }
    }
};
