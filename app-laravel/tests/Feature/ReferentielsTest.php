<?php

use App\Livewire\Admin\Referentiels;
use App\Models\Referentiel;
use App\Models\User;
use Database\Seeders\ReferentielsSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Referentiels : les valeurs des listes deroulantes du site public.
 *
 * Cinq familles dans une seule table, editees d'un seul ecran. Les tests
 * portent en priorite sur ce que ce partage rend fragile : le rang, qui doit
 * rester propre a chaque famille, et les identifiants recus du navigateur, qui
 * pourraient designer une ligne d'une autre famille.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('refuse l ecran a un editeur', function () {
    $this->actingAs($this->editeur)->get('/admin/referentiels')->assertForbidden();

    Livewire::actingAs($this->editeur)
        ->test(Referentiels::class)
        ->assertForbidden();
});

it('ouvre l ecran a un administrateur', function () {
    $this->actingAs($this->admin)->get('/admin/referentiels')->assertOk();
});

it('charge chaque famille dans son propre bloc', function () {
    $zone = Referentiel::factory()->create(['famille' => 'zones', 'valeur' => 'cocody', 'libelle_fr' => 'Cocody', 'ordre' => 1]);
    $type = Referentiel::factory()->create(['famille' => 'types_de_bien', 'valeur' => 'villa', 'libelle_fr' => 'Villa', 'ordre' => 1]);

    // Les identifiants REELS, et non « 1 » et « 2 » ecrits en dur : SQLite
    // repart de 1 a chaque test, MySQL non. Le premier jet ne tombait donc en
    // rouge que sur le moteur de production.
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->assertSet("lignes.zones.{$zone->id}.libelle_fr", 'Cocody')
        ->assertSet("lignes.types_de_bien.{$type->id}.libelle_fr", 'Villa');
});

it('donne a chaque ligne une cle de rendu qui lui est propre', function () {
    // SANS wire:key, la reconciliation du DOM apparie les lignes PAR POSITION.
    // Retirer une valeur au milieu d'une famille decalait alors toutes les
    // suivantes : le champ gardait sa valeur affichee mais recevait le chemin
    // « lignes.famille.ID » de son voisin, et la saisie partait dans
    // l'enregistrement d'a cote. Constate sur les equipements, ou trente-trois
    // valeurs rendent le decalage evident ; le defaut valait pour toutes les
    // familles.
    $a = Referentiel::factory()->create(['famille' => 'equipements', 'valeur' => 'piscine', 'libelle_fr' => 'Piscine', 'ordre' => 1]);
    $b = Referentiel::factory()->create(['famille' => 'equipements', 'valeur' => 'garage', 'libelle_fr' => 'Garage', 'ordre' => 2]);

    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->assertSee('wire:key="ligne-equipements-'.$a->id.'"', false)
        ->assertSee('wire:key="ligne-equipements-'.$b->id.'"', false);
});

it('ne heurte pas deux lignes neuves de familles differentes', function () {
    // Une ligne jamais enregistree s'appelle « neuf-1 » DANS CHAQUE FAMILLE :
    // le compteur est par famille. Une cle reduite a « neuf-1 » se retrouverait
    // donc en double sur la page des qu'on ajoute une valeur a deux endroits,
    // et deux cles identiques ramenent exactement le defaut qu'elles doivent
    // fermer. La famille fait partie de la cle pour cette raison.
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->call('ajouter', 'equipements')
        ->assertSee('wire:key="ligne-zones-neuf-1"', false)
        ->assertSee('wire:key="ligne-equipements-neuf-1"', false);
});

it('ajoute une valeur a la famille demandee', function () {
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->assertSet('lignes.zones.neuf-1.visible', '1')
        ->set('lignes.zones.neuf-1.valeur', 'yopougon')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Yopougon')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Referentiel::where('famille', 'zones')->where('valeur', 'yopougon')->exists())->toBeTrue();
});

it('refuse une famille inventee', function () {
    // `lignes` est une propriete publique : le navigateur en fixe les cles.
    // Une famille forgee creerait des lignes qu'aucun ecran n'afficherait.
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'famille-inventee')
        ->assertNotFound();
});

it('ignore une famille forgee glissee dans l enregistrement', function () {
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->set('lignes.famille-fantome.neuf-9', [
            'valeur' => 'intrus',
            'libelle_fr' => 'Intrus',
            'libelle_en' => '',
            'visible' => '1',
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Mesure au point sensible : la LIGNE ne doit pas exister en base, quel
    // que soit ce que l'ecran affiche ensuite.
    expect(Referentiel::where('valeur', 'intrus')->exists())->toBeFalse();
});

/*
 * Le rang est le point vraiment fragile de cette table : cinq familles la
 * partagent, et un rang global melangerait les quatre listes du site.
 */
it('numerote les rangs famille par famille', function () {
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-1.valeur', 'cocody')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Cocody')
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-2.valeur', 'marcory')
        ->set('lignes.zones.neuf-2.libelle_fr', 'Marcory')
        ->call('ajouter', 'types_de_bien')
        ->set('lignes.types_de_bien.neuf-1.valeur', 'villa')
        ->set('lignes.types_de_bien.neuf-1.libelle_fr', 'Villa')
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Le premier type de bien porte le rang 1, et NON le rang qui suit la
    // derniere zone : c'est exactement ce qu'un rang global aurait produit.
    expect(Referentiel::where('famille', 'zones')->orderBy('ordre')->pluck('valeur')->all())
        ->toBe(['cocody', 'marcory'])
        ->and(Referentiel::where('famille', 'types_de_bien')->value('ordre'))->toBe(1);
});

it('ne deplace pas une ligne empruntee a une autre famille', function () {
    $zone = Referentiel::factory()->create(['famille' => 'zones', 'valeur' => 'cocody', 'ordre' => 1]);

    // L'identifiant d'une zone, glisse dans le bloc des types de bien. Sans
    // relecture depuis la base, l'enregistrement l'aurait reecrit — et la zone
    // aurait change de famille.
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->set("lignes.types_de_bien.{$zone->id}", [
            'valeur' => 'detourne',
            'libelle_fr' => 'Détourné',
            'libelle_en' => '',
            'visible' => '1',
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($zone->fresh()->famille)->toBe('zones')
        ->and($zone->fresh()->valeur)->toBe('cocody');
});

it('refuse deux valeurs techniques identiques dans une famille', function () {
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-1.valeur', 'cocody')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Cocody')
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-2.valeur', 'cocody')
        ->set('lignes.zones.neuf-2.libelle_fr', 'Cocody bis')
        ->call('enregistrer')
        ->assertHasErrors('lignes.zones');

    expect(Referentiel::count())->toBe(0);
});

it('accepte la meme valeur technique dans deux familles differentes', function () {
    // « autre » a un sens dans chaque liste : la contrainte porte sur le
    // couple, pas sur la valeur seule.
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-1.valeur', 'autre')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Autre zone')
        ->call('ajouter', 'types_de_bien')
        ->set('lignes.types_de_bien.neuf-1.valeur', 'autre')
        ->set('lignes.types_de_bien.neuf-1.libelle_fr', 'Autre type')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Referentiel::where('valeur', 'autre')->count())->toBe(2);
});

it('refuse une valeur technique avec des majuscules ou des espaces', function () {
    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-1.valeur', 'Cocody Riviera')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Cocody')
        ->call('enregistrer')
        ->assertHasErrors('lignes.zones.neuf-1.valeur');
});

it('supprime une valeur retiree', function () {
    $zone = Referentiel::factory()->create(['famille' => 'zones', 'valeur' => 'cocody']);

    Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('retirer', 'zones', $zone->id)
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Referentiel::find($zone->id))->toBeNull();
});

it('n enregistre pas deux fois les lignes ajoutees', function () {
    // Les cles « neuf-N » doivent laisser place aux identifiants reels apres
    // enregistrement, sans quoi un second clic recreerait les memes lignes.
    $composant = Livewire::actingAs($this->admin)
        ->test(Referentiels::class)
        ->call('ajouter', 'zones')
        ->set('lignes.zones.neuf-1.valeur', 'cocody')
        ->set('lignes.zones.neuf-1.libelle_fr', 'Cocody')
        ->call('enregistrer');

    $composant->call('enregistrer')->assertHasNoErrors();

    expect(Referentiel::where('famille', 'zones')->count())->toBe(1);
});

it('rappelle les referentiels geres ailleurs sans les rendre modifiables', function () {
    $corps = Livewire::actingAs($this->admin)->test(Referentiels::class)->html();

    // Ils doivent apparaitre — l'ecran serait incomplet face a la maquette —
    // mais sans champ de saisie qui en ferait une seconde source.
    expect($corps)->toContain(__('Référentiels gérés ailleurs'))
        ->and($corps)->not->toContain('wire:model="categories');
});

/*
 * Le vocabulaire du backoffice et celui du site doivent etre LE MEME.
 *
 * Les valeurs techniques avaient d'abord ete derivees des libelles des filtres
 * — « Villa & Duplex » donnait « villa-duplex » — alors que le site emploie
 * l'attribut `value` de ses listes deroulantes. Onze sur seize ne
 * correspondaient pas. Ce test lit le HTML du site plutot que de recopier une
 * liste : recopier, c'est exactement ce qui avait produit l'ecart.
 */
it('emploie les memes valeurs techniques que les filtres du site', function () {
    $this->seed(ReferentielsSeeder::class);

    $html = file_get_contents(
        file_exists(base_path('../frontoffice/biens.html'))
            ? base_path('../frontoffice/biens.html')
            : base_path('../maquettes-frontoffice/biens.html')
    );

    $familles = [
        'selectType' => 'types_de_bien',
        'selectLoc' => 'zones',
        'selectRooms' => 'tranches_pieces',
        'selectSurface' => 'tranches_surface',
    ];

    foreach ($familles as $champ => $famille) {
        preg_match('#<select[^>]*id="'.$champ.'"[^>]*>(.*?)</select>#s', $html, $bloc);
        expect($bloc)->not->toBeEmpty("liste « $champ » introuvable dans biens.html");

        preg_match_all('#<option[^>]*value="([^"]*)"#', $bloc[1], $options);

        // « all » est le choix « tous », qui n'est pas une valeur du referentiel.
        $duSite = array_values(array_diff($options[1], ['all']));
        $enBase = Referentiel::deLaFamille($famille)->ordonnees()->pluck('valeur')->all();

        expect($enBase)->toBe($duSite, "famille « $famille »");
    }
});
