<?php

use App\Models\PageStatique;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le site cesse de collecter l'adresse IP et le type de navigateur.
 *
 * La politique de confidentialite promet, en gras : « Aucune adresse IP n'est
 * conservee ». Le controleur des commentaires en enregistrait pourtant une a
 * chaque message. La page publiee disait donc faux, sur le point precis qu'elle
 * avait pris la peine de mettre en avant.
 *
 * Les deux colonnes retirees ici n'etaient LUES NULLE PART : ni le backoffice,
 * ni l'ecran de frequentation, ni la moderation des commentaires ne les
 * regardaient. Le filtre de mise en attente juge le contenu du message, pas son
 * origine. On ne perd donc aucun usage — on cesse de conserver ce dont personne
 * ne se servait, ce qui est aussi la regle la plus simple a tenir dans le
 * temps : une donnee qu'on ne detient pas ne peut ni fuiter, ni etre reclamee,
 * ni contredire une politique.
 *
 * `session_hash` reste : c'est l'empreinte d'un identifiant que le site a
 * lui-meme tire au sort, et non une donnee prise au visiteur. Il compte les
 * visiteurs distincts, et la politique le decrit deja.
 */
return new class extends Migration
{
    /**
     * La phrase de la politique qui annonce la collecte du navigateur.
     *
     * Le remplacement est cible plutot que global : la page est editable depuis
     * le backoffice, et reecrire son contenu entier effacerait le travail de
     * l'editeur. Une phrase absente — parce que quelqu'un l'a deja reformulee —
     * laisse simplement le texte tel quel.
     */
    private const CORRECTIONS = [
        'contenu_fr' => [
            "la date et l'heure, le type de navigateur, et un identifiant de session"
                => "la date et l'heure, et un identifiant de session",
        ],
        'contenu_en' => [
            'the date and time, the browser type, and a session identifier'
                => 'the date and time, and a session identifier',
        ],
    ];

    public function up(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            $table->dropColumn('adresse_ip');
        });

        Schema::table('visites', function (Blueprint $table) {
            $table->dropColumn('user_agent');
        });

        $this->corrigerLaPolitique(self::CORRECTIONS);
    }

    public function down(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            $table->string('adresse_ip', 45)->nullable();
        });

        Schema::table('visites', function (Blueprint $table) {
            $table->string('user_agent', 255)->nullable();
        });

        $this->corrigerLaPolitique(array_map('array_flip', self::CORRECTIONS));
    }

    /**
     * @param  array<string, array<string, string>>  $corrections
     */
    private function corrigerLaPolitique(array $corrections): void
    {
        $page = PageStatique::where('slug', 'politique-confidentialite')->first();

        if (! $page) {
            return;
        }

        foreach ($corrections as $colonne => $remplacements) {
            $page->$colonne = str_replace(
                array_keys($remplacements),
                array_values($remplacements),
                (string) $page->$colonne,
            );
        }

        $page->save();
    }
};
