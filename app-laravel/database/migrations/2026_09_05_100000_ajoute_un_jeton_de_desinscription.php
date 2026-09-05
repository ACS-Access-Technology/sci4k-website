<?php

use App\Models\AbonneNewsletter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Chaque abonne recoit un jeton, et donc une adresse de desinscription.
 *
 * On pouvait s'inscrire a la lettre d'information, et l'ecran du backoffice
 * pouvait desinscrire quelqu'un — mais l'interesse lui-meme n'avait aucun
 * moyen de partir. Il devait ecrire a l'agence et attendre qu'on le fasse pour
 * lui. C'est le sens meme du droit de retrait : il ne doit dependre de
 * personne.
 *
 * Le jeton, et non l'adresse, dans l'URL : une adresse dans un lien se
 * retrouve dans les journaux du serveur, dans l'historique du navigateur et
 * chez tout intermediaire, et un lien construit sur l'adresse permettrait de
 * desinscrire n'importe qui en la devinant.
 *
 * L'envoi des lettres se fait depuis un outil externe, alimente par l'export
 * CSV de l'ecran des abonnes. Le lien voyage donc avec l'export, une colonne
 * par ligne : c'est la seule facon pour que l'outil d'envoi puisse le poser
 * dans le pied de chaque message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnes_newsletter', function (Blueprint $table) {
            $table->string('jeton', 64)->nullable()->unique()->after('email');
        });

        // Les inscrits d'avant la migration en recoivent un aussi : sans quoi
        // ceux-la seraient precisement les seuls a ne pas pouvoir partir.
        AbonneNewsletter::whereNull('jeton')->each(function (AbonneNewsletter $abonne) {
            $abonne->forceFill(['jeton' => Str::random(48)])->save();
        });
    }

    public function down(): void
    {
        Schema::table('abonnes_newsletter', function (Blueprint $table) {
            $table->dropColumn('jeton');
        });
    }
};
