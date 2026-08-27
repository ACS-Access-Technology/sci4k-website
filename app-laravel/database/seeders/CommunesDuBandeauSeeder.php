<?php

namespace Database\Seeders;

use App\Models\CommuneDuBandeau;
use App\Models\ReglageDeSection;
use Illuminate\Database\Seeder;

/**
 * Les sept communes du bandeau, relevees dans frontoffice/index.html.
 *
 * Elles y etaient ecrites en dur, et deux fois — le bandeau defile en boucle.
 * Ce sont donc exactement celles que le visiteur voyait.
 *
 * Cle d'idempotence : le nom, qui est ici la donnee elle-meme et non un
 * libelle susceptible d'etre reformule.
 */
class CommunesDuBandeauSeeder extends Seeder
{
    /** Champs pilotes par l'administration, jamais reecrits. */
    protected const EDITORIAUX = ['ordre', 'visible'];

    public function run(): void
    {
        $communes = ['Cocody', 'Riviera', 'Bingerville', 'Marcory', 'Angré', 'Plateau', 'Abatta'];

        foreach ($communes as $rang => $nom) {
            $commune = CommuneDuBandeau::firstOrNew(['nom' => $nom]);

            if (! $commune->exists) {
                $commune->fill(['ordre' => $rang + 1, 'visible' => true]);
            }

            $commune->save();
        }

        // L'en-tete de section porte les reglages d'apparence. Il n'affiche
        // aucun titre sur le site : le bandeau n'en a pas.
        $section = ReglageDeSection::firstOrNew(['slug' => CommuneDuBandeau::SECTION]);

        if (! $section->exists) {
            $section->fill([
                // Chaine vide et non null : la colonne ne l'accepte pas.
                // Releve par MySQL, que SQLite aurait laisse passer.
                'etiquette_fr' => '',
                'titre_fr' => 'Bandeau des communes',
                'options' => ['fond' => 'sombre', 'separateur' => '·', 'casse' => 'majuscules'],
            ])->save();
        }

        $this->command?->info(sprintf('%d communes du bandeau.', CommuneDuBandeau::count()));
    }
}
