<?php

use App\Livewire\Admin\PagesStatiques;
use App\Models\PageStatique;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Les deux pages legales, servies par Laravel dans les deux langues.
 *
 * Jusqu'ici, tools/sync-frontoffice.sh copiait mentions-legales.html et
 * politique-confidentialite.html dans public/, et le serveur les servait avant
 * Laravel. Le pied de page y renvoyait, dans les deux langues : le visiteur
 * lisait une ANCIENNE version de la politique de confidentialite, corrigee
 * depuis en base, et la version anglaise de l'adresse n'existait pas.
 *
 * Desormais : les pages vivent a /mentions-legales et /politique-
 * confidentialite (et sous /en), les anciennes adresses redirigent en 301, et
 * les liens du site menent directement aux nouvelles.
 *
 * La base de test a recu les migrations : la politique y est publiee en
 * francais et en anglais ; les mentions y sont en brouillon, publiees ici
 * quand un test les veut en ligne.
 */
beforeEach(function () {
    $this->seed();
});

/** Publie les mentions legales, telles que la migration les a versees. */
function publierLesMentions(): void
{
    PageStatique::where('slug', 'mentions-legales')->update(['publie' => true]);
}

/** Les balises de tete d'une page, lues dans le HTML rendu. */
function teteDeLaPage(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
    $xpath = new DOMXPath($document);
    $valeur = fn (string $requete) => $xpath->query($requete)->item(0)?->nodeValue;

    return [
        'lang' => $valeur('/html/@lang'),
        'titre' => trim((string) $valeur('//title')),
        'description' => $valeur('//meta[@name="description"]/@content'),
        'canonique' => $valeur('//link[@rel="canonical"]/@href'),
        'fr' => $valeur('//link[@rel="alternate"][@hreflang="fr"]/@href'),
        'en' => $valeur('//link[@rel="alternate"][@hreflang="en"]/@href'),
        'x-default' => $valeur('//link[@rel="alternate"][@hreflang="x-default"]/@href'),
        'og:url' => $valeur('//meta[@property="og:url"]/@content'),
        'og:locale' => $valeur('//meta[@property="og:locale"]/@content'),
        'og:title' => $valeur('//meta[@property="og:title"]/@content'),
    ];
}

/* ------------------------------------------------ routes */

it('sert les quatre pages depuis Laravel', function (string $adresse) {
    publierLesMentions();

    // « page-statique » est la classe du gabarit Laravel : le fichier
    // d'origine ne la porte pas.
    $this->get($adresse)->assertOk()->assertSee('page-statique', false);
})->with(['/mentions-legales', '/politique-confidentialite', '/en/mentions-legales', '/en/politique-confidentialite']);

it('redirige les anciennes adresses en 301 vers les nouvelles', function (string $ancienne, string $nouvelle) {
    // Relative, comme celle des autres anciennes adresses (/biens.html).
    $this->get($ancienne)
        ->assertStatus(301)
        ->assertHeader('Location', $nouvelle);
})->with([
    ['/mentions-legales.html', '/mentions-legales'],
    ['/politique-confidentialite.html', '/politique-confidentialite'],
]);

/**
 * Le site statique changeait de langue sans changer d'adresse : aucune
 * adresse anglaise en .html n'a jamais existe, et aucune n'est inventee.
 */
it('n invente pas d ancienne adresse anglaise', function (string $adresse) {
    $this->get($adresse)->assertNotFound();
})->with(['/en/mentions-legales.html', '/en/politique-confidentialite.html']);

/**
 * Les fichiers ne doivent plus arriver dans public/ : le serveur les y
 * servirait avant Laravel, et la redirection ne s'appliquerait jamais. Ce
 * test ne peut pas le verifier en servant la page — c'est Laravel qui repond
 * ici — d'ou la lecture du script lui-meme.
 */
it('ne copie plus les pages legales dans public', function () {
    $script = file_get_contents(base_path('../tools/sync-frontoffice.sh'));

    preg_match('/^exclues=\((.*)\)$/m', $script, $liste);

    expect($liste[1])->toContain('"mentions-legales.html"')->toContain('"politique-confidentialite.html"');
});

/* ------------------------------------------------ bilingue */

it('sert a chaque langue son propre texte', function (string $slug, string $francais, string $anglais) {
    publierLesMentions();

    $fr = $this->get("/$slug")->getContent();
    $en = $this->get("/en/$slug")->getContent();

    expect($fr)->toContain($francais)->not->toContain($anglais)
        ->and($en)->toContain($anglais)->not->toContain($francais);
})->with([
    'mentions' => ['mentions-legales', '1. Éditeur du site', '1. Site publisher'],
    'politique' => ['politique-confidentialite', '1. Qui sommes-nous', '1. Who we are'],
]);

/* ------------------------------------------------ SEO */

it('porte des balises de langue coherentes', function (string $slug, string $titreFr, string $titreEn) {
    publierLesMentions();

    $fr = teteDeLaPage($this->get("/$slug")->getContent());
    $en = teteDeLaPage($this->get("/en/$slug")->getContent());

    expect($fr)->toMatchArray([
        'lang' => 'fr',
        'canonique' => url("/$slug"),
        'fr' => url("/$slug"),
        'en' => url("/en/$slug"),
        'x-default' => url("/$slug"),
        'og:url' => url("/$slug"),
        'og:locale' => 'fr_FR',
    ])->and($fr['titre'])->toStartWith($titreFr);

    expect($en)->toMatchArray([
        'lang' => 'en',
        'canonique' => url("/en/$slug"),
        'fr' => url("/$slug"),
        'en' => url("/en/$slug"),
        'x-default' => url("/$slug"),
        'og:url' => url("/en/$slug"),
        'og:locale' => 'en_US',
    ])->and($en['titre'])->toStartWith($titreEn);

    foreach ([$fr, $en] as $tete) {
        expect($tete['description'])->not->toBeEmpty()
            ->and($tete['og:title'])->toBe($tete['titre'])
            ->and($tete['canonique'])->not->toContain('.html');
    }
})->with([
    'mentions' => ['mentions-legales', 'Mentions légales', 'Legal notices'],
    'politique' => ['politique-confidentialite', 'Politique de confidentialité', 'Privacy policy'],
]);

/* ------------------------------------------------ securite */

const PIEGES_HTML = '<script>alert(1)</script><img src=x onerror=alert(2)><a href="javascript:alert(3)">piege</a>';

/**
 * Le filtre de la phase 1 vaut pour les quatre adresses, que le contenu
 * vienne de l'ecran ou soit ecrit directement en base.
 */
it('ne rend jamais de script depuis la base', function (string $slug) {
    PageStatique::where('slug', $slug)->update([
        'publie' => true,
        'contenu_fr' => '<p>Texte FR.</p>'.PIEGES_HTML,
        'contenu_en' => '<p>Text EN.</p>'.PIEGES_HTML,
    ]);

    foreach (["/$slug", "/en/$slug"] as $adresse) {
        $corps = $this->get($adresse)->assertOk()->getContent();

        expect($corps)->not->toContain('alert(')
            ->not->toContain('onerror')
            ->not->toContain('javascript:')
            ->toContain('>piege</a>');
    }
})->with(['mentions-legales', 'politique-confidentialite']);

it('ne rend jamais de script saisi depuis l administration', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    Livewire::actingAs($admin)
        ->test(PagesStatiques::class)
        ->set('page', 'mentions-legales')
        ->set('titreFr', 'Mentions légales')
        ->set('contenuFr', '<p>Texte FR.</p>'.PIEGES_HTML)
        ->set('contenuEn', '<p>Text EN.</p>'.PIEGES_HTML)
        ->set('publie', true)
        ->call('enregistrer')
        ->assertHasNoErrors();

    foreach (['/mentions-legales', '/en/mentions-legales'] as $adresse) {
        expect($this->get($adresse)->getContent())
            ->not->toContain('alert(')->not->toContain('onerror')->not->toContain('javascript:');
    }
});

/* ------------------------------------------------ pied de page */

it('mene le pied de page aux pages legales de la langue courante', function (string $accueil, string $prefixe) {
    $corps = $this->get($accueil)->getContent();

    expect($corps)
        ->toContain('href="'.url($prefixe.'/mentions-legales').'"')
        ->toContain('href="'.url($prefixe.'/politique-confidentialite').'"')
        ->not->toContain('mentions-legales.html')
        ->not->toContain('politique-confidentialite.html');
})->with([
    'francais' => ['/', ''],
    'anglais' => ['/en', '/en'],
]);

/* ------------------------------------------------ changement de langue */

it('change de langue sans quitter la page legale', function (string $depart, string $arrivee) {
    publierLesMentions();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$this->get($depart)->getContent());
    $bascule = (new DOMXPath($document))->query('//a[contains(@class, "lang-toggle")]/@href')->item(0)?->nodeValue;

    expect($bascule)->toBe(url($arrivee));
})->with([
    ['/mentions-legales', '/en/mentions-legales'],
    ['/en/mentions-legales', '/mentions-legales'],
    ['/politique-confidentialite', '/en/politique-confidentialite'],
    ['/en/politique-confidentialite', '/politique-confidentialite'],
]);

/* ------------------------------------------------ page non publiee */

/**
 * Tant que la version en base n'est pas publiee, l'adresse sert la page
 * d'origine, lue a sa source dans maquettes-frontoffice/ — elle n'est plus
 * dans public/. C'est une page d'attente : elle ne doit pas etre indexee.
 */
it('sert la page d origine tant que la version en base n est pas publiee', function (string $adresse) {
    // Les mentions sont en brouillon depuis la migration.
    $reponse = $this->get($adresse)->assertOk()->assertHeader('X-Robots-Tag', 'noindex');

    expect($reponse->getContent())->not->toContain('page-statique')
        ->and(strlen($reponse->getContent()))->toBeGreaterThan(2000);
})->with(['/mentions-legales', '/en/mentions-legales']);

it('ne pose pas noindex sur une page publiee', function () {
    publierLesMentions();

    $this->get('/mentions-legales')->assertHeaderMissing('X-Robots-Tag');
});

/* ------------------------------------------------ administration */

it('previent l administrateur qu un brouillon n est pas en ligne', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    $ecran = Livewire::actingAs($admin)->test(PagesStatiques::class)->set('page', 'mentions-legales');

    $ecran->assertSee("Cette page n'est pas publiée");

    $ecran->set('page', 'politique-confidentialite')->assertDontSee("Cette page n'est pas publiée");
});

/*
 * La page d'attente est ecrite pour le site statique : chemins relatifs,
 * langue lue dans la memoire du navigateur, canonique vers l'ancienne adresse.
 * Servie par Laravel aux deux adresses, elle doit suivre l'URL.
 */
it('sert la page d attente dans la langue de son adresse', function (string $adresse, string $langue) {
    $html = $this->get($adresse)->assertOk()->getContent();

    expect($html)->toContain('<html lang="'.$langue.'">')
        // main.js lit cette cle au chargement pour traduire la page : elle est
        // posee avant lui, avec le nonce de la requete.
        ->toMatch('/<script nonce="[^"]+">\(function\(\)\{try\{localStorage\.setItem\(\'sci4k-lang\', \''.$langue.'\'\);/')
        ->not->toContain('rel="canonical"');
})->with([
    'francais' => ['/mentions-legales', 'fr'],
    'anglais' => ['/en/mentions-legales', 'en'],
]);

it('charge la page d attente par des chemins absolus', function (string $adresse) {
    $html = $this->get($adresse)->getContent();

    // Sous /en/, un chemin relatif menait a /en/assets/… : page sans style.
    expect($html)->toContain('src="/assets/main.js"')
        ->toContain('href="/assets/style.css"')
        ->not->toMatch('/(href|src)="(assets|images)\//');
})->with(['/mentions-legales', '/en/mentions-legales']);

it('mene les liens de la page d attente vers la langue courante', function () {
    $html = $this->get('/en/mentions-legales')->getContent();

    expect($html)->toContain('href="'.url('/en/contact').'"')
        ->toContain('href="'.url('/en/politique-confidentialite').'"')
        ->not->toMatch('/href="[^"]*\.html"/');
});

/*
 * La description vient du texte de la page. Elle reprenait son premier titre
 * — « 1. Qui sommes-nous » — et ses sauts de ligne, dans ce que Google affiche
 * sous le lien.
 */
it('tire une description propre du texte de la page', function (string $adresse) {
    $description = teteDeLaPage($this->get($adresse)->getContent())['description'];

    expect($description)->not->toBeEmpty()
        ->not->toMatch('/\s{2,}|\n/')
        ->not->toMatch('/^\s*\d+\./');
})->with(['/politique-confidentialite', '/en/politique-confidentialite']);
