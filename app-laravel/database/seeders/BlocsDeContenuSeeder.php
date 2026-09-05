<?php

namespace Database\Seeders;

use App\Models\ChiffreCle;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Reprend les neuf familles de blocs de l'accueil et de la presentation.
 *
 * Rejouable, et SANS ECRASER LE TRAVAIL EDITORIAL — meme regle qu'au lot 2a,
 * ou un `db:seed` de routine remettait l'ordre du glisser-deposer a celui du
 * site et reaffichait les elements masques. Les champs que l'administration
 * pilote — `ordre`, `visible` — ne sont poses qu'a la CREATION ; ensuite ils
 * appartiennent a l'editeur.
 *
 * Chaque famille a une cle stable, choisie pour ne pas bouger quand on
 * reordonne : un slug quand il en existe un, le nom propre sinon. La cle
 * (famille, rang) du premier jet du lot 2a s'etait revelee fragile, le rang
 * changeant au premier glisser-deposer.
 */
class BlocsDeContenuSeeder extends Seeder
{
    /** Champs pilotes par l'administration, jamais reecrits. */
    protected const EDITORIAUX = ['ordre', 'visible'];

    public function run(): void
    {
        $this->semer(ReglageDeSection::class, 'reglages-de-section.json', 'slug');
        $this->semer(Temoignage::class, 'temoignages.json', 'auteur');
        $this->semer(Partenaire::class, 'partenaires.json', 'nom');
        $this->semer(MembreEquipe::class, 'equipe.json', 'nom');
        $this->semer(Encart::class, 'encarts.json', 'slug');
        $this->semer(ImageDeFond::class, 'images-de-fond.json', 'slug');
        // Les trois ensembles figes sont cles sur leur RANG, et non sur un
        // texte : leur nombre ne change pas et l'ecran ne les reordonne pas,
        // si bien que le rang est stable. Les cler sur un titre aurait cree un
        // doublon des qu'un editeur renomme une valeur puis rejoue l'import.
        $this->semer(Valeur::class, 'valeurs.json', 'ordre');
        $this->semer(ChiffreCle::class, 'chiffres-cles.json', 'ordre');
        $this->semer(EtapeProcessus::class, 'etapes-processus.json', 'ordre');

        $this->command?->info(sprintf(
            '%d reglages, %d temoignages, %d partenaires, %d membres, %d encarts, '.
            '%d images de fond, %d valeurs, %d chiffres, %d etapes.',
            ReglageDeSection::count(), Temoignage::count(), Partenaire::count(),
            MembreEquipe::count(), Encart::count(), ImageDeFond::count(),
            Valeur::count(), ChiffreCle::count(), EtapeProcessus::count(),
        ));
    }

    /**
     * @param  class-string<Model>  $modele
     */
    protected function semer(string $modele, string $fichier, string $cle): void
    {
        $chemin = database_path('data/'.$fichier);

        if (! is_file($chemin)) {
            throw new \RuntimeException("Donnees d'import introuvables : $fichier.");
        }

        $entrees = json_decode(file_get_contents($chemin), true);

        if (! $entrees) {
            throw new \RuntimeException("Donnees d'import illisibles ou vides : $fichier.");
        }

        foreach ($entrees as $entree) {
            if (! array_key_exists($cle, $entree)) {
                throw new \RuntimeException("Cle « $cle » absente d'une entree de $fichier.");
            }

            $element = $modele::firstOrNew([$cle => $entree[$cle]]);

            $element->fill(array_diff_key($entree, array_flip(self::EDITORIAUX)));

            if (! $element->exists) {
                $element->fill(array_intersect_key($entree, array_flip(self::EDITORIAUX)));
            }

            $element->save();
        }
    }
}
