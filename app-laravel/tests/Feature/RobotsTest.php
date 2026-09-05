<?php

use App\Models\Parametre;

/*
 * Le fichier robots.txt.
 *
 * Il existait en deux exemplaires qui ne se parlaient pas : un fichier fige
 * dans public/, servi par le serveur web avant meme d'entrer dans PHP, et un
 * champ « Fichier robots.txt » dans l'ecran Configuration que RIEN ne lisait.
 * L'editeur pouvait le remplir, l'enregistrer, le relire — le site ne changeait
 * pas d'un octet.
 *
 * Le fichier fige annonçait par ailleurs « Disallow: » sans rien, et ne
 * declarait pas le plan du site.
 */
it('est servi par Laravel et non par un fichier fige', function () {
    expect(file_exists(public_path('robots.txt')))->toBeFalse(
        'Un fichier public/robots.txt masquerait la route : le serveur le sert avant PHP.',
    );

    $reponse = $this->get('/robots.txt')->assertOk();

    expect($reponse->headers->get('Content-Type'))->toContain('text/plain');
});

it('declare le plan du site', function () {
    // C'est ce qui manquait le plus : sans cette ligne, chaque moteur doit
    // deviner l'adresse du plan.
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('plan-du-site'), false);
});

it('sert ce que l editeur a saisi', function () {
    Parametre::poser('robots_txt', "User-agent: *\nDisallow: /interne", 'seo');

    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /interne', false);
});

it('n ajoute pas un second plan du site quand l editeur l a deja ecrit', function () {
    Parametre::poser('robots_txt', "User-agent: *\nSitemap: https://exemple.test/sitemap.xml", 'seo');

    $contenu = $this->get('/robots.txt')->assertOk()->getContent();

    expect(substr_count(strtolower($contenu), 'sitemap:'))->toBe(1);
});

it('refuse tout le site des la porte quand l indexation est coupee', function () {
    Parametre::poser('autoriser_indexation', '0', 'seo');

    // La balise meta de la page ne suffit pas : elle est lue APRES avoir charge
    // la page, robots.txt AVANT de la demander. Un site en preparation doit
    // etre refuse des la porte.
    $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
});

it('laisse le site ouvert par defaut, hors administration', function () {
    // Aucun reglage enregistre : c'est l'etat d'une installation neuve. Un site
    // qui se refuserait aux moteurs par defaut serait invisible sans que
    // personne ne l'ait decide.
    $contenu = $this->get('/robots.txt')->assertOk()->getContent();

    expect($contenu)->toContain('Disallow: /admin')
        ->and($contenu)->not->toContain("Disallow: /\n");
});
