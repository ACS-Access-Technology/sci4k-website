<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

class CategorieSeeder extends Seeder
{
    /** Les sept categories du site. Six correspondent aux six services. */
    public function run(): void
    {
        $categories = [
            ['slug' => 'foncier',        'nom_fr' => 'Foncier',                  'nom_en' => 'Land & Title',           'ordre' => 1],
            ['slug' => 'construction',   'nom_fr' => 'Construction',             'nom_en' => 'Construction',           'ordre' => 2],
            ['slug' => 'gestion',        'nom_fr' => 'Gestion / Location',       'nom_en' => 'Rental Management',      'ordre' => 3],
            ['slug' => 'achat',          'nom_fr' => 'Achat',                    'nom_en' => 'Buying',                 'ordre' => 4],
            ['slug' => 'vente',          'nom_fr' => 'Vente',                    'nom_en' => 'Selling',                'ordre' => 5],
            ['slug' => 'administration', 'nom_fr' => 'Administration de biens',  'nom_en' => 'Property Administration','ordre' => 6],
            ['slug' => 'marche',         'nom_fr' => 'Marché',                   'nom_en' => 'Market',                 'ordre' => 7],
        ];

        foreach ($categories as $c) {
            Categorie::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
