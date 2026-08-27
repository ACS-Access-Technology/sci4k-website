<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // « redacteur » vient de la maquette des utilisateurs. Ce n'est pas un
        // role decoratif : il ne peut pas publier, et ne voit que ses propres
        // articles. Voir User::roles().
        foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $nom) {
            Role::findOrCreate($nom);
        }
    }
}
