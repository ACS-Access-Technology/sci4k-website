<?php

namespace Database\Seeders;

use App\Models\Referentiel;
use Illuminate\Database\Seeder;

/**
 * Reprend les valeurs des filtres de la page des biens.
 *
 * Elles sont relevees dans le HTML de frontoffice/biens.html, ou elles etaient
 * ecrites en dur : ce sont donc exactement celles que le visiteur voit
 * aujourd'hui, et non une liste reinventee.
 *
 * Rejouable et SANS ECRASER LE TRAVAIL EDITORIAL, meme regle que les autres
 * imports : `ordre` et `visible` ne sont poses qu'a la creation. Sans cela un
 * `db:seed` de routine remettrait l'ordre du glisser-deposer a celui du
 * fichier, et reafficherait les valeurs qu'un editeur avait masquees.
 *
 * La cle d'idempotence est le COUPLE (famille, valeur technique), et pas le
 * libelle : renommer « Villa & Duplex » en « Villas » ne doit pas creer un
 * doublon au prochain import. C'est la leçon du lot 2, ou les valeurs cles sur
 * leur titre se dedoublaient des le premier renommage.
 */
class ReferentielsSeeder extends Seeder
{
    /** Champs pilotes par l'administration, jamais reecrits. */
    protected const EDITORIAUX = ['ordre', 'visible'];

    public function run(): void
    {
        $chemin = database_path('data/referentiels.json');

        if (! is_file($chemin)) {
            throw new \RuntimeException("Donnees d'import introuvables : referentiels.json.");
        }

        $entrees = json_decode(file_get_contents($chemin), true);

        if (! $entrees) {
            throw new \RuntimeException("Donnees d'import illisibles ou vides : referentiels.json.");
        }

        foreach ($entrees as $entree) {
            foreach (['famille', 'valeur'] as $obligatoire) {
                if (! array_key_exists($obligatoire, $entree)) {
                    throw new \RuntimeException("Cle « $obligatoire » absente d'une entree de referentiels.json.");
                }
            }

            if (! Referentiel::familleConnue($entree['famille'])) {
                throw new \RuntimeException("Famille inconnue dans referentiels.json : {$entree['famille']}.");
            }

            $element = Referentiel::firstOrNew([
                'famille' => $entree['famille'],
                'valeur' => $entree['valeur'],
            ]);

            $element->fill(array_diff_key($entree, array_flip(self::EDITORIAUX)));

            if (! $element->exists) {
                $element->fill(array_intersect_key($entree, array_flip(self::EDITORIAUX)));
            }

            $element->save();
        }

        $this->command?->info(sprintf('%d valeurs de referentiel.', Referentiel::count()));
    }
}
