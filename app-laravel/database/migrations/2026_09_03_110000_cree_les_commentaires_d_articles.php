<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les commentaires d'articles.
 *
 * Ils paraissent SANS attendre une approbation : c'est le choix du client. La
 * moderation est donc « independante » a deux titres — une machine ecarte
 * d'elle-meme ce qui ressemble a du courrier indesirable, avant toute lecture
 * humaine, et un ecran dedie permet ensuite de retirer ce qui est passe.
 *
 * `statut` porte cette distinction :
 *   - `publie`   : visible sur le site ;
 *   - `en_attente` : mis de cote par le filtre automatique, invisible tant
 *                    qu'un editeur ne l'a pas approuve ;
 *   - `rejete`   : ecarte par un editeur, conserve pour qu'un meme auteur ne
 *                  revienne pas indefiniment sans qu'on le sache.
 *
 * `parent_id` porte les reponses, sur UN SEUL niveau : au-dela, un fil de
 * discussion devient illisible sur telephone, et la page d'un article n'est
 * pas un forum. La contrainte s'efface en cascade — supprimer un commentaire
 * emporte ses reponses, qui n'auraient plus de question.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commentaires', function (Blueprint $table) {
            $table->id();

            $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('commentaires')->cascadeOnDelete();

            $table->string('auteur', 120);
            $table->string('email', 160);
            $table->text('message');

            $table->string('statut', 20)->default('publie')->index();

            /*
             * Pourquoi le filtre a mis ce commentaire de cote. Affiche a
             * l'editeur : « en attente » sans raison ne lui dit pas s'il doit
             * s'inquieter ou approuver d'un clic.
             */
            $table->string('motif_de_mise_en_attente', 80)->nullable();

            /*
             * L'adresse IP sert a reperer une meme source qui inonde. Elle est
             * une donnee personnelle : elle n'est jamais affichee sur le site,
             * et le nettoyage des vieux commentaires l'emporte avec eux.
             */
            $table->string('adresse_ip', 45)->nullable();

            $table->timestamps();

            // La page d'un article lit ses commentaires publies, du plus ancien
            // au plus recent : c'est cet index-la qu'elle emprunte.
            $table->index(['article_id', 'statut', 'created_at']);
        });

        Schema::table('articles', function (Blueprint $table) {
            /*
             * Les commentaires se ferment article par article. Ouverts par
             * defaut : un article publie avant cette migration continue de se
             * comporter comme le reste du site.
             */
            $table->boolean('commentaires_ouverts')->default(true)->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('commentaires_ouverts');
        });

        Schema::dropIfExists('commentaires');
    }
};
