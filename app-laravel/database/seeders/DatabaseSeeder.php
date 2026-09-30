<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Ce que `php artisan db:seed` pose : de quoi faire tourner le site, et
     * rien d'autre. Sur de la production comme ailleurs.
     *
     * AUCUN COMPTE. Ce seeder creait « test@example.com », mot de passe
     * « password », actif : un identifiant public sur toute base qui l'avait
     * recu. Le premier administrateur se cree desormais expressement, avec un
     * mot de passe que personne d'autre ne connait :
     *
     *     php artisan compte:creer-administrateur
     *
     * Trois natures de donnees, trois portes :
     *
     *   - la STRUCTURE (roles, categories, referentiels, menus, services, FAQ,
     *     textes des sections) : ici. Rejouable sans risque — elle ne remplit
     *     que des tables vides, voir StructureSeeder ;
     *   - la DEMONSTRATION (contenu de la maquette, biens et realisations
     *     fictifs) : `db:seed --class=DemonstrationSeeder`, jamais par defaut ;
     *   - les donnees de TEST : les fabriques de database/factories, que seuls
     *     les tests emploient.
     *
     * Les seeders d'import restent disponibles pour realigner expressement les
     * textes sur database/data/ — ce qui DEFAIT les corrections faites depuis
     * l'administration :
     *
     *   php artisan db:seed --class=ServiceFaqSeeder
     *   php artisan db:seed --class=BlocsDeContenuSeeder
     *   php artisan db:seed --class=ReferentielsSeeder
     *   php artisan db:seed --class=MenusSeeder
     *   php artisan db:seed --class=CommunesDuBandeauSeeder
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            StructureSeeder::class,
        ]);
    }
}
