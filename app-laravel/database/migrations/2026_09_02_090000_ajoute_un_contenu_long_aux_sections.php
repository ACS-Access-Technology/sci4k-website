<?php

use App\Models\ReglageDeSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un vrai champ de corps de texte pour les sections qui en ont un.
 *
 * Deux blocs de la page Presentation — la presentation generale et le mot du
 * directeur — portent plusieurs paragraphes. Ils logeaient jusqu'ici dans
 * « chapo », un champ prevu pour UNE phrase d'accroche, decoupe en paragraphes
 * sur les doubles sauts de ligne. Ca fonctionnait, mais l'intitule mentait sur
 * l'usage, et rien ne disait a l'editeur que sa mise en forme dependait de
 * lignes vides.
 *
 * Le contenu existant est RECOPIE, pas deplace : « chapo » garde sa valeur.
 * Les vues lisent « contenu » et retombent sur « chapo » s'il est vide, de
 * sorte qu'une base non migree — ou un bloc qui n'a jamais eu de corps de
 * texte — continue de s'afficher comme avant.
 */
return new class extends Migration
{
    /** Les sections dont le chapo est en realite un corps de texte. */
    private const SECTIONS = ['about.overview', 'about.dg'];

    public function up(): void
    {
        Schema::table('reglages_de_section', function (Blueprint $table) {
            $table->longText('contenu_fr')->nullable()->after('chapo_en');
            $table->longText('contenu_en')->nullable()->after('contenu_fr');
        });

        foreach (self::SECTIONS as $slug) {
            $section = ReglageDeSection::where('slug', $slug)->first();

            if (! $section) {
                continue;
            }

            $section->contenu_fr = $section->chapo_fr;
            $section->contenu_en = $section->chapo_en;
            $section->save();
        }
    }

    public function down(): void
    {
        Schema::table('reglages_de_section', function (Blueprint $table) {
            $table->dropColumn(['contenu_fr', 'contenu_en']);
        });
    }
};
