<?php

use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Livewire\Admin\PageFaq;
use App\Livewire\Admin\RubriqueFaqFormulaire;
use App\Livewire\Admin\RubriqueFaqListe;
use App\Models\Parametre;
use App\Models\QuestionFaq;
use App\Models\ReglageDeSection;
use App\Models\RubriqueFaq;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Sixieme ecran de la refonte : la page FAQ.
 *
 * Les rubriques ont leur propre module, avant les questions : sur le site, le
 * titre de chaque groupe EST le nom de la rubrique, et masquer une rubrique
 * retire aussi ses questions. Les fondre dans le module des questions aurait
 * cache une decision qui gouverne la page entiere.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->rubrique = RubriqueFaq::factory()->create();
});

/** Raccourci : une question rattachee a la rubrique du test. */
function uneQuestion(array $attributs = []): QuestionFaq
{
    return QuestionFaq::factory()->create($attributs + ['rubrique_id' => test()->rubrique->id]);
}

it('affiche les quatre modules dans l ordre du site', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->assertOk()
        ->assertSeeInOrder(['Bannière', 'Rubriques', 'Questions', 'Poser une question']);
});

it('refuse un module inconnu', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'inexistant')
        ->assertNotFound();
});

it('enregistre l en-tete de la banniere et la sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'banniere')
        ->set('entete.titre_fr', 'Vos questions, nos réponses')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('Vos questions, nos réponses', false);
});

it('enregistre le bloc de demande et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->set('entete.titre_fr', 'Une autre question ?')
        ->set('entete.chapo_fr', 'Un conseiller vous répond sous 24 heures.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'faq.ask')->value('titre_fr'))
        ->toBe('Une autre question ?');

    $this->get(route('faq.index'))->assertSee('Une autre question ?', false);
});

/** Le bloc de demande n'affiche pas d'etiquette : le champ serait sans effet. */
it('ne propose pas d etiquette sur le module Poser une question', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->html();

    expect($rendu)->toContain('wire:model="entete.titre_fr"')
        ->and($rendu)->toContain('wire:model="entete.chapo_fr"')
        ->and($rendu)->not->toContain('wire:model="entete.etiquette_fr"');
});

/* ------------------------------------------------------------------ */
/* Les textes du formulaire « poser une question »                     */
/* ------------------------------------------------------------------ */

/**
 * Les huit libelles etaient ecrits en dur dans la vue publique et traduits par
 * __() : aucun ecran du backoffice ne les exposait. Le module les porte tous,
 * sans quoi une partie du bloc resterait hors de portee de l'editeur.
 */
it('propose tous les textes du formulaire', function () {
    $rendu = Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->html();

    foreach (array_keys(PageFaq::TEXTES_DU_FORMULAIRE) as $nom) {
        expect($rendu)->toContain('wire:model="textes.'.$nom.'_fr"');
    }
});

it('n affiche ces textes que sur le module Poser une question', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', $module)
        ->html();

    expect($rendu)->not->toContain('wire:model="textes.libelle_bouton_fr"');
})->with(['banniere', 'rubriques', 'questions']);

it('enregistre un libelle et le sert sur le site', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->set('textes.libelle_bouton_fr', 'Envoyer sur WhatsApp')
        ->set('textes.libelle_nom_fr', 'Votre nom')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(ReglageDeSection::where('slug', 'faq.ask')->first()->option('libelle_bouton_fr'))
        ->toBe('Envoyer sur WhatsApp');

    $this->get(route('faq.index'))
        ->assertSee('Envoyer sur WhatsApp', false)
        ->assertSee('Votre nom', false);
});

/** Laisse vide, un texte retombe sur celui d'origine, comme avant la refonte. */
it('retombe sur le texte d origine quand le champ est vide', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get(route('faq.index'))->assertSee('Envoyer ma question', false);
});

/**
 * Repli sur l'autre langue : mieux vaut un libelle dans la mauvaise langue
 * qu'un champ sans nom sur un bloc a demi traduit.
 */
it('replie sur l autre langue quand une seule est saisie', function () {
    $section = ReglageDeSection::pour('faq.ask');
    $section->poserOptions(['libelle_bouton_fr' => 'Envoyer sur WhatsApp']);
    $section->save();

    expect($section->texteBilingue('libelle_bouton', 'en'))->toBe('Envoyer sur WhatsApp')
        ->and($section->texteBilingue('libelle_bouton', 'fr'))->toBe('Envoyer sur WhatsApp');
});

/**
 * `$textes` est une propriete publique : le navigateur en fixe le contenu,
 * CLES COMPRISES. Sans filtre sur les cles declarees, n'importe quelle option
 * de la section serait ecrivable sans passer par aucune regle — la mise en
 * page du processus, par exemple.
 */
it('n ecrit que les textes que le module declare', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'demande')
        ->set('textes.mise_en_page', 'liste')
        ->set('textes.libelle_bouton_fr', 'Envoyer')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $options = ReglageDeSection::where('slug', 'faq.ask')->first()->options;

    expect($options)->not->toHaveKey('mise_en_page')
        ->and($options['libelle_bouton_fr'])->toBe('Envoyer');
});

/**
 * Le formulaire ouvre une conversation WhatsApp depuis assets/main.js, qui lit
 * window.SCI4K_WHATSAPP. Cette ligne n'existait que sur la page Contact : ici,
 * main.js retombait sur le numero qu'il porte en dur, et le reglage du
 * backoffice restait sans effet sur ce seul formulaire.
 */
it('sert le numero WhatsApp regle dans le backoffice', function () {
    Parametre::poser('whatsapp', '+225 01 02 03 04 05');

    $this->get(route('faq.index'))
        ->assertOk()
        ->assertSee('window.SCI4K_WHATSAPP = "2250102030405"', false);
});

it('n affiche pas de formulaire sur les modules sans en-tete', function (string $module) {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', $module)
        ->assertDontSee('wire:model="entete.titre_fr"', false);
})->with(['rubriques', 'questions']);

it('refuse d enregistrer un module sans section', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'questions')
        ->call('enregistrer')
        ->assertNotFound();
});

/* ------------------------------------------------------------------ */
/* Les ecrans embarques                                                */
/* ------------------------------------------------------------------ */

it('embarque la liste des rubriques dans le module Rubriques', function () {
    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'rubriques')
        ->assertSee('wire:name="admin.rubrique-faq-liste"', false);
});

it('embarque la liste des questions dans le module Questions', function () {
    uneQuestion();

    Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', 'questions')
        ->assertSee('wire:name="admin.faq-liste"', false);
});

/** L'ecran ne doit renvoyer vers aucun ecran voue a disparaitre. */
it('ne renvoie vers aucun ancien ecran', function (string $module) {
    $rendu = Livewire::actingAs($this->admin)->test(PageFaq::class)
        ->call('ouvrir', $module)
        ->html();

    foreach (['/admin/faq', '/admin/rubriques-faq'] as $adresse) {
        expect($rendu)->not->toContain('href="'.$adresse);
    }
})->with(['banniere', 'rubriques', 'questions', 'demande']);

/* ------------------------------------------------------------------ */
/* L'edition sur place                                                 */
/* ------------------------------------------------------------------ */

it('ouvre une question sur place', function () {
    $question = uneQuestion();

    Livewire::actingAs($this->admin)
        ->test(FaqListe::class, ['embarque' => true])
        ->assertSet('formulaireOuvert', null)
        ->call('ouvrirEdition', $question->id)
        ->assertSet('formulaireOuvert', $question->id)
        ->assertSee('wire:name="admin.faq-formulaire"', false);
});

it('ouvre une rubrique sur place', function () {
    Livewire::actingAs($this->admin)
        ->test(RubriqueFaqListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $this->rubrique->id)
        ->assertSet('formulaireOuvert', $this->rubrique->id)
        ->assertSee('wire:name="admin.rubrique-faq-formulaire"', false);
});

it('ouvre la creation sur place', function (string $composant) {
    Livewire::actingAs($this->admin)
        ->test($composant, ['embarque' => true])
        ->call('ouvrirCreation')
        ->assertSet('formulaireOuvert', 'creation');
})->with([[FaqListe::class], [RubriqueFaqListe::class]]);

it('refuse un identifiant inexistant', function (string $composant) {
    Livewire::actingAs($this->admin)
        ->test($composant, ['embarque' => true])
        ->call('ouvrirEdition', 99999)
        ->assertNotFound();
})->with([[FaqListe::class], [RubriqueFaqListe::class]]);

it('interdit l ouverture a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');
    $question = uneQuestion();

    Livewire::actingAs($lecteur)
        ->test(FaqListe::class, ['embarque' => true])
        ->call('ouvrirEdition', $question->id)
        ->assertForbidden();
});




/**
 * Enregistres depuis une liste, les formulaires ne redirigent pas : ils
 * previennent la liste, qui se referme. Une redirection ferait quitter l'ecran
 * de page au milieu d'une modification.
 */
it('ne redirige pas quand le formulaire de question est embarque', function () {
    $question = uneQuestion();

    Livewire::actingAs($this->admin)
        ->test(FaqFormulaire::class, ['question' => $question, 'embarque' => true])
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('bloc-enregistre');
});

it('ne redirige pas quand le formulaire de rubrique est embarque', function () {
    Livewire::actingAs($this->admin)
        ->test(RubriqueFaqFormulaire::class, ['rubrique' => $this->rubrique, 'embarque' => true])
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertDispatched('bloc-enregistre');
});

/**
 * Le formulaire de question sait creer une rubrique a la volee. Cette voie doit
 * survivre a l'embarquement : sans elle, il faudrait sortir du module Questions
 * pour ajouter une rubrique, ce que la refonte veut precisement eviter.
 */
it('cree encore une rubrique a la volee depuis le formulaire embarque', function () {
    Livewire::actingAs($this->admin)
        ->test(FaqFormulaire::class, ['embarque' => true])
        ->set('rubriqueId', FaqFormulaire::NOUVELLE_RUBRIQUE)
        ->set('nouvelleRubriqueFr', 'Fiscalité')
        ->set('nouvelleRubriqueEn', 'Taxation')
        ->set('questionFr', 'Quels impôts sur un bien locatif ?')
        ->set('questionEn', 'What taxes on a rental property?')
        ->set('reponseFr', 'Cela dépend du régime choisi.')
        ->set('reponseEn', 'It depends on the chosen regime.')
        ->call('enregistrer')
        ->assertHasNoErrors()
        ->assertDispatched('bloc-enregistre');

    expect(RubriqueFaq::where('nom_fr', 'Fiscalité')->exists())->toBeTrue();
});

/* ------------------------------------------------------------------ */
/* L'ecran complet, rendu de bout en bout                              */
/* ------------------------------------------------------------------ */

/**
 * `Livewire::test()` ne rend les composants imbriques qu'en marque-place : il
 * montre `wire:name`, pas leur contenu. Seule une vraie requete HTTP traverse
 * l'arbre entier — ecran de page, liste embarquee, formulaire.
 */
it('rend l ecran de page et ses composants imbriques', function () {
    uneQuestion(['question_fr' => 'Puis-je acheter sans apport ?']);

    $this->actingAs($this->admin)
        ->get(route('admin.pages.faq'))
        ->assertOk()
        ->assertSee('Page FAQ', false);

    Livewire::actingAs($this->admin)->test(FaqListe::class, ['embarque' => true])
        ->assertSee('Puis-je acheter sans apport ?', false);
});

/* ------------------------------------------------------------------ */
/* Droits et acces                                                     */
/* ------------------------------------------------------------------ */

it('interdit toute ecriture a un lecteur sur l ecran de page', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)->test(PageFaq::class)
        ->assertOk()
        ->call('enregistrer')
        ->assertForbidden();
});

it('ferme l ecran a un compte sans role', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(PageFaq::class)
        ->assertForbidden();
});

it('est atteignable depuis la barre laterale', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('admin.pages.faq'), false);
});
