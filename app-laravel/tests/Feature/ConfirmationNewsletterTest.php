<?php

use App\Models\AbonneNewsletter;

/*
 * L'inscription a la lettre d'information le DIT.
 *
 * Elle enregistrait deja l'adresse : la route, le controleur et le modele
 * etaient en place et testes. Mais cote visiteur, rien ne se voyait — le
 * champ se vidait, un point c'est tout. Le script prevoyait un remerciement,
 * lu dans un attribut `data-merci` que le pied de page ne portait nulle part :
 * le repli reaffichait donc le texte d'origine, et un refus du serveur
 * n'existait que dans la console.
 *
 * Ces tests gardent les deux messages attaches au gabarit. Leur affichage
 * releve du navigateur ; leur PRESENCE, non — et c'est elle qui manquait.
 */

it('porte les deux messages d inscription dans le pied de page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-merci', false)
        ->assertSee('data-erreur', false)
        ->assertSee('newsletter-msg', false);
});

it('annonce le message par un role status, sans voler le focus', function () {
    // Un visiteur au clavier doit rester dans le champ pour corriger son
    // adresse : une alerte qui prend le focus l'en sortirait.
    $this->get('/')
        ->assertOk()
        ->assertSee('role="status"', false)
        ->assertSee('aria-live="polite"', false);
});

it('enregistre l adresse envoyee depuis le pied de page', function () {
    $this->postJson('/newsletter', ['email' => 'awa@exemple.ci'])
        ->assertCreated();

    expect(AbonneNewsletter::where('email', 'awa@exemple.ci')->exists())->toBeTrue();
});

it('refuse une adresse invalide, ce que le pied de page peut alors dire', function () {
    // Le message d'erreur n'a de sens que si le serveur repond bien un refus
    // lisible : sans ce contrat, la page annoncerait un echec sur un succes.
    $this->postJson('/newsletter', ['email' => 'pas-une-adresse'])
        ->assertUnprocessable();

    expect(AbonneNewsletter::count())->toBe(0);
});
