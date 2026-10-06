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
    // Le formulaire ne nomme plus la fonction a appeler : il declare ce qu'il
    // est, et main.js decide. L'accroche reste, elle a seulement change de
    // forme en meme temps que le JavaScript en ligne a quitte le balisage.
    'data-envoi="contact"',
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

/* ------------------------------------------------ un moyen de repondre */

/** Les attributs d'un champ du formulaire, lus dans la page rendue. */
function champDuContact(string $html, string $id): DOMElement
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

    return $document->getElementById($id);
}

/**
 * Telephone OU e-mail. Le navigateur exigeait les deux (« required » sur
 * chacun), le serveur aucun : aucun des deux ne l'est plus a lui seul, et
 * main.js bloque l'envoi quand les deux sont vides, avec le message que porte
 * le formulaire. Le nom et le message, eux, restent exiges.
 */
it('n exige plus le telephone et l e-mail a la fois', function (string $adresse) {
    $html = $this->get($adresse)->assertOk()->getContent();

    expect(champDuContact($html, 'contactPhone')->hasAttribute('required'))->toBeFalse()
        ->and(champDuContact($html, 'contactEmail')->hasAttribute('required'))->toBeFalse()
        ->and(champDuContact($html, 'contactName')->hasAttribute('required'))->toBeTrue()
        ->and(champDuContact($html, 'messageTextarea')->hasAttribute('required'))->toBeTrue()
        // Le champ piege est toujours la.
        ->and(champDuContact($html, 'contactSiteWeb'))->not->toBeNull();
})->with(['/contact', '/en/contact']);

it('annonce la regle dans la langue de la page', function (string $adresse, string $attendu) {
    $html = $this->get($adresse)->getContent();

    expect(champDuContact($html, 'contactForm')->getAttribute('data-erreur-coordonnees'))->toBe($attendu);
})->with([
    'francais' => ['/contact', 'Indiquez une adresse e-mail ou un numéro de téléphone pour que nous puissions vous répondre.'],
    'anglais' => ['/en/contact', 'Please give an email address or a phone number so that we can reply to you.'],
]);

it('laisse l agence reformuler le message', function () {
    ReglageDeSection::updateOrCreate(['slug' => 'contact.form'], [
        'titre_fr' => 'Écrivez-nous',
        'options' => ['erreur_coordonnees_fr' => 'Un téléphone ou un e-mail, s’il vous plaît.'],
    ]);

    $html = $this->get('/contact')->getContent();

    expect(champDuContact($html, 'contactForm')->getAttribute('data-erreur-coordonnees'))
        ->toBe('Un téléphone ou un e-mail, s’il vous plaît.');
});

/** Le script qui applique la regle cote navigateur est bien celui du site. */
it('fait appliquer la regle par main.js', function () {
    $script = file_get_contents(base_path('../maquettes-frontoffice/assets/main.js'));

    expect($script)->toContain("getAttribute('data-erreur-coordonnees')")
        ->toContain('setCustomValidity');
});

/**
 * Sans adresse saisie, le site retombait sur contact@sci4k.com — une boite
 * qui n'existe pas chez Google Workspace : les courriels des visiteurs
 * revenaient en erreur. Le repli est la boite que l'agence releve, et les
 * donnees structurees suivent la meme adresse que la page.
 */
it('affiche l adresse relevee par l agence quand aucune n est configuree', function () {
    $page = $this->get('/contact')->assertOk()->getContent();

    expect($page)->toContain('info@acsgroupe.ci')
        ->toContain('"email": "info@acsgroupe.ci"')
        ->not->toContain('contact@sci4k.com');
});

it('reprend l adresse configuree dans les donnees structurees', function () {
    Parametre::poser('email_public', 'essai@sci4k.test');

    expect($this->get('/contact')->getContent())->toContain('"email": "essai@sci4k.test"');
});
