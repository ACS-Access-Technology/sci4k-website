<?php

use App\Models\PageStatique;
use App\Models\Parametre;
use App\Models\ReglageDeSection;

/**
 * La page de contact est desormais rendue par Laravel, et non plus servie
 * comme fichier statique depuis public/.
 */
it('rend la page depuis Laravel', function () {
    $reponse = $this->get('/contact');

    $reponse->assertOk();
    $reponse->assertSee('page-contact', false);
});

/**
 * Le balisage que main.js interroge doit rester intact. handleContactSubmit
 * lit les champs PAR LEUR IDENTIFIANT, et le bloc de la page ne s'active que
 * sur body.page-contact : renommer l'un d'eux casserait l'envoi sans qu'aucun
 * autre test ne s'en apercoive.
 */
it('conserve les points d accroche de main.js', function (string $accroche) {
    $this->get('/contact')->assertSee($accroche, false);
})->with([
    'class="page-contact"',
    'id="contactForm"',
    'id="contactName"',
    'id="contactPhone"',
    'id="contactEmail"',
    'id="contactSubject"',
    'id="messageTextarea"',
    'id="contactSiteWeb"',
    'id="successAlert"',
    'id="formTitle"',
    'onsubmit="handleContactSubmit(event)"',
]);

it('sert les en-tetes de section depuis la base', function () {
    // updateOrCreate et non update : les sections ne sont semees que par
    // BlocsDeContenuSeeder, qui ne tourne pas pour cette suite.
    ReglageDeSection::updateOrCreate(['slug' => 'contact.page'], [
        'titre_fr' => 'Parlons de votre projet',
        'etiquette_fr' => 'Une etiquette a nous',
    ]);
    ReglageDeSection::updateOrCreate(['slug' => 'contact.form'], ['titre_fr' => 'Ecrivez-nous']);
    ReglageDeSection::updateOrCreate(['slug' => 'contact.map'], ['titre_fr' => 'Ou nous trouver']);

    $reponse = $this->get('/contact');

    $reponse->assertSee('Parlons de votre projet', false);
    $reponse->assertSee('Une etiquette a nous', false);
    $reponse->assertSee('Ecrivez-nous', false);
    $reponse->assertSee('Ou nous trouver', false);
});

/**
 * « Horaires » et « Coordonnees de la carte » etaient enregistres dans la
 * configuration sans que rien ne les lise nulle part. La page les applique.
 */
it('applique les reglages de l onglet contact', function () {
    Parametre::poser('adresse_postale', "Rue des Essais\nImmeuble Modele\nAbidjan");
    Parametre::poser('telephone', '+225 01 02 03 04 05');
    Parametre::poser('email_public', 'essai@sci4k.test');
    Parametre::poser('horaires', "Lundi : 09h00 - 12h00\nMardi : ferme");
    Parametre::poser('coordonnees_carte', '5.4000,-4.0000');

    $reponse = $this->get('/contact');

    $reponse->assertSee('Rue des Essais', false);
    $reponse->assertSee('Immeuble Modele', false);
    $reponse->assertSee('+225 01 02 03 04 05', false);
    $reponse->assertSee('essai@sci4k.test', false);
    $reponse->assertSee('Mardi : ferme', false);
    // La carte ET le lien partent des memes coordonnees. Ils divergeaient dans
    // la page d'origine, dont le lien portait un signe moins typographique
    // que Google ne sait pas lire.
    $reponse->assertSee('5.4000%2C-4.0000', false);
    expect(substr_count($reponse->getContent(), '5.4000%2C-4.0000'))->toBe(2);
});

it('injecte le numero WhatsApp de la configuration', function () {
    Parametre::poser('whatsapp', '+225 01 99 88 77 66');

    $this->get('/contact')->assertSee('window.SCI4K_WHATSAPP = "2250199887766"', false);
});

it('redirige l ancienne adresse', function () {
    $this->get('/contact.html')->assertRedirect('/contact');
});

/**
 * L'ecran « Pages editables » ne doit plus proposer « contact » : son contenu
 * ne serait servi par aucune adresse. C'etait la definition meme d'un ecran
 * menteur.
 */
it('ne propose plus contact parmi les pages editables', function () {
    expect(PageStatique::slugsEditables())
        ->not->toContain('contact')
        ->toContain('mentions-legales')
        ->toContain('politique-confidentialite');
});

it('reste complet quand la configuration est muette', function () {
    Parametre::query()->delete();
    ReglageDeSection::query()->delete();

    $reponse = $this->get('/contact');

    $reponse->assertOk();
    $reponse->assertSee('Contactez SCI4K', false);
    $reponse->assertSee('Cocody, Cité des Arts', false);
    $reponse->assertSee('id="contactForm"', false);
});
