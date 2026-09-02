<?php

use App\Livewire\Admin\PagePresentation;
use App\Models\MembreEquipe;
use App\Models\ReglageDeSection;
use App\Models\User;
use App\Models\Valeur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Deuxieme ecran de la refonte : la page Presentation, geree d'un seul endroit.
 *
 * Trois elements de cette page n'etaient modifiables nulle part — le corps de
 * texte logeait dans un champ d'accroche, les deux atouts et le compteur
 * etaient ecrits en dur. Ces controles fixent le fait qu'ils le sont
 * desormais, ET que la page reste identique tant que rien n'est saisi.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('affiche les cinq modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->assertOk()
        ->assertSeeInOrder([
            'Bannière',
            'Présentation générale',
            'Mot du Directeur',
            'Valeurs',
            'Équipe',
        ]);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

/* ------------------------------------------------------------------ */
/* Le corps de texte a enfin son champ                                 */
/* ------------------------------------------------------------------ */

it('enregistre le corps de texte et le sert en paragraphes', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'presentation')
        ->set('entete.contenu_fr', "Premier paragraphe.\n\nSecond paragraphe.")
        ->call('enregistrer')
        ->assertHasNoErrors();

    $section = ReglageDeSection::where('slug', 'about.overview')->first();

    expect($section->paragraphes('fr'))->toBe(['Premier paragraphe.', 'Second paragraphe.']);

    $this->get(route('presentation.index'))
        ->assertOk()
        ->assertSee('Premier paragraphe.', false)
        ->assertSee('Second paragraphe.', false);
});

/**
 * Le corps de texte logeait dans « chapo » avant d'avoir son champ. Une base ou
 * la migration de recopie n'a pas tourne doit continuer de l'afficher.
 */
it('retombe sur l accroche tant que le contenu est vide', function () {
    ReglageDeSection::updateOrCreate(['slug' => 'about.overview'], [
        'chapo_fr' => 'Un texte venu de l ancien champ.',
        'contenu_fr' => '',
    ]);

    $section = ReglageDeSection::where('slug', 'about.overview')->first();

    expect($section->contenu('fr'))->toBe('Un texte venu de l ancien champ.');

    $this->get(route('presentation.index'))->assertSee('Un texte venu de l ancien champ.', false);
});

/* ------------------------------------------------------------------ */
/* Les deux atouts, jusqu'ici ecrits en dur                            */
/* ------------------------------------------------------------------ */

it('enregistre les deux atouts et les sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'presentation')
        ->set('atouts.atout1_titre_fr', 'Sécurité foncière')
        ->set('atouts.atout1_texte_fr', 'Chaque acte est vérifié.')
        ->set('atouts.atout2_titre_fr', 'Réseau local')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('presentation.index'))
        ->assertSee('Sécurité foncière', false)
        ->assertSee('Chaque acte est vérifié.', false)
        ->assertSee('Réseau local', false);
});

it('garde les atouts d origine quand rien n est saisi', function () {
    $this->get(route('presentation.index'))
        ->assertSee('Expertise Juridique', false)
        ->assertSee('Ancrage Abidjanais', false);
});

/* ------------------------------------------------------------------ */
/* Le compteur, jusqu'ici ecrit en dur                                 */
/* ------------------------------------------------------------------ */

it('enregistre le compteur et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'directeur')
        ->set('compteur.valeur', '22')
        ->set('compteur.libelle_fr', 'communes couvertes')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('presentation.index'))
        ->assertSee('data-target="22"', false)
        ->assertSee('communes couvertes', false);
});

/** Un compteur s'anime : une valeur non numerique le laisserait a zero. */
it('refuse une valeur de compteur non numerique', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'directeur')
        ->set('compteur.valeur', 'beaucoup')
        ->call('enregistrer')
        ->assertHasErrors('compteur.valeur');
});

it('garde le compteur d origine quand rien n est saisi', function () {
    $this->get(route('presentation.index'))->assertSee('data-target="14"', false);
});

/* ------------------------------------------------------------------ */
/* Les collections, embarquees entieres                                */
/* ------------------------------------------------------------------ */

it('embarque l ecran complet des membres de l equipe', function () {
    MembreEquipe::factory()->create();

    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'equipe')
        ->assertSee('Faites glisser une ligne par sa poignée', false);
});

it('embarque l editeur des valeurs', function () {
    Valeur::factory()->create();

    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'valeurs')
        ->assertSee('wire:name="admin.valeur-ensemble"', false);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', $module)
        ->html();

    foreach ([route('admin.valeurs'), route('admin.equipe.liste')] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse.'"');
    }
})->with(['banniere', 'presentation', 'directeur', 'valeurs', 'equipe']);

/* ------------------------------------------------------------------ */
/* Droits                                                             */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PagePresentation::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PagePresentation::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.presentation'), false);
});

/** L'ordre des modules est fixe dans le gabarit public. */
it('ne propose pas de reordonner les modules', function () {
    Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->assertDontSee('wire:sortable', false);
});


/**
 * Le mot du directeur n'affiche pas d'accroche sur le site : le champ
 * n'aurait rien montre.
 */
it('n affiche pas d accroche sur le mot du directeur', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'directeur')
        ->html();

    expect($rendu)->not->toContain('wire:model="entete.chapo_fr"')
        ->and($rendu)->toContain('wire:model="entete.titre_fr"');
});

/** Les autres modules gardent leur accroche. */
it('garde l accroche sur les modules qui l affichent', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', $module)
        ->html();

    expect($rendu)->toContain('wire:model="entete.chapo_fr"');
})->with(['banniere', 'presentation', 'valeurs', 'equipe']);

/**
 * L'editeur embarque ne doit pas afficher son panneau « Reglages du bloc » :
 * il edite la MEME section que le formulaire du module, juste au-dessus. Deux
 * formulaires pour une donnee, c'est une saisie qui en ecrase une autre selon
 * l'ordre des clics.
 */
it('masque les reglages du bloc dans l editeur embarque', function () {
    Valeur::factory()->create();

    $rendu = Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->call('ouvrir', 'valeurs')
        ->html();

    expect($rendu)->not->toContain('Réglages du bloc');
});

/** Sur son propre ecran, le panneau reste : rien d'autre n'edite la section. */
it('garde les reglages du bloc sur l ecran des valeurs', function () {
    Valeur::factory()->create();

    $rendu = Livewire::actingAs($this->admin)
        ->test(App\Livewire\Admin\ValeurEnsemble::class)
        ->html();

    expect($rendu)->toContain('Réglages du bloc');
});
