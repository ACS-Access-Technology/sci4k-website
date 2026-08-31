<?php

use App\Models\Encart;
use App\Models\ReglageDeSection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // L'annonce reelle de l'accueil vivait dans ReglageDeSection (ad.house).
        // On la recree comme encart pour la gerer depuis « Annonces & Actions ».
        $ancienne = ReglageDeSection::where('slug', 'ad.house')->first();

        Encart::updateOrCreate(
            ['slug' => 'accueil.annonce'],
            [
                'visible' => true,
                'ordre' => 0,
                'etiquette_fr' => $ancienne?->etiquette_fr ?: 'Annonce',
                'etiquette_en' => $ancienne?->etiquette_en ?: 'Announcement',
                'titre_fr' => $ancienne?->titre_fr ?: '',
                'titre_en' => $ancienne?->titre_en ?: '',
                'texte_fr' => $ancienne?->chapo_fr ?: '',
                'texte_en' => $ancienne?->chapo_en ?: '',
                'libelle_bouton_fr' => 'Voir les parcelles',
                'libelle_bouton_en' => 'View plots',
                'cible_bouton' => '/biens',
            ]
        );
    }

    public function down(): void
    {
        Encart::where('slug', 'accueil.annonce')->delete();
    }
};
