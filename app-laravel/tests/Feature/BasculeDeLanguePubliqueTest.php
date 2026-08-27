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
 * Ces tests fixent le mecanisme unique retenu : le bouton appelle la route de
 * bascule, le serveur retient la langue en session et rend TOUT dans cette
 * langue au rechargement.
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
    $this->get(route('langue.basculer', 'en'));

    $this->get('/services')->assertOk()->assertSee('Land & Title');
});

it('bascule le texte fixe de la page en meme temps que le contenu', function () {
    $francais = $this->get('/services')->assertOk()->getContent();

    $this->get(route('langue.basculer', 'en'));
    $anglais = $this->get('/services')->assertOk()->getContent();

    expect($francais)->toContain('Nos Services &amp; Prestations');
    expect($anglais)->not->toContain('Nos Services &amp; Prestations');
});

it('bascule l entete et le pied, seuls fragments restes cote client', function () {
    // C'est ici que se jouait le defaut : l'entete et le pied basculaient par
    // data-i18n sans que le serveur le sache, si bien qu'ils passaient a
    // l'anglais pendant que tout le rendu serveur restait francais.
    $this->get(route('langue.basculer', 'en'));
    $anglais = $this->get('/services')->assertOk()->getContent();

    $pied = substr($anglais, strrpos($anglais, '<footer'));

    expect($pied)->toContain('Navigation');
    expect($pied)->not->toContain('Nous contacter');
    expect($anglais)->not->toContain('data-i18n');
});

it('propose dans l entete un lien vers l autre langue', function () {
    $this->get('/services')->assertOk()->assertSee(route('langue.basculer', 'en'), false);

    $this->get(route('langue.basculer', 'en'));
    $this->get('/services')->assertOk()->assertSee(route('langue.basculer', 'fr'), false);
});

it('accorde l attribut lang du document a la langue servie', function () {
    $this->get('/services')->assertOk()->assertSee('<html lang="fr">', false);

    $this->get(route('langue.basculer', 'en'));
    $this->get('/services')->assertOk()->assertSee('<html lang="en">', false);
});

it('aligne le stockage local du navigateur sur la langue du serveur', function () {
    // Sans cet accord, main.js rappliquerait l'ancienne langue au chargement
    // et re-basculerait le texte porteur de data-i18n dans le mauvais sens.
    $this->get(route('langue.basculer', 'en'));

    $this->get('/services')->assertOk()->assertSee("sci4k-lang', 'en'", false);
});

it('refuse une langue inconnue', function () {
    $this->get(route('langue.basculer', 'de'))->assertNotFound();
});

it('bascule la page FAQ comme la page des services', function () {
    $this->get(route('langue.basculer', 'en'));

    $this->get('/faq')->assertOk()->assertDontSee('Questions fréquentes', false);
});

it('bascule la liste des actualites', function () {
    $this->get(route('langue.basculer', 'en'));

    $this->get('/actualites')->assertOk()->assertDontSee('Actualités SCI4K', false);
});
