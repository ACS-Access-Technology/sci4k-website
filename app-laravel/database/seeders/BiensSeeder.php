<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Referentiel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Reprend les six biens ecrits en dur dans frontoffice/assets/main.js.
 *
 * Ce sont exactement ceux que le visiteur voit aujourd'hui, dans les deux
 * langues. Rien n'est invente : ni prix — le site n'en affiche aucun — ni
 * photo, les six etant des illustrations dessinees.
 *
 * La SURFACE est extraite du texte (« 310 m² ») pour devenir un nombre, et la
 * tranche s'en deduit. Le fichier d'import porte aussi la tranche telle que le
 * site la stockait : le seeder VERIFIE que les deux concordent, et refuse
 * l'import sinon. C'est ce controle qui a montre que la deduction tenait sur
 * les six.
 *
 * Cle d'idempotence : le slug, derive du titre a la premiere importation puis
 * stable. Rejouable sans ecraser le travail editorial — `ordre`, `statut` et
 * `en_avant` ne sont poses qu'a la creation.
 */
class BiensSeeder extends Seeder
{
    /** Champs pilotes par l'administration, jamais reecrits. */
    protected const EDITORIAUX = ['ordre', 'statut', 'en_avant', 'urgent', 'date_mise_en_ligne'];

    public function run(): void
    {
        $chemin = database_path('data/biens.json');

        if (! is_file($chemin)) {
            throw new \RuntimeException("Donnees d'import introuvables : biens.json.");
        }

        $entrees = json_decode(file_get_contents($chemin), true);

        if (! $entrees) {
            throw new \RuntimeException("Donnees d'import illisibles ou vides : biens.json.");
        }

        $rang = 0;

        foreach ($entrees as $entree) {
            $this->verifierLeVocabulaire($entree);

            $surface = $this->surfaceEnMetres($entree['surface_texte_fr'] ?? '');
            $slug = Str::slug($entree['titre_fr']);

            $bien = Bien::firstOrNew(['slug' => $slug]);

            // Un terrain n'a pas de pieces. Le site lui posait 1, ce qui le
            // faisait remonter dans le filtre « 1 a 2 pieces ».
            $pieces = $entree['type'] === 'terrain' ? null : ($entree['nombre_pieces'] ?: null);

            $bien->fill([
                'titre_fr' => $entree['titre_fr'],
                'titre_en' => $entree['titre_en'],
                'sous_titre_fr' => $entree['sous_titre_fr'],
                'sous_titre_en' => $entree['sous_titre_en'],
                'description_fr' => $entree['description_fr'],
                'description_en' => $entree['description_en'],
                'type' => $entree['type'],
                'offre' => $entree['offre'],
                'zone' => $entree['zone'],
                'quartier' => $this->quartier($entree['localisation_fr'] ?? ''),
                'statut_juridique' => $this->statutJuridique($entree['statut_juridique_texte_fr'] ?? ''),
                'nombre_pieces' => $pieces,
                // La surface d'un terrain nu est celle de la parcelle.
                'surface_habitable' => $entree['type'] === 'terrain' ? null : $surface,
                'surface_terrain' => $entree['type'] === 'terrain' ? $surface : null,
            ]);

            if (! $bien->exists) {
                $bien->fill([
                    'ordre' => ++$rang,
                    'statut' => Bien::PUBLIE,
                    'date_mise_en_ligne' => now()->toDateString(),
                ]);
            }

            $bien->save();

            $this->rattacherLesEquipements($bien, $entree);
            $this->verifierLaTranche($bien, $entree);
        }

        $this->command?->info(sprintf('%d biens au catalogue.', Bien::count()));
    }

    /**
     * Verse les equipements du fichier d'import au referentiel, puis les lie.
     *
     * Ils etaient ecrits dans une colonne JSON du bien. Depuis qu'ils sont
     * aussi les cases a cocher du catalogue, ils appartiennent au vocabulaire
     * partage : deux biens qui mentionnent « Piscine privative » doivent
     * pointer LA MEME entree, sans quoi la case ne ramene que l'un des deux.
     *
     * Le rattachement est rejouable : l'appariement se fait sur le libelle
     * francais, et sync() remet exactement la liste du fichier.
     */
    protected function rattacherLesEquipements(Bien $bien, array $entree): void
    {
        $fr = array_values(array_filter(array_map('trim', $entree['equipements_fr'] ?? [])));
        $en = array_values(array_filter(array_map('trim', $entree['equipements_en'] ?? [])));

        // L'anglais n'est repris que si les deux listes se correspondent une a
        // une : des longueurs differentes ne disent pas quel mot traduit quel
        // autre.
        $apparier = count($fr) === count($en);
        $ids = [];

        foreach ($fr as $i => $libelle) {
            $equipement = Referentiel::equipementNomme($libelle);

            if (! $equipement) {
                $equipement = Referentiel::create([
                    'famille' => 'equipements',
                    'valeur' => $this->valeurLibrePour($libelle),
                    'libelle_fr' => $libelle,
                    'libelle_en' => $apparier ? ($en[$i] ?? null) : null,
                    'ordre' => Referentiel::rangSuivantDans('equipements'),
                    'visible' => true,
                ]);
            }

            $ids[] = $equipement->id;
        }

        $bien->equipements()->sync($ids);
    }

    /** Une valeur technique encore libre dans la famille des equipements. */
    protected function valeurLibrePour(string $libelle): string
    {
        $prises = Referentiel::deLaFamille('equipements')->pluck('valeur')->all();
        $base = mb_substr(Str::slug($libelle) ?: 'equipement', 0, 50);
        $valeur = $base;
        $suffixe = 2;

        while (in_array($valeur, $prises, true)) {
            $valeur = $base.'-'.$suffixe++;
        }

        return $valeur;
    }

    /**
     * Les valeurs de type, d'offre et de zone doivent exister au referentiel.
     *
     * Sans ce controle, un bien entrerait avec un type qu'aucun filtre ne
     * propose : il serait en ligne et introuvable.
     */
    protected function verifierLeVocabulaire(array $entree): void
    {
        foreach (['type' => 'types_de_bien', 'zone' => 'zones'] as $champ => $famille) {
            $connu = Referentiel::deLaFamille($famille)->where('valeur', $entree[$champ])->exists();

            if (! $connu) {
                throw new \RuntimeException(
                    "Valeur « {$entree[$champ]} » absente du referentiel « $famille ». ".
                    'Lancez ReferentielsSeeder avant BiensSeeder.'
                );
            }
        }

        if (! in_array($entree['offre'], [Bien::VENTE, Bien::LOCATION], true)) {
            throw new \RuntimeException("Offre inconnue : {$entree['offre']}.");
        }
    }

    /**
     * La tranche calculee doit retrouver celle que le site stockait.
     *
     * C'est le controle qui justifie de ne PAS stocker la tranche : s'il passe,
     * la deduction remplace la colonne sans rien perdre.
     */
    protected function verifierLaTranche(Bien $bien, array $entree): void
    {
        $attendue = $entree['tranche_surface'] ?? null;

        if ($attendue && $bien->trancheDeSurface() !== $attendue) {
            throw new \RuntimeException(sprintf(
                'Tranche de surface incoherente pour « %s » : le site disait « %s », la surface de %s m² donne « %s ».',
                $bien->titre_fr, $attendue, $bien->surface_habitable ?? $bien->surface_terrain, $bien->trancheDeSurface()
            ));
        }
    }

    /** « 310 m² » devient 310. */
    protected function surfaceEnMetres(string $texte): ?int
    {
        preg_match('/([\d\s]+)\s*m/u', $texte, $trouve);

        if (! $trouve) {
            return null;
        }

        $nombre = (int) preg_replace('/\s/u', '', $trouve[1]);

        return $nombre ?: null;
    }

    /**
     * « 📍 Riviera Golf, Cocody, Abidjan » devient « Riviera Golf ».
     *
     * Le premier segment est le quartier ; les suivants sont la commune et la
     * ville, deja portees par la zone.
     */
    protected function quartier(string $localisation): ?string
    {
        $sans = trim(preg_replace('/^[^\p{L}]+/u', '', $localisation));
        $segments = array_map('trim', explode(',', $sans));

        return $segments[0] ?: null;
    }

    /**
     * Rattache le texte juridique du site a une valeur du referentiel.
     *
     * Le site ecrit des phrases — « Arrêté de Concession Définitive (ACD) »,
     * « ACD Notarié & Découpage individuel » — la ou le referentiel a quatre
     * valeurs. Rend null quand aucune ne s'impose, plutot que d'en choisir une
     * au hasard : mieux vaut un champ vide qu'un statut juridique invente sur
     * une annonce immobiliere.
     */
    protected function statutJuridique(string $texte): ?string
    {
        $minuscules = mb_strtolower($texte);

        return match (true) {
            str_contains($minuscules, 'titre foncier') => 'titre-foncier',
            str_contains($minuscules, 'concession') => 'arrete-concession',
            str_contains($minuscules, 'attribution') => 'lettre-attribution',
            str_contains($minuscules, 'acd') => 'acd-disponible',
            default => null,
        };
    }
}
