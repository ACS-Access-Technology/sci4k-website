<?php

use App\Models\Encart;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Encart::updateOrCreate(
            ['slug' => 'services.annonce'],
            [
                'visible' => true,
                'ordre' => 0,
                'etiquette_fr' => 'Offre spéciale',
                'etiquette_en' => 'Special offer',
                'titre_fr' => 'Réductions de fin d\'année',
                'titre_en' => 'End-of-year discounts',
                'texte_fr' => 'Profitez de nos offres exceptionnelles sur la gestion locative et la création de votre SCI.',
                'texte_en' => 'Take advantage of our exceptional offers on rental management and creating your SCI.',
                'libelle_bouton_fr' => 'En savoir plus',
                'libelle_bouton_en' => 'Learn more',
                'cible_bouton' => '/contact',
            ]
        );
    }

    public function down(): void
    {
        Encart::where('slug', 'services.annonce')->delete();
    }
};
