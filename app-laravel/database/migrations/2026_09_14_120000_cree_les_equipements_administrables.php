<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Les equipements passent du texte libre au referentiel partage.
 *
 * Ils vivaient dans une colonne JSON par bien, saisis ligne par ligne dans
 * deux zones de texte. Tant qu'ils n'etaient que des etiquettes d'affichage,
 * rien n'empechait « Piscine », « piscine » et « Piscine privative » de
 * coexister sans consequence. Des qu'ils deviennent des CASES A COCHER du
 * catalogue, il faut au contraire qu'un meme equipement soit une seule valeur,
 * sans quoi chaque case ne ramene qu'une partie des biens concernes.
 *
 * L'existant est donc rapatrie : chaque libelle francais distinct devient une
 * entree du referentiel, et le lien bien <-> equipement passe dans une table
 * de jointure.
 */
return new class extends Migration
{
    private const FAMILLE = 'equipements';

    public function up(): void
    {
        Schema::create('bien_equipement', function (Blueprint $table) {
            $table->foreignId('bien_id')->constrained('biens')->cascadeOnDelete();
            $table->foreignId('referentiel_id')->constrained('referentiels')->cascadeOnDelete();

            // Un bien ne porte un equipement qu'une fois. La contrainte n'est
            // pas cosmetique : le filtre « tous les criteres » COMPTE les
            // correspondances, et un doublon ferait passer un bien pour
            // repondant a deux criteres alors qu'il n'en porte qu'un.
            $table->primary(['bien_id', 'referentiel_id']);
        });

        if (! Schema::hasColumn('biens', 'equipements')) {
            return;
        }

        $this->rapatrierLExistant();

        Schema::table('biens', function (Blueprint $table) {
            $table->dropColumn('equipements');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('biens', 'equipements')) {
            Schema::table('biens', function (Blueprint $table) {
                $table->json('equipements')->nullable();
            });

            // Avant de defaire la table de jointure, pas apres : elle porte la
            // seule trace de ce qui doit repartir dans la colonne.
            $this->rendreLesListes();
        }

        Schema::dropIfExists('bien_equipement');
        DB::table('referentiels')->where('famille', self::FAMILLE)->delete();
    }

    /** Verse les listes JSON dans le referentiel et la table de jointure. */
    private function rapatrierLExistant(): void
    {
        $rang = (int) DB::table('referentiels')->where('famille', self::FAMILLE)->max('ordre');
        $valeursPrises = DB::table('referentiels')->where('famille', self::FAMILLE)->pluck('valeur')->all();

        // Amorce avec ce qui existe deja : la famille est neuve au moment de
        // cette migration, mais repartir d'une table vide ferait recreer une
        // ligne deja presente si elle ne l'etait pas.
        $connus = [];

        foreach (DB::table('referentiels')->where('famille', self::FAMILLE)->get() as $deja) {
            $connus[mb_strtolower(trim((string) $deja->libelle_fr))] = (int) $deja->id;
        }

        $liens = [];

        foreach (DB::table('biens')->select('id', 'equipements')->get() as $bien) {
            $listes = json_decode((string) $bien->equipements, true);

            if (! is_array($listes)) {
                continue;
            }

            $fr = $this->lignesUtiles($listes['fr'] ?? []);
            $en = $this->lignesUtiles($listes['en'] ?? []);

            // L'anglais n'est apparie que si les deux listes se correspondent
            // une a une. Des longueurs differentes signifient qu'on ne SAIT PAS
            // quel mot traduit quel autre : un libelle anglais vide, que le
            // site remplace par le francais, vaut mieux qu'un faux couple.
            $apparier = count($fr) === count($en);

            foreach ($fr as $i => $libelle) {
                $cle = mb_strtolower($libelle);

                if (! isset($connus[$cle])) {
                    $valeur = $this->valeurLibre($libelle, $valeursPrises);
                    $valeursPrises[] = $valeur;

                    $connus[$cle] = (int) DB::table('referentiels')->insertGetId([
                        'famille' => self::FAMILLE,
                        'valeur' => $valeur,
                        'libelle_fr' => $libelle,
                        'libelle_en' => $apparier ? ($en[$i] ?? null) : null,
                        'ordre' => ++$rang,
                        'visible' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Indexe par le couple : un bien qui listait deux fois le meme
                // equipement ne doit pas heurter la cle primaire.
                $liens[$bien->id.'-'.$connus[$cle]] = [
                    'bien_id' => $bien->id,
                    'referentiel_id' => $connus[$cle],
                ];
            }
        }

        foreach (array_chunk(array_values($liens), 500) as $paquet) {
            DB::table('bien_equipement')->insert($paquet);
        }
    }

    /** Refait les listes JSON a partir de la table de jointure. */
    private function rendreLesListes(): void
    {
        $libelles = DB::table('referentiels')
            ->where('famille', self::FAMILLE)
            ->get()
            ->keyBy('id');

        $parBien = [];

        foreach (DB::table('bien_equipement')->orderBy('referentiel_id')->get() as $lien) {
            $equipement = $libelles->get($lien->referentiel_id);

            if (! $equipement) {
                continue;
            }

            $parBien[$lien->bien_id]['fr'][] = $equipement->libelle_fr;

            if ($equipement->libelle_en) {
                $parBien[$lien->bien_id]['en'][] = $equipement->libelle_en;
            }
        }

        foreach ($parBien as $bienId => $listes) {
            DB::table('biens')->where('id', $bienId)->update([
                // Le francais est toujours la : un bien n'entre dans ce tableau
                // que par la ligne qui l'y pose. L'anglais, lui, n'est ajoute
                // que si l'equipement en porte un — d'ou le repli sur la seule
                // liste qui peut manquer.
                'equipements' => json_encode([
                    'fr' => $listes['fr'],
                    'en' => $listes['en'] ?? [],
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * Les lignes non vides d'une liste, debarrassees de leurs espaces.
     *
     * @return list<string>
     */
    private function lignesUtiles(mixed $liste): array
    {
        if (! is_array($liste)) {
            return [];
        }

        $lignes = array_map(static fn ($ligne) => trim((string) $ligne), $liste);

        return array_values(array_filter($lignes, static fn (string $ligne): bool => $ligne !== ''));
    }

    /**
     * Une valeur technique encore libre dans la famille.
     *
     * La base interdit deux fois la meme valeur dans une famille. Le radical
     * est borne AVANT d'etre suffixe : le tronquer apres pourrait ramener deux
     * valeurs distinctes sur la meme chaine.
     *
     * @param  list<string>  $prises
     */
    private function valeurLibre(string $libelle, array $prises): string
    {
        $base = mb_substr(Str::slug($libelle) ?: 'equipement', 0, 50);
        $valeur = $base;
        $suffixe = 2;

        while (in_array($valeur, $prises, true)) {
            $valeur = $base.'-'.$suffixe++;
        }

        return $valeur;
    }
};
