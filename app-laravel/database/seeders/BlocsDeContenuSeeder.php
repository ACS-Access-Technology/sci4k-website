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

    /**
     * Les neuf familles : modele, fichier d'import, cle d'idempotence.
     *
     * Chaque famille a une cle stable, choisie pour ne pas bouger quand on
     * reordonne. Les trois ensembles figes — valeurs, chiffres, etapes — sont
     * cles sur leur RANG, et non sur un texte : leur nombre ne change pas et
     * l'ecran ne les reordonne pas, si bien que le rang est stable. Les cler
     * sur un titre aurait cree un doublon des qu'un editeur renomme une valeur
     * puis rejoue l'import.
     *
     * Nommees, pour que StructureSeeder et DemonstrationSeeder puissent en
     * semer une partie : toutes ne sont pas de meme nature. Voir ces deux
     * classes pour le partage.
     *
     * @var array<string, array{0: class-string<Model>, 1: string, 2: string}>
     */
    public const FAMILLES = [
        'reglages-de-section' => [ReglageDeSection::class, 'reglages-de-section.json', 'slug'],
        'temoignages' => [Temoignage::class, 'temoignages.json', 'auteur'],
        'partenaires' => [Partenaire::class, 'partenaires.json', 'nom'],
        'equipe' => [MembreEquipe::class, 'equipe.json', 'nom'],
        'encarts' => [Encart::class, 'encarts.json', 'slug'],
        'images-de-fond' => [ImageDeFond::class, 'images-de-fond.json', 'slug'],
        'valeurs' => [Valeur::class, 'valeurs.json', 'ordre'],
        'chiffres-cles' => [ChiffreCle::class, 'chiffres-cles.json', 'ordre'],
        'etapes-processus' => [EtapeProcessus::class, 'etapes-processus.json', 'ordre'],
    ];

    public function run(): void
    {
        foreach (array_keys(self::FAMILLES) as $famille) {
            self::semerLaFamille($famille);
        }

        $this->command?->info(sprintf(
            '%d reglages, %d temoignages, %d partenaires, %d membres, %d encarts, '.
            '%d images de fond, %d valeurs, %d chiffres, %d etapes.',
            ReglageDeSection::count(), Temoignage::count(), Partenaire::count(),
            MembreEquipe::count(), Encart::count(), ImageDeFond::count(),
            Valeur::count(), ChiffreCle::count(), EtapeProcessus::count(),
        ));
    }

    /**
     * Seme une famille.
     *
     * $sansModifier : ne cree que les elements absents et ne touche a AUCUN
     * element existant, textes compris. C'est le mode d'une installation qui
     * complete une base, par opposition a l'import, qui realigne les textes sur
     * le fichier.
     *
     * Statique : elle ne depend que des fichiers et des modeles, et les autres
     * seeders l'appellent sans avoir a instancier celui-ci.
     */
    public static function semerLaFamille(string $famille, bool $sansModifier = false): void
    {
        [$modele, $fichier, $cle] = self::FAMILLES[$famille]
            ?? throw new \InvalidArgumentException("Famille de blocs inconnue : $famille.");

        self::semer($modele, $fichier, $cle, $sansModifier);
    }

    /**
     * @param  class-string<Model>  $modele
     */
    protected static function semer(string $modele, string $fichier, string $cle, bool $sansModifier = false): void
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

            if ($sansModifier && $element->exists) {
                continue;
            }

            $element->fill(array_diff_key($entree, array_flip(self::EDITORIAUX)));

            if (! $element->exists) {
                $element->fill(array_intersect_key($entree, array_flip(self::EDITORIAUX)));
            }

            $element->save();
        }
    }
}
