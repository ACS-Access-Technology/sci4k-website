<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seuls les referentiels sont semes ici : roles et categories, qui ne
        // portent aucun texte editorial.
        //
        // Les seeders d'IMPORT en sont volontairement absents —
        // ArticleImportSeeder depuis le lot 1, ServiceFaqSeeder et
        // BlocsDeContenuSeeder depuis le lot 2. Ils reecrivent le contenu du
        // site depuis les fichiers de database/data/, ce qui est le comportement
        // voulu quand on les lance expressement, et destructeur quand un
        // `db:seed` de routine les emporte : les corrections faites depuis
        // l'administration seraient defaites sans que personne ne le demande.
        //
        // ReferentielsSeeder les rejoint : il reecrit les libelles francais et
        // anglais a chaque passage, donc il defairait le renommage d'une zone
        // ou d'un type de bien fait depuis l'administration.
        //
        //   php artisan db:seed --class=ServiceFaqSeeder
        //   php artisan db:seed --class=BlocsDeContenuSeeder
        //   php artisan db:seed --class=ReferentielsSeeder
        //   php artisan db:seed --class=MenusSeeder
        $this->call([
            RoleSeeder::class,
            CategorieSeeder::class,
        ]);
    }
}
