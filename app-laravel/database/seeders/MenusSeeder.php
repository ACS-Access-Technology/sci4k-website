<?php

namespace Database\Seeders;

use App\Models\EntreeDeMenu;
use Illuminate\Database\Seeder;

/**
 * Reprend les menus tels qu'ils etaient ecrits dans les gabarits.
 *
 * Les dix-sept entrees sont relevees dans entete.blade.php et pied.blade.php,
 * ou elles etaient en dur : le visiteur retrouve exactement la meme navigation
 * apres le portage.
 *
 * La cle d'idempotence est le couple (menu, cible) et non le libelle :
 * renommer « Biens Immobiliers » en « Nos biens » ne doit pas creer une
 * seconde entree au prochain import.
 *
 * Rejouable sans ecraser le travail editorial : `ordre` et `visible` ne sont
 * poses qu'a la creation.
 */
class MenusSeeder extends Seeder
{
    /** Champs pilotes par l'administration, jamais reecrits. */
    protected const EDITORIAUX = ['ordre', 'visible'];

    public function run(): void
    {
        $chemin = database_path('data/menus.json');

        if (! is_file($chemin)) {
            throw new \RuntimeException("Donnees d'import introuvables : menus.json.");
        }

        $entrees = json_decode(file_get_contents($chemin), true);

        if (! $entrees) {
            throw new \RuntimeException("Donnees d'import illisibles ou vides : menus.json.");
        }

        foreach ($entrees as $entree) {
            foreach (['menu', 'cible'] as $obligatoire) {
                if (! array_key_exists($obligatoire, $entree)) {
                    throw new \RuntimeException("Cle « $obligatoire » absente d'une entree de menus.json.");
                }
            }

            if (! EntreeDeMenu::menuConnu($entree['menu'])) {
                throw new \RuntimeException("Menu inconnu dans menus.json : {$entree['menu']}.");
            }

            // Le fichier d'import est une source de confiance, mais il traverse
            // les memes yeux qu'un formulaire : une cible qui ne passerait pas
            // la validation de l'ecran ne doit pas entrer par la porte de
            // service.
            if (! EntreeDeMenu::cibleAcceptable($entree['cible'])) {
                throw new \RuntimeException("Cible refusee dans menus.json : {$entree['cible']}.");
            }

            $element = EntreeDeMenu::firstOrNew([
                'menu' => $entree['menu'],
                'cible' => $entree['cible'],
            ]);

            $element->fill(array_diff_key($entree, array_flip(self::EDITORIAUX)));

            if (! $element->exists) {
                $element->fill(array_intersect_key($entree, array_flip(self::EDITORIAUX)));
            }

            $element->save();
        }

        $this->command?->info(sprintf('%d entrees de menu.', EntreeDeMenu::count()));
    }
}
