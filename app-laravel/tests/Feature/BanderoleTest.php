<?php

use App\Livewire\Admin\CommuneBandeauEnsemble;
use App\Models\CommuneDuBandeau;
use App\Models\ReglageDeSection;
use App\Models\User;
use Database\Seeders\CommunesDuBandeauSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * La banderole des communes.
 *
 * Elle existait dans index.html et avait DISPARU au portage du lot 2 : la page
 * servie n'en contenait plus trace. Ces tests verrouillent son retour autant
 * que son ecran d'administration.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create();
    $this->lecteur->assignRole('lecteur');
});

it('sert la banderole sur la page d accueil', function () {
    $this->seed(CommunesDuBandeauSeeder::class);

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->toContain('marquee-band');

    foreach (['Cocody', 'Riviera', 'Bingerville', 'Marcory', 'Angré', 'Plateau', 'Abatta'] as $commune) {
        expect($corps)->toContain($commune);
    }
});

it('repete la liste deux fois pour boucler sans coupure', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Yopougon', 'ordre' => 1]);

    $corps = $this->get('/')->getContent();

    // Le bandeau defile en boucle : une seule serie laisserait un blanc a
    // chaque tour. C'est ce que faisait deja la page statique d'origine.
    expect(substr_count($corps, '<span>Yopougon</span>'))->toBe(2);
});

it('n affiche pas le bandeau quand aucune commune n est visible', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Yopougon', 'visible' => false]);

    // Un bandeau vide serait une bande de couleur sans contenu : mieux vaut ne
    // rien afficher.
    expect($this->get('/')->getContent())->not->toContain('marquee-band');
});

it('masque une commune rendue invisible', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Cocody', 'ordre' => 1, 'visible' => true]);
    CommuneDuBandeau::factory()->create(['nom' => 'Yopougon', 'ordre' => 2, 'visible' => false]);

    $corps = $this->get('/')->getContent();

    expect($corps)->toContain('Cocody')
        ->and($corps)->not->toContain('Yopougon');
});

it('suit l ordre d affichage regle en administration', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Marcory', 'ordre' => 2]);
    CommuneDuBandeau::factory()->create(['nom' => 'Abatta', 'ordre' => 1]);

    // Mesure DANS LA BANDEROLE et non dans la page : « Marcory » figure aussi
    // dans les donnees structurees en tete de document, bien avant le bandeau.
    // Le premier jet comparait des positions dans la page entiere et tombait
    // sur cette occurrence-la.
    $bande = bandeDuMarquee($this->get('/')->getContent());

    expect(mb_strpos($bande, 'Abatta'))->toBeLessThan(mb_strpos($bande, 'Marcory'));
});

/** Le bandeau seul, du conteneur a sa fermeture. */
function bandeDuMarquee(string $html): string
{
    $debut = mb_strpos($html, 'marquee-band');

    if ($debut === false) {
        return '';
    }

    $fin = mb_strpos($html, '</div>', mb_strpos($html, 'marquee-track', $debut));

    return mb_substr($html, $debut, $fin - $debut);
}

it('emploie le separateur choisi', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Cocody', 'ordre' => 1]);
    ReglageDeSection::create([
        'slug' => CommuneDuBandeau::SECTION,
        'etiquette_fr' => '',
        'titre_fr' => 'Bandeau',
        'options' => ['fond' => 'sombre', 'separateur' => '—', 'casse' => 'majuscules'],
    ]);

    expect($this->get('/')->getContent())->toContain('<b>—</b>');
});

/* ------------------------------------------------------------ ecran */

it('charge les communes dans l ecran', function () {
    $commune = CommuneDuBandeau::factory()->create(['nom' => 'Cocody', 'ordre' => 1]);

    Livewire::actingAs($this->editeur)
        ->test(CommuneBandeauEnsemble::class)
        ->assertSet("lignes.{$commune->id}.nom", 'Cocody');
});

it('ajoute puis enregistre une commune', function () {
    Livewire::actingAs($this->editeur)
        ->test(CommuneBandeauEnsemble::class)
        ->call('ajouter')
        ->set('lignes.neuf-1.nom', 'Yopougon')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(CommuneDuBandeau::where('nom', 'Yopougon')->exists())->toBeTrue();
});

it('refuse une commune sans nom', function () {
    Livewire::actingAs($this->editeur)
        ->test(CommuneBandeauEnsemble::class)
        ->call('ajouter')
        ->set('lignes.neuf-1.nom', '')
        ->call('enregistrer')
        ->assertHasErrors('lignes.neuf-1.nom');
});

it('refuse l enregistrement a un lecteur', function () {
    Livewire::actingAs($this->lecteur)
        ->test(CommuneBandeauEnsemble::class)
        ->call('enregistrer')
        ->assertForbidden();
});

it('enregistre les reglages d apparence du bandeau', function () {
    Livewire::actingAs($this->editeur)
        ->test(CommuneBandeauEnsemble::class)
        ->set('reglages.fond', 'clair')
        ->set('reglages.separateur', '—')
        ->set('reglages.casse', 'saisie')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', CommuneDuBandeau::SECTION)->first();

    expect($section->option('fond'))->toBe('clair')
        ->and($section->option('separateur'))->toBe('—')
        ->and($section->option('casse'))->toBe('saisie');
});

it('montre un apercu doublé, comme le bandeau reel', function () {
    CommuneDuBandeau::factory()->create(['nom' => 'Cocody', 'ordre' => 1]);

    $corps = Livewire::actingAs($this->editeur)->test(CommuneBandeauEnsemble::class)->html();

    // L'apercu doit refleter le doublement, sans quoi l'editeur croirait a une
    // erreur en voyant chaque commune deux fois sur le site.
    expect(substr_count($corps, '<span>Cocody</span>'))->toBeGreaterThanOrEqual(2);
});

it('propose la banderole dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/banderole');
});
