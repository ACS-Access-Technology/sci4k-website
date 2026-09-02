<?php

use App\Livewire\Admin\PageAccueil;
use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Models\ReglageDeSection;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Premier ecran de la refonte : une page d'administration par page publique.
 *
 * Les anciens ecrans restent en place et ecrivent LES MEMES tables. Ces
 * controles verifient donc surtout que le nouvel ecran ecrit bien la ou il
 * faut, et qu'il n'annonce rien qu'il ne fasse.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('affiche les huit modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->assertOk()
        ->assertSeeInOrder([
            'Hero',
            'Bande déroulante',
            'Services',
            'Annonce',
            "Bandeau d'appel à l'action",
            'Articles',
            'Avis clients',
            'Partenaires',
        ]);
});

it('ouvre le module demande', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'partenaires')
        ->assertSet('module', 'partenaires');
});

/**
 * Chaque module qui pilote une collection embarque l'ANCIEN ECRAN ENTIER, et
 * non un resume renvoyant ailleurs : c'est la condition pour que les ecrans
 * par type de contenu puissent disparaitre sans rien perdre.
 */
it('embarque l ecran complet de la collection', function (string $module, string $marqueur) {
    $rendu = Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', $module)
        ->html();

    expect($rendu)->toContain($marqueur);
})->with([
    // Le texte du glisser-deposer ne sort que du corps de l'ancien ecran : le
    // trouver ici prouve que l'ecran entier est rendu, et pas un resume.
    ['temoignages', 'Faites glisser une ligne par sa poignée'],
    ['partenaires', 'Faites glisser une ligne par sa poignée'],
    ['services', 'Faites glisser une ligne par sa poignée'],
    // Les deux editeurs groupes sont montes comme composants imbriques :
    // Livewire ne rend pas leur corps dans le test du parent, seul leur
    // ancrage y figure. Leur contenu est couvert par leurs propres tests.
    ['hero', 'wire:name="admin.chiffre-cle-ensemble"'],
    ['bandeau', 'wire:name="admin.commune-bandeau-ensemble"'],
]);

/** Les statistiques de l'ancien ecran suivent, elles aussi. */
it('embarque les statistiques de l ancien ecran', function () {
    Temoignage::factory()->create(['visible' => true, 'note' => 5]);

    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'temoignages')
        ->assertSee('Note moyenne')
        ->assertSee('Affichés sur le site');
});

/** L'en-tete de page de l'ecran embarque ne doit PAS apparaitre : la page qui
 *  l'accueille porte deja le sien, et deux fils d'Ariane se contrediraient. */
it('n affiche pas le fil d Ariane de l ecran embarque', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'temoignages')
        ->html();

    expect(substr_count($rendu, 'aria-label="Fil d’Ariane"'))->toBeLessThanOrEqual(1);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran de liste', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', $module)
        ->html();

    foreach ([
        route('admin.chiffres-cles'),
        route('admin.banderole'),
        route('admin.temoignages.liste'),
        route('admin.partenaires.liste'),
    ] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse.'"');
    }
})->with(['hero', 'bandeau', 'articles', 'temoignages', 'partenaires']);

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete d une section', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'hero')
        ->set('entete.titre_fr', 'Un tout nouveau titre')
        ->set('entete.chapo_fr', 'Une accroche revue.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', 'home.hero')->first();

    expect($section->titre_fr)->toBe('Un tout nouveau titre')
        ->and($section->chapo_fr)->toBe('Une accroche revue.');
});

/**
 * Les modules Annonce et CTA embarquent le FORMULAIRE D'ENCART, et non une
 * copie de ses champs. Lui seul sait televerser une image, la remplacer et
 * faire le menage de l'ancienne : la recopier avait donne un module d'annonce
 * sans visuel.
 */
it('embarque le formulaire d encart, image comprise', function (string $module, string $slug) {
    $rendu = Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', $module)
        ->html();

    expect($rendu)->toContain('wire:name="admin.encart-formulaire"');
    expect(Encart::where('slug', $slug)->exists())->toBeTrue();
})->with([
    ['annonce', 'accueil.annonce'],
    ['cta', 'accueil'],
]);

/** Le formulaire d'encart gere bien un fichier : c'est ce qui manquait. */
it('permet de gerer l image de l encart', function () {
    $encart = Encart::firstOrCreate(['slug' => 'accueil.annonce']);

    $rendu = Livewire::actingAs($this->admin)
        ->test(App\Livewire\Admin\EncartFormulaire::class, ['element' => $encart, 'embarque' => true])
        ->html();

    expect($rendu)->toContain('type="file"');
});

/**
 * Les deux boutons du hero etaient ecrits en dur dans le gabarit public, et le
 * premier pointait sur /biens.html — une adresse qui ne repond plus que par une
 * redirection.
 */
it('enregistre les deux boutons du hero et les sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'hero')
        ->set('boutons.bouton1_libelle_fr', 'Voir nos biens')
        ->set('boutons.bouton1_cible', '/biens?offre=vente')
        ->set('boutons.bouton2_libelle_fr', 'Qui sommes-nous')
        ->set('boutons.bouton2_cible', '/presentation')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get('/')
        ->assertOk()
        ->assertSee('Voir nos biens', false)
        ->assertSee('Qui sommes-nous', false)
        ->assertSee('/biens?offre=vente', false);
});

/** Sans saisie, le site garde les libelles et les liens d'origine. */
it('garde les boutons d origine quand rien n est saisi', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Rechercher un bien', false)
        ->assertSee('Découvrir SCI4K', false)
        // Et surtout plus /biens.html, qui ne repond que par une redirection.
        ->assertDontSee('href="/biens.html"', false);
});

/**
 * Un module d'encart n'affiche pas les champs d'en-tete de section : il n'a
 * pas de section, et le formulaire n'aurait eu qu'un bouton « Enregistrer »
 * sans rien a enregistrer.
 */
it('n affiche pas les champs de section sur un module d encart', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'annonce')
        ->assertDontSee('wire:model="entete.titre_fr"', false);
});

/** Le formulaire embarque ne doit proposer aucune sortie vers un autre ecran. */
it('n offre aucune sortie depuis le formulaire embarque', function () {
    $encart = Encart::firstOrCreate(['slug' => 'accueil.annonce']);

    $rendu = Livewire::actingAs($this->admin)
        ->test(App\Livewire\Admin\EncartFormulaire::class, ['element' => $encart, 'embarque' => true])
        ->html();

    expect($rendu)->not->toContain(route('admin.encarts.liste'));
});

it('enregistre l apparence de la bande deroulante', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'bandeau')
        ->set('bandeau.fond', 'clair')
        ->set('bandeau.separateur', '—')
        ->set('bandeau.casse', 'normale')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', CommuneDuBandeau::SECTION)->first();

    expect($section->option('fond'))->toBe('clair')
        ->and($section->option('separateur'))->toBe('—')
        ->and($section->option('casse'))->toBe('normale');
});

/**
 * Un lecteur consulte, il ne modifie pas. Le controle est sur l'ACTION et non
 * seulement sur l'affichage du bouton : Livewire expose toute methode publique
 * au navigateur, masquer le bouton ne protegerait rien.
 */
it('interdit toute ecriture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageAccueil::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();

});

it('ferme l ecran a un compte sans role', function () {
    $sansRole = User::factory()->create();

    Livewire::actingAs($sansRole)->test(PageAccueil::class)->assertForbidden();
});

/**
 * L'ordre des modules est fixe dans le gabarit public : l'ecran ne doit pas
 * proposer de poignee de deplacement, qui ne deplacerait rien.
 */
it('ne propose pas de reordonner les modules', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->assertDontSee('wire:sortable', false)
        ->assertDontSee('⋮⋮', false);
});

/** Le bloc des articles doit dire qu'il ne se choisit pas. */
it('annonce que les articles ne se choisissent pas ici', function () {
    Livewire::actingAs($this->admin)->test(PageAccueil::class)
        ->call('ouvrir', 'articles')
        ->assertSee('ne se choisissent pas ici', false);
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.accueil'), false);
});
