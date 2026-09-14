<?php

use App\Livewire\Admin\BienFormulaire;
use App\Livewire\Admin\BienListe;
use App\Models\ActiviteJournalisee;
use App\Models\Bien;
use App\Models\PhotoDeBien;
use App\Models\Referentiel;
use App\Models\User;
use Database\Seeders\BiensSeeder;
use Database\Seeders\ReferentielsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Le catalogue des biens.
 *
 * Les tests portent d'abord sur les deux choix de modelisation qui distinguent
 * cette table de ce que faisait le site : la tranche de surface est CALCULEE
 * et non stockee, et un terrain n'a PAS de pieces.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->seed(ReferentielsSeeder::class);

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->lecteur->assignRole('lecteur');
});

/* ------------------------------------------------ tranches calculees */

it('deduit la tranche de surface de la surface', function () {
    $cas = [45 => 's1', 99 => 's1', 100 => 's2', 250 => 's2', 251 => 's3', 500 => 's3', 501 => 's4', 736 => 's4'];

    foreach ($cas as $metres => $attendue) {
        $bien = Bien::factory()->make(['surface_habitable' => $metres]);

        expect($bien->trancheDeSurface())->toBe($attendue, "$metres m²");
    }
});

it('emploie la surface du terrain quand il n y a pas de surface habitable', function () {
    // Un terrain nu ne possede que celle-la.
    $terrain = Bien::factory()->terrain()->make();

    expect($terrain->trancheDeSurface())->toBe('s4');
});

it('ne classe dans aucune tranche un bien sans surface', function () {
    $bien = Bien::factory()->make(['surface_habitable' => null, 'surface_terrain' => null]);

    expect($bien->trancheDeSurface())->toBeNull();
});

it('deduit la tranche de pieces du nombre de pieces', function () {
    foreach ([1 => '1', 2 => '1', 3 => '3', 4 => '3', 5 => '5', 12 => '5'] as $pieces => $attendue) {
        expect(Bien::factory()->make(['nombre_pieces' => $pieces])->trancheDePieces())
            ->toBe($attendue, "$pieces pieces");
    }
});

it('exclut un terrain des filtres par pieces', function () {
    Bien::factory()->terrain()->create(['titre_fr' => 'Terrain nu']);
    Bien::factory()->create(['titre_fr' => 'Studio', 'nombre_pieces' => 1]);

    // Le site posait 1 piece a un terrain, ce qui le faisait remonter dans
    // « 1 a 2 pieces ». Un terrain nu n'est pas un logement d'une piece.
    $trouves = Bien::deLaTrancheDePieces('1')->pluck('titre_fr')->all();

    expect($trouves)->toBe(['Studio']);
});

it('filtre par tranche de surface, terrain compris', function () {
    Bien::factory()->terrain()->create(['titre_fr' => 'Grand terrain']);      // 800 m² -> s4
    Bien::factory()->create(['titre_fr' => 'Petit studio', 'surface_habitable' => 45]);

    expect(Bien::deLaTrancheDeSurface('s4')->pluck('titre_fr')->all())->toBe(['Grand terrain'])
        ->and(Bien::deLaTrancheDeSurface('s1')->pluck('titre_fr')->all())->toBe(['Petit studio']);
});

/* ------------------------------------------------ import du site */

it('reprend les six biens du site', function () {
    $this->seed(BiensSeeder::class);

    expect(Bien::count())->toBe(6);

    $villa = Bien::where('slug', 'villa-les-palmiers')->first();

    expect($villa->titre_fr)->toBe('Villa Les Palmiers')
        ->and($villa->type)->toBe('villa')
        ->and($villa->offre)->toBe(Bien::LOCATION)
        ->and($villa->zone)->toBe('cocody')
        ->and($villa->surface_habitable)->toBe(310)
        ->and($villa->quartier)->toBe('Riviera Golf')
        ->and($villa->equipements->pluck('libelle_fr'))->toContain('Piscine privative')
        ->and($villa->equipements->pluck('libelle_en'))->toContain('Private pool');
});

it('ne cree qu une entree par equipement, partagee entre les biens', function () {
    $this->seed(BiensSeeder::class);

    // Deux biens qui mentionnent le meme equipement doivent pointer LA MEME
    // ligne du referentiel : c'est toute la raison d'etre du vocabulaire
    // partage, sans quoi la case a cocher du catalogue ne ramenerait qu'un
    // des deux.
    $libelles = Referentiel::deLaFamille('equipements')->pluck('libelle_fr');

    expect($libelles->count())->toBe($libelles->unique()->count());
});

it('rejoue le rattachement des equipements sans les dupliquer', function () {
    $this->seed(BiensSeeder::class);
    $avant = Referentiel::deLaFamille('equipements')->count();

    $this->seed(BiensSeeder::class);

    expect(Referentiel::deLaFamille('equipements')->count())->toBe($avant)
        ->and(Bien::where('slug', 'villa-les-palmiers')->first()->equipements->pluck('libelle_fr'))
        ->toContain('Piscine privative');
});

it('n invente ni prix ni photo pour les biens repris', function () {
    $this->seed(BiensSeeder::class);

    // Le site n'affiche aucun prix et n'a aucune photo : en inventer aurait
    // mis de fausses informations sur des annonces immobilieres.
    expect(Bien::whereNotNull('prix')->count())->toBe(0)
        ->and(Bien::has('photos')->count())->toBe(0);
});

it('laisse le terrain sans pieces', function () {
    $this->seed(BiensSeeder::class);

    expect(Bien::where('type', 'terrain')->value('nombre_pieces'))->toBeNull();
});

it('refuse d importer un bien dont le vocabulaire est inconnu au referentiel', function () {
    // Sans ce controle, un bien entrerait avec un type qu'aucun filtre ne
    // propose : en ligne et introuvable.
    Referentiel::where('famille', 'types_de_bien')->delete();

    expect(fn () => $this->seed(BiensSeeder::class))
        ->toThrow(RuntimeException::class);
});

it('est rejouable sans creer de doublon', function () {
    $this->seed(BiensSeeder::class);
    $this->seed(BiensSeeder::class);

    expect(Bien::count())->toBe(6);
});

it('ne defait pas le travail editorial a la reimportation', function () {
    $this->seed(BiensSeeder::class);

    $bien = Bien::where('slug', 'villa-les-palmiers')->first();
    $bien->update(['statut' => Bien::BROUILLON, 'en_avant' => true]);

    $this->seed(BiensSeeder::class);

    expect($bien->fresh()->statut)->toBe(Bien::BROUILLON)
        ->and($bien->fresh()->en_avant)->toBeTrue();
});

/* ------------------------------------------------ ecrans */

it('ouvre la liste a un lecteur et la refuse en ecriture', function () {
    // Le catalogue s'ouvre depuis l'ecran de la page, les ecrans par type de
    // contenu ayant ete retires. La garde d'ecriture se lit sur l'ACTION du
    // composant, la ou elle a toujours ete posee.
    $this->actingAs($this->lecteur)->get('/admin/pages/biens')->assertOk();

    Livewire::actingAs($this->lecteur)
        ->test(BienFormulaire::class)
        ->call('enregistrer')
        ->assertForbidden();
});

it('cree un bien', function () {
    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Villa des Cocotiers')
        ->set('slug', 'villa-des-cocotiers')
        ->set('type', 'villa')
        ->set('zone', 'cocody')
        ->set('offre', Bien::VENTE)
        ->set('surfaceHabitable', '280')
        ->set('nombrePieces', '6')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $bien = Bien::where('slug', 'villa-des-cocotiers')->first();

    expect($bien)->not->toBeNull()
        ->and($bien->auteur_id)->toBe($this->editeur->id)
        // La tranche n'est pas saisie : elle se deduit.
        ->and($bien->trancheDeSurface())->toBe('s3')
        ->and($bien->trancheDePieces())->toBe('5');
});

it('refuse un type absent du referentiel', function () {
    // Le vocabulaire vient du referentiel : un bien ne peut pas porter un type
    // qu'aucun filtre ne propose.
    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Chateau')
        ->set('slug', 'chateau')
        ->set('type', 'chateau-fort')
        ->set('zone', 'cocody')
        ->call('enregistrer')
        ->assertHasErrors('type');

    expect(Bien::where('slug', 'chateau')->exists())->toBeFalse();
});

it('refuse deux biens a la meme adresse', function () {
    Bien::factory()->create(['slug' => 'villa-unique']);

    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Autre villa')
        ->set('slug', 'villa-unique')
        ->set('type', 'villa')
        ->set('zone', 'cocody')
        ->call('enregistrer')
        ->assertHasErrors('slug');
});

it('propose une adresse a partir du titre', function () {
    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Villa Les Palmiers')
        ->assertSet('slug', 'villa-les-palmiers');
});

it('rattache au bien les equipements coches', function () {
    $piscine = Referentiel::create([
        'famille' => 'equipements', 'valeur' => 'piscine',
        'libelle_fr' => 'Piscine', 'ordre' => 1, 'visible' => true,
    ]);
    $garage = Referentiel::create([
        'famille' => 'equipements', 'valeur' => 'garage',
        'libelle_fr' => 'Garage', 'ordre' => 2, 'visible' => true,
    ]);

    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Villa équipée')
        ->set('slug', 'villa-equipee')
        ->set('type', 'villa')
        ->set('zone', 'cocody')
        ->set('equipements', [$piscine->id, $garage->id])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Bien::where('slug', 'villa-equipee')->first()->equipements->pluck('libelle_fr')->all())
        ->toBe(['Piscine', 'Garage']);
});

it('refuse un equipement qui n appartient pas au referentiel', function () {
    // L'identifiant vient du navigateur : une valeur forgee designerait une
    // zone ou un type de bien, que la fiche afficherait alors comme un
    // equipement.
    $zone = Referentiel::deLaFamille('zones')->first();

    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('titreFr', 'Villa forgée')
        ->set('slug', 'villa-forgee')
        ->set('type', 'villa')
        ->set('zone', 'cocody')
        ->set('equipements', [$zone->id])
        ->call('enregistrer')
        ->assertHasErrors('equipements.0');
});

it('cree un equipement depuis la fiche du bien, et le coche', function () {
    $composant = Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('nouvelEquipement', 'Groupe électrogène')
        ->call('ajouterEquipement')
        ->assertSet('nouvelEquipement', '');

    $cree = Referentiel::deLaFamille('equipements')->where('libelle_fr', 'Groupe électrogène')->first();

    expect($cree)->not->toBeNull()
        ->and($cree->valeur)->toBe('groupe-electrogene');

    $composant->assertSet('equipements', [$cree->id]);
});

it('coche un equipement deja connu au lieu de le creer deux fois', function () {
    $piscine = Referentiel::create([
        'famille' => 'equipements', 'valeur' => 'piscine',
        'libelle_fr' => 'Piscine', 'ordre' => 1, 'visible' => true,
    ]);

    // La casse ne doit pas creer un doublon : « piscine » et « Piscine »
    // donneraient deux cases a cocher dont chacune ne ramene qu'une partie
    // des biens.
    Livewire::actingAs($this->editeur)
        ->test(BienFormulaire::class)
        ->set('nouvelEquipement', 'piscine')
        ->call('ajouterEquipement')
        ->assertSet('equipements', [$piscine->id]);

    expect(Referentiel::deLaFamille('equipements')->count())->toBe(1);
});

it('filtre la liste avec le vocabulaire du referentiel', function () {
    Bien::factory()->create(['titre_fr' => 'La villa', 'type' => 'villa']);
    Bien::factory()->create(['titre_fr' => "L'appartement", 'type' => 'appartement', 'slug' => 'appart']);

    $corps = Livewire::actingAs($this->editeur)
        ->test(BienListe::class)
        ->set('type', 'villa')
        ->html();

    expect($corps)->toContain('La villa')
        ->and($corps)->not->toContain("L'appartement");
});

it('supprime un bien et ses photos', function () {
    $bien = Bien::factory()->create();
    $bien->photos()->create(['fichier' => 'storage/biens/essai.jpg', 'ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(BienListe::class)
        ->call('supprimer', $bien->id);

    expect(Bien::find($bien->id))->toBeNull()
        ->and(PhotoDeBien::count())->toBe(0);
});

it('refuse a un lecteur de supprimer', function () {
    $bien = Bien::factory()->create();

    Livewire::actingAs($this->lecteur)
        ->test(BienListe::class)
        ->call('supprimer', $bien->id)
        ->assertForbidden();

    expect(Bien::find($bien->id))->not->toBeNull();
});

it('inscrit les biens au journal des activites', function () {
    $this->actingAs($this->editeur);

    Bien::factory()->create(['titre_fr' => 'Villa journalisée']);

    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->sujet_intitule)->toBe('Villa journalisée')
        // Le journal doit NOMMER la famille. « Contenu » etait le repli des
        // familles non declarees, et un bien en est une.
        ->and($ligne->famille())->toBe(__('Bien'))
        // Le journal mene desormais a l'ECRAN DE PAGE : les fiches n'ont
        // plus d'adresse a elles.
        ->and($ligne->lienDEdition())->toContain('/admin/pages/biens');
});

it('propose les biens dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/pages/biens')
        // L'ancienne entree a disparu avec son ecran.
        ->and($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->not->toContain('href="http://localhost:8000/admin/biens"');
});
