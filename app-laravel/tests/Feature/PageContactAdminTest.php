<?php

use App\Livewire\Admin\PageContact;
use App\Models\MessageDeContact;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Dernier ecran de la refonte : la page Contact.
 *
 * Deux particularites.
 *
 * Les coordonnees et le point de la carte ne sont pas des sections mais des
 * REGLAGES — les memes cles Parametre qu'edite « Configuration », qui les
 * reserve aux administrateurs. Les ouvrir aux editeurs depuis cet ecran aurait
 * elargi un acces par la bande.
 *
 * Les messages recus ne sont pas un bloc de la page mais ce qu'elle produit :
 * le formulaire les enregistre avant d'ouvrir WhatsApp.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('affiche les cinq modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->assertOk()
        ->assertSeeInOrder(['Bannière', 'Formulaire', 'Coordonnées', 'Carte', 'Messages reçus']);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete de la banniere et la sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'banniere')
        ->set('entete.titre_fr', 'Parlons de votre projet')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('contact.index'))
        ->assertOk()
        ->assertSee('Parlons de votre projet', false);
});

it('enregistre l en-tete du formulaire et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('entete.titre_fr', 'Écrivez-nous')
        ->set('entete.chapo_fr', 'Réponse sous 24 heures.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'contact.form')->value('titre_fr'))->toBe('Écrivez-nous');

    $this->get(route('contact.index'))->assertSee('Écrivez-nous', false);
});

/** La carte n'affiche que son titre : ni etiquette ni accroche n'y ont place. */
it('ne propose que le titre sur le module Carte', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'carte')
        ->html();

    expect($rendu)->toContain('wire:model="entete.titre_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.chapo_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.etiquette_fr"');
});

it('n affiche aucun formulaire sur le module Messages', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'messages')
        ->assertDontSee('wire:model="entete.titre_fr"', false)
        ->assertDontSee('wire:model="reglages.', false);
});

it('refuse d enregistrer un module sans section ni reglage', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'messages')
        ->call('enregistrer')
        ->assertNotFound();
});

/* ------------------------------------------------------------------ */
/* Les textes du formulaire */
/* ------------------------------------------------------------------ */

it('propose tous les textes du formulaire', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->html();

    foreach (array_keys(PageContact::TEXTES_DU_FORMULAIRE) as $nom) {
        expect($rendu)->toContain('wire:model="textes.'.$nom.'_fr"');
    }
});

it('enregistre un libelle et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('textes.libelle_bouton_fr', 'Envoyer sur WhatsApp')
        ->set('textes.libelle_nom_fr', 'Votre nom')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('contact.index'))
        ->assertSee('Envoyer sur WhatsApp', false)
        ->assertSee('Votre nom', false);
});

it('retombe sur le texte d origine quand le champ est vide', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('contact.index'))->assertSee('Envoyer mon message', false);
});

/**
 * `$textes` est une propriete publique : le navigateur en fixe le contenu,
 * CLES COMPRISES. Sans filtre sur les cles declarees, n'importe quelle option
 * de la section serait ecrivable sans passer par aucune regle.
 */
it('n ecrit que les textes que le module declare', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('textes.mise_en_page', 'liste')
        ->set('textes.libelle_bouton_fr', 'Envoyer')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $options = ReglageDeSection::where('slug', 'contact.form')->first()->options;

    expect($options)->not->toHaveKey('mise_en_page')
        ->and($options['libelle_bouton_fr'])->toBe('Envoyer');
});

/* ------------------------------------------------------------------ */
/* Les sujets proposes */
/* ------------------------------------------------------------------ */

it('sert les sept sujets d origine quand rien n est saisi', function () {
    $rendu = $this->get(route('contact.index'))->assertOk()->getContent();

    foreach (['Achat de bien / terrain', 'Question Foncier / ACD', 'Autre demande'] as $sujet) {
        expect($rendu)->toContain('<option value="'.e($sujet).'">');
    }
});

it('sert les sujets saisis, un par ligne', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('textes.sujets_fr', "Achat\nLocation\n\n  Autre  ")
        ->call('enregistrer')
        ->assertHasNoErrors();

    $rendu = $this->get(route('contact.index'))->assertOk()->getContent();

    // Les lignes vides et les espaces de bord sont retires : une ligne laissee
    // par megarde aurait ajoute un choix sans intitule dans la liste.
    expect(substr_count($rendu, '<option value="'))->toBe(3)
        ->and($rendu)->toContain('<option value="Autre">Autre</option>')
        ->and($rendu)->not->toContain('Question Foncier / ACD');
});

/**
 * L'intitule EST la valeur : main.js recopie ce qui est choisi dans le message
 * WhatsApp, et c'est aussi ce qui est enregistre comme sujet du message. Une
 * valeur technique distincte ferait arriver « Gestion » dans la boite de
 * reception la ou l'editeur a ecrit « Gestion locative ».
 */
it('donne au sujet la meme valeur que son intitule', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('textes.sujets_fr', 'Gestion locative')
        ->call('enregistrer');

    $this->get(route('contact.index'))
        ->assertSee('<option value="Gestion locative">Gestion locative</option>', false);
});

/* ------------------------------------------------------------------ */
/* Les reglages : coordonnees et carte */
/* ------------------------------------------------------------------ */

it('enregistre les coordonnees et les sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'coordonnees')
        ->set('reglages.telephone', '+225 01 02 03 04 05')
        ->set('reglages.email_public', 'bonjour@sci4k.com')
        ->set('reglages.adresse_postale', "Cocody\nRiviera 3")
        ->set('reglages.horaires', 'Lundi — Vendredi : 09h - 17h')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('telephone'))->toBe('+225 01 02 03 04 05');

    $this->get(route('contact.index'))
        ->assertOk()
        ->assertSee('bonjour@sci4k.com', false)
        ->assertSee('Riviera 3', false)
        ->assertSee('Lundi — Vendredi : 09h - 17h', false);
});

it('enregistre le point de la carte et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'carte')
        ->set('entete.titre_fr', 'Où nous trouver')
        ->set('reglages.coordonnees_carte', '5.3600,-3.9800')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('coordonnees_carte'))->toBe('5.3600,-3.9800');

    $this->get(route('contact.index'))
        ->assertSee('Où nous trouver', false)
        ->assertSee(urlencode('5.3600,-3.9800'), false);
});

/** Les regles viennent de « Configuration » : une adresse invalide y est refusee. */
it('refuse une adresse e-mail invalide', function () {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'coordonnees')
        ->set('reglages.email_public', 'pas-une-adresse')
        ->call('enregistrer')
        ->assertHasErrors('reglages.email_public');
});

it('n affiche les reglages que sur les modules qui en portent', function (string $module) {
    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', $module)
        ->assertDontSee('wire:model="reglages.telephone"', false);
})->with(['banniere', 'formulaire', 'carte', 'messages']);

/* ------------------------------------------------------------------ */
/* Les reglages restent reserves aux administrateurs */
/* ------------------------------------------------------------------ */

/**
 * « Configuration » reserve ces cles aux administrateurs. Les ouvrir aux
 * editeurs depuis cet ecran aurait elargi un acces sans que personne ne
 * l'ait decide.
 */
it('interdit a un editeur d ecrire les reglages', function (string $module) {
    Livewire::actingAs($this->editeur)->test(PageContact::class)
        ->call('ouvrir', $module)
        ->call('enregistrer')
        ->assertForbidden();
})->with(['coordonnees', 'carte']);

it('laisse un editeur modifier les blocs de contenu', function () {
    Livewire::actingAs($this->editeur)->test(PageContact::class)
        ->call('ouvrir', 'formulaire')
        ->set('entete.titre_fr', 'Écrivez-nous')
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertOk();

    expect(ReglageDeSection::where('slug', 'contact.form')->value('titre_fr'))->toBe('Écrivez-nous');
});

it('previent l editeur que les reglages lui sont fermes', function () {
    Livewire::actingAs($this->editeur)->test(PageContact::class)
        ->call('ouvrir', 'coordonnees')
        ->assertSee('seul un administrateur peut les modifier', false);
});

/* ------------------------------------------------------------------ */
/* L'ecran embarque */
/* ------------------------------------------------------------------ */

it('embarque la liste des messages', function () {
    MessageDeContact::factory()->create();

    Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', 'messages')
        ->assertSee('wire:name="admin.message-liste"', false);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageContact::class)
        ->call('ouvrir', $module)
        ->html();

    foreach ([route('admin.configuration'), route('admin.messages')] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse);
    }
})->with(['banniere', 'formulaire', 'coordonnees', 'carte', 'messages']);

/* ------------------------------------------------------------------ */
/* Droits et acces */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageContact::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PageContact::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.contact'), false);
});

it('rend l ecran de page et ses composants imbriques', function () {
    MessageDeContact::factory()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.pages.contact'))
        ->assertOk()
        ->assertSee('Page Contact', false);
});
