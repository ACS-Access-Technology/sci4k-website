<?php

use App\Models\Categorie;
use App\Models\Service;

/*
 * Bascule de langue sur les pages publiques.
 *
 * Defaut trouve a la verification de bout en bout du lot 2a : le portage des
 * pages vers Blade avait laisse la bascule cote client — main.js echangeait le
 * texte porteur de data-i18n sans rien dire au serveur — pendant que le
 * contenu passait cote serveur, rendu selon la session. Les deux mecanismes ne
 * se parlaient pas : un visiteur choisissant l'anglais gardait 42 blocs de
 * texte sur 62 en francais sur /services.
 *
 * Ces tests fixaient le mecanisme d'alors : la langue vivait en session, et la
 * bascule l'y ecrivait. Elle vit maintenant dans l'ADRESSE — /services et
 * /en/services — parce qu'une session ne se partage pas et qu'un moteur de
 * recherche n'en a pas : le site anglais lui etait entierement invisible.
 *
 * Ils disent donc le contrat en vigueur : demander l'adresse anglaise sert la
 * page anglaise, entierement, sans qu'aucun etat n'ait a etre retenu.
 */
beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'foncier', 'visible' => true, 'ordre' => 1,
        'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title',
        'accroche_fr' => 'Sécuriser vos terrains', 'accroche_en' => 'Secure your land',
    ]);
});

it('sert la page en francais par defaut', function () {
    $this->get('/services')->assertOk()->assertSee('Foncier')->assertDontSee('Land & Title');
});

it('retient la langue choisie et sert le contenu de la base en anglais', function () {
    $this->get('/en/services')->assertOk()->assertSee('Land & Title');
});

it('bascule le texte fixe de la page en meme temps que le contenu', function () {
    $francais = $this->get('/services')->assertOk()->getContent();

    $anglais = $this->get('/en/services')->assertOk()->getContent();

    expect($francais)->toContain('Nos Services &amp; Prestations');
    expect($anglais)->not->toContain('Nos Services &amp; Prestations');
});

it('bascule l entete et le pied, seuls fragments restes cote client', function () {
    // C'est ici que se jouait le defaut : l'entete et le pied basculaient par
    // data-i18n sans que le serveur le sache, si bien qu'ils passaient a
    // l'anglais pendant que tout le rendu serveur restait francais.
    $anglais = $this->get('/en/services')->assertOk()->getContent();

    $pied = substr($anglais, strrpos($anglais, '<footer'));

    expect($pied)->toContain('Navigation');
    expect($pied)->not->toContain('Nous contacter');
    expect($anglais)->not->toContain('data-i18n');
});

it('propose dans l entete un lien vers LA MEME PAGE dans l autre langue', function () {
    // Et non vers une route qui reecrivait un etat : le visiteur qui lisait
    // « Nos services » en anglais et bascule doit arriver sur « Nos services »
    // en francais, jamais sur l'accueil.
    $this->get('/services')->assertOk()->assertSee(url('/en/services'), false);
    $this->get('/en/services')->assertOk()->assertSee(url('/services'), false);
});

it('accorde l attribut lang du document a la langue servie', function () {
    $this->get('/services')->assertOk()->assertSee('<html lang="fr">', false);

    $this->get('/en/services')->assertOk()->assertSee('<html lang="en">', false);
});

it('aligne le stockage local du navigateur sur la langue du serveur', function () {
    // Sans cet accord, main.js rappliquerait l'ancienne langue au chargement
    // et re-basculerait le texte porteur de data-i18n dans le mauvais sens.
    $this->get('/en/services')->assertOk()->assertSee("sci4k-lang', 'en'", false);
});

it('refuse une langue inconnue', function () {
    $this->get(route('langue.basculer', 'de'))->assertNotFound();
});

it('renvoie la bascule vers la meme page traduite', function () {
    // La route survit : les pages statiques encore servies depuis public/
    // pointent dessus, et main.js l'appelle. Elle ne retient plus rien — elle
    // renvoie a l'adresse de l'autre langue.
    $this->get(route('langue.basculer', 'en'), ['referer' => url('/services')])
        ->assertRedirect(url('/en/services'));

    $this->get(route('langue.basculer', 'fr'), ['referer' => url('/en/services')])
        ->assertRedirect(url('/services'));
});

it('bascule la page FAQ comme la page des services', function () {
    $this->get('/en/faq')->assertOk()->assertDontSee('Questions fréquentes', false);
});

it('bascule la liste des actualites', function () {
    $this->get('/en/actualites')->assertOk()->assertDontSee('Actualités SCI4K', false);
});
