<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['administrateur', 'editeur', 'lecteur'] as $nom) {
            Role::findOrCreate($nom);
        }
    }
}
