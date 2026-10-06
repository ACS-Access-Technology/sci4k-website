<?php

use App\Models\Article;
use App\Models\Bien;
use App\Models\Encart;
use App\Models\EntreeDeMenu;
use App\Models\PageStatique;
use App\Models\Parametre;
use App\Routing\GenerateurDUrlBilingue;
use Database\Seeders\DemonstrationSeeder;

/**
 * Un lien interne reste dans la langue de la page.
 *
 * Le francais est servi sans prefixe (/services), l'anglais sous /en
 * (/en/services), avec les memes segments. route() suit la langue en cours ;
 * mais les menus, les boutons d'encart et celui de l'en-tete stockent des
 * CHEMINS — « / », « /biens.html », « /contact » — et les vues en ecrivaient
 * d'autres en dur. Sur /en, la navigation, le pied de page et la plupart des
 * boutons ramenaient au francais, souvent par le detour d'une redirection.
 *
 * Le test parcourt chaque page publique dans les deux langues et examine TOUS
 * les liens <a> qu'elle contient.
 */
beforeEach(function () {
    $this->seed();
    $this->seed(DemonstrationSeeder::class);

    // Les trois encarts, visibles, avec des cibles telles qu'un editeur les
    // saisit : un chemin moderne, une ancienne adresse, rien du tout.
    foreach (['accueil.annonce' => '/biens', 'accueil' => '/biens.html', 'services.annonce' => ''] as $slug => $cible) {
        Encart::updateOrCreate(['slug' => $slug], [
            'visible' => true, 'diffusion_de' => null, 'diffusion_a' => null,
            'titre_fr' => "Encart $slug", 'titre_en' => "Box $slug",
            'libelle_bouton_fr' => 'Voir', 'libelle_bouton_en' => 'See',
            'cible_bouton' => $cible,
        ]);
    }

    // Les deux pages legales publiees : non publiees, la route sert le
    // fichier HTML d'origine, qui n'a pas de version anglaise.
    PageStatique::query()->update(['publie' => true, 'contenu_fr' => '<p>Texte.</p>', 'contenu_en' => '<p>Text.</p>']);
});

/** Les pages publiques a parcourir, en chemin francais. */
function pagesPubliques(): array
{
    return [
        '/', '/presentation', '/services', '/biens', '/biens/'.Bien::publies()->value('slug'),
        '/actualites', '/actualites/'.Article::value('slug'), '/faq', '/contact',
        '/mentions-legales', '/politique-confidentialite',
    ];
}

/**
 * Les liens internes d'une page : [chemin, classe, texte].
 *
 * Sont ecartes : adresses externes, mailto:, tel:, ancres, et fichiers (images,
 * styles, scripts, televersements).
 *
 * @return list<array{0: string, 1: string, 2: string}>
 */
function liensInternes(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $hote = parse_url(config('app.url'), PHP_URL_HOST);
    $liens = [];

    foreach ($document->getElementsByTagName('a') as $a) {
        $href = trim($a->getAttribute('href'));
        $hoteDuLien = parse_url($href, PHP_URL_HOST);

        if ($href === '' || str_starts_with($href, '#') || preg_match('#^(mailto|tel):#i', $href)
            || ($hoteDuLien && $hoteDuLien !== $hote)) {
            continue;
        }

        $chemin = '/'.ltrim((string) parse_url($href, PHP_URL_PATH), '/');

        if (preg_match('#^/(storage|images|assets|build|fonts)/#', $chemin)) {
            continue;
        }

        $liens[] = [$chemin, $a->getAttribute('class'), trim($a->textContent)];
    }

    return $liens;
}

function estAnglais(string $chemin): bool
{
    return $chemin === '/en' || str_starts_with($chemin, '/en/');
}

it('garde chaque lien interne d une page anglaise en anglais', function () {
    foreach (pagesPubliques() as $page) {
        $adresse = $page === '/' ? '/en' : '/en'.$page;
        $html = $this->get($adresse)->assertOk()->getContent();

        foreach (liensInternes($html) as [$chemin, $classe, $texte]) {
            if (str_contains($classe, 'lang-toggle')) {
                continue;
            }

            expect(estAnglais($chemin))->toBeTrue("Sur $adresse, « $texte » mene a $chemin, hors de /en.");
        }
    }
});

it('garde chaque lien interne d une page francaise en francais', function () {
    foreach (pagesPubliques() as $page) {
        $html = $this->get($page)->assertOk()->getContent();

        foreach (liensInternes($html) as [$chemin, $classe, $texte]) {
            if (str_contains($classe, 'lang-toggle')) {
                continue;
            }

            expect(estAnglais($chemin))->toBeFalse("Sur $page, « $texte » mene a $chemin, en anglais.");
        }
    }
});

/**
 * Un lien interne ne passe plus par une ancienne adresse en .html quand une
 * page moderne existe : le detour coutait une redirection a chaque clic.
 */
it('ne fait plus passer les liens par les anciennes adresses', function () {
    foreach (pagesPubliques() as $page) {
        foreach ([$page, $page === '/' ? '/en' : '/en'.$page] as $adresse) {
            foreach (liensInternes($this->get($adresse)->getContent()) as [$chemin, , $texte]) {
                expect($chemin)->not->toEndWith('.html', "Sur $adresse, « $texte » passe par $chemin.");
            }
        }
    }
});

/* ------------------------------------------------ elements precis */

it('traduit la navigation principale et le pied de page', function () {
    $html = $this->get('/en')->getContent();
    $chemins = array_column(liensInternes($html), 0);

    // « /biens.html » et « /contact.html » des menus d'origine, rendus
    // directement vers la page anglaise.
    expect($chemins)->toContain('/en', '/en/presentation', '/en/biens', '/en/services', '/en/actualites', '/en/faq', '/en/contact');
});

it('mene les boutons d encart a la page de la langue courante', function () {
    expect(array_column(liensInternes($this->get('/en')->getContent()), 0))->toContain('/en/biens')
        ->and(array_column(liensInternes($this->get('/')->getContent()), 0))->toContain('/biens')
        ->and(array_column(liensInternes($this->get('/en/services')->getContent()), 0))->toContain('/en/contact');
});

it('traduit le bouton de l en-tete, meme saisi comme chemin', function () {
    Parametre::poser('cta_header_url', '/contact');

    $en = collect(liensInternes($this->get('/en/faq')->getContent()))->firstWhere(1, 'cta-btn');
    $fr = collect(liensInternes($this->get('/faq')->getContent()))->firstWhere(1, 'cta-btn');

    expect($en[0])->toBe('/en/contact')->and($fr[0])->toBe('/contact');
});

it('bascule de langue sur la meme page', function (string $fr, string $en) {
    $versEn = collect(liensInternes($this->get($fr)->getContent()))->first(fn ($l) => str_contains($l[1], 'lang-toggle'));
    $versFr = collect(liensInternes($this->get($en)->getContent()))->first(fn ($l) => str_contains($l[1], 'lang-toggle'));

    expect($versEn[0])->toBe($en)->and($versFr[0])->toBe($fr);
})->with([
    'accueil' => ['/', '/en'],
    'services' => ['/services', '/en/services'],
    'biens' => ['/biens', '/en/biens'],
    'contact' => ['/contact', '/en/contact'],
    'mentions' => ['/mentions-legales', '/en/mentions-legales'],
]);

/**
 * Le catalogue ouvre ses fiches dans une fenetre, par un bouton : il n'a pas
 * de lien vers elles. Les liens vers une fiche sont ceux de la page des
 * services, qui montre les biens de chaque activite.
 */
it('garde les liens vers les biens et les services dans la langue', function () {
    $fiches = collect(liensInternes($this->get('/en/services')->getContent()))
        ->pluck(0)->filter(fn ($c) => str_contains($c, '/biens/'));

    expect($fiches)->not->toBeEmpty()
        ->and($fiches->every(fn ($c) => str_starts_with($c, '/en/biens/')))->toBeTrue();

    $services = array_column(liensInternes($this->get('/en')->getContent()), 0);
    expect(collect($services)->filter(fn ($c) => str_starts_with($c, '/en/contact'))->isNotEmpty())->toBeTrue();
});

/* ------------------------------------------------ l'adresse saisie */

it('ramene une adresse saisie a la route de la langue courante', function (string $saisie, string $fr, string $en) {
    app()->setLocale('fr');
    expect(parse_url(GenerateurDUrlBilingue::localiser($saisie), PHP_URL_PATH) ?: '/')->toBe($fr);

    app()->setLocale('en');
    expect(parse_url(GenerateurDUrlBilingue::localiser($saisie), PHP_URL_PATH))->toBe($en);
})->with([
    'accueil' => ['/', '/', '/en'],
    'chemin moderne' => ['/services', '/services', '/en/services'],
    'ancienne adresse redirigee' => ['/biens.html', '/biens', '/en/biens'],
    'nom de route' => ['faq.index', '/faq', '/en/faq'],
    'chemin deja anglais' => ['/en/faq', '/faq', '/en/faq'],
]);

it('conserve la requete et l ancre d une adresse saisie', function () {
    app()->setLocale('en');

    expect(GenerateurDUrlBilingue::localiser('/biens?type=villa#grille'))->toEndWith('/en/biens?type=villa#grille');
});

it('laisse passer ce qui n est pas une page bilingue', function (string $cible) {
    app()->setLocale('en');

    expect(GenerateurDUrlBilingue::localiser($cible))->toBe($cible);
})->with([
    'externe' => ['https://www.exemple.ci/page'],
    'courriel' => ['mailto:info@acsgroupe.ci'],
    'ancre' => ['#contact'],
    'fichier servi par le serveur' => ['/images/plan-du-quartier.pdf'],
    'adresse inconnue' => ['/nulle-part'],
    'autre domaine deguise' => ['//exemple.test/biens'],
]);

it('refuse une cible de bouton dangereuse', function () {
    $encart = new Encart(['cible_bouton' => 'javascript:alert(1)']);
    app()->setLocale('en');

    expect($encart->lienDuBouton('contact.index'))->toEndWith('/en/contact');
});

it('garde a l entree de menu son comportement pour une route nommee', function () {
    app()->setLocale('en');

    expect((new EntreeDeMenu(['cible' => 'services.index']))->lien())->toEndWith('/en/services')
        ->and((new EntreeDeMenu(['cible' => 'javascript:alert(1)']))->lien())->toBe('#');
});
