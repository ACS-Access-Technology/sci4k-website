<?php

use App\Models\ImageDeFond;
use Illuminate\Database\Migrations\Migration;

/**
 * Rend editables les deux illustrations de la page Presentation.
 *
 * Elles etaient ecrites en dur dans le gabarit : les changer demandait de
 * toucher au code, puis de relancer la synchronisation du frontoffice. Tous
 * les autres visuels du site se pilotent depuis « Images de fond » ; elles y
 * entrent a leur tour.
 *
 * Une migration plutot qu'un passage de seeder : BlocsDeContenuSeeder reecrit
 * TOUTES les images depuis le fichier de donnees, et effacerait donc les fonds
 * deja televerses depuis l'administration. Ici on ne touche qu'aux deux lignes
 * ajoutees, et seulement si elles n'existent pas.
 */
return new class extends Migration
{
    /**
     * Les valeurs de depart : celles que le gabarit affichait en dur, pour que
     * la page rende exactement la meme chose avant toute intervention.
     */
    private const VISUELS = [
        'presentation-apercu' => [
            'ordre' => 21,
            'fichier' => 'images/presentation/apercu.jpg',
            'texte_alternatif_fr' => 'Immobilier Abidjan',
            'texte_alternatif_en' => 'Real estate in Abidjan',
        ],
        'presentation-directeur' => [
            'ordre' => 22,
            'fichier' => 'images/presentation/silhouette.svg',
            'texte_alternatif_fr' => 'Portrait du Directeur Général',
            'texte_alternatif_en' => 'Portrait of the Managing Director',
        ],
    ];

    public function up(): void
    {
        foreach (self::VISUELS as $slug => $valeurs) {
            // firstOrCreate et non updateOrCreate : sur une base ou ces lignes
            // existent deja, on ne remplace pas un fichier televerse par le
            // fichier d'origine.
            ImageDeFond::firstOrCreate(
                ['slug' => $slug],
                $valeurs + ['visible' => true],
            );
        }
    }

    public function down(): void
    {
        ImageDeFond::whereIn('slug', array_keys(self::VISUELS))->delete();
    }
};
