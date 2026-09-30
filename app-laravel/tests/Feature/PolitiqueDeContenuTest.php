<?php

use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Parametre;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * La politique de securite du contenu (CSP), et ce qu'elle doit laisser
 * passer. Voir App\Support\PolitiqueDeContenu.
 *
 * Le test qui compte le plus est celui des scripts en ligne : une balise
 * <script> ou <style> ajoutee a une vue sans @nonce est BLOQUEE par le
 * navigateur, sans erreur cote serveur. Aucun autre test ne le verrait.
 */
beforeEach(function () {
    $this->seed();

    $categorie = Categorie::firstOrCreate(['slug' => 'foncier'], ['nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1]);
    Article::factory()->create([
        'categorie_id' => $categorie->id,
        'statut' => 'publie',
        'slug' => 'un-article',
        'commentaires_ouverts' => true,
    ]);
    Bien::factory()->create(['slug' => 'une-villa', 'statut' => Bien::PUBLIE]);
});

/** Les directives de l'en-tete, par nom. */
function directivesDe($reponse): array
{
    $entete = (string) $reponse->headers->get('Content-Security-Policy');

    expect($entete)->not->toBe('', 'la reponse ne porte aucune politique de contenu');

    return collect(explode(';', $entete))
        ->map(fn ($morceau) => preg_split('/\s+/', trim($morceau)))
        ->mapWithKeys(fn ($mots) => [array_shift($mots) => $mots])
        ->all();
}

const PAGES_PUBLIQUES = [
    '/', '/presentation', '/services', '/biens', '/biens/une-villa', '/actualites',
    '/actualites/un-article', '/faq', '/contact', '/mentions-legales', '/politique-confidentialite',
    '/en', '/en/presentation', '/en/services', '/en/biens', '/en/biens/une-villa', '/en/actualites',
    '/en/actualites/un-article', '/en/faq', '/en/contact', '/en/mentions-legales', '/en/politique-confidentialite',
    '/adresse-qui-n-existe-pas',
];

/* ------------------------------------------------ l'en-tete */

it('pose une politique restrictive sur chaque page publique', function (string $adresse) {
    $directives = directivesDe($this->get($adresse));

    expect($directives['default-src'])->toBe(["'self'"])
        ->and($directives['object-src'])->toBe(["'none'"])
        ->and($directives['base-uri'])->toBe(["'none'"])
        ->and($directives['frame-ancestors'])->toBe(["'none'"])
        ->and($directives['form-action'])->toBe(["'self'"])
        ->and($directives['connect-src'])->toBe(["'self'"])
        ->and($directives['font-src'])->toBe(["'self'"])
        ->and($directives['img-src'])->toBe(["'self'", 'data:', 'blob:']);

    // Ni 'unsafe-inline' pour les scripts et les balises <style>, ni joker.
    foreach (['script-src', 'style-src'] as $directive) {
        expect($directives[$directive])->not->toContain("'unsafe-inline'")
            ->and(collect($directives[$directive])->filter(fn ($source) => str_starts_with($source, "'nonce-")))->toHaveCount(1);
    }

    foreach ($directives as $sources) {
        expect($sources)->not->toContain('*')->not->toContain('http:')->not->toContain('https:');
    }
})->with(PAGES_PUBLIQUES);

it('ne donne unsafe-eval qu aux pages qui chargent Livewire', function (string $adresse, bool $livewire) {
    $scripts = directivesDe($this->get($adresse))['script-src'];

    expect(in_array("'unsafe-eval'", $scripts, true))->toBe($livewire);
})->with([
    'catalogue' => ['/biens', true],
    'catalogue anglais' => ['/en/biens', true],
    'connexion' => ['/login', true],
    'accueil' => ['/', false],
    'contact' => ['/contact', false],
    'fiche' => ['/biens/une-villa', false],
    'faq' => ['/en/faq', false],
]);

it('ne transmet pas unsafe-eval d une requete a la suivante', function () {
    $this->get('/biens');

    expect(directivesDe($this->get('/'))['script-src'])->not->toContain("'unsafe-eval'");
});

it('change de nonce a chaque requete', function () {
    $premier = directivesDe($this->get('/'))['script-src'][1];
    $second = directivesDe($this->get('/'))['script-src'][1];

    expect($premier)->not->toBe($second);
});

it('n ouvre les cadres qu a la carte de la page contact', function () {
    expect(directivesDe($this->get('/contact'))['frame-src'])->toBe(['https://www.google.com'])
        ->and(directivesDe($this->get('/en/contact'))['frame-src'])->toBe(['https://www.google.com'])
        ->and(directivesDe($this->get('/faq'))['frame-src'])->toBe(["'none'"]);
});

it('laisse les attributs style, et eux seuls, en ligne', function () {
    expect(directivesDe($this->get('/'))['style-src-attr'])->toBe(["'unsafe-inline'"]);
});

/* ------------------------------------------------ les scripts en ligne */

/**
 * Chaque <script> execute et chaque <style> ecrits dans la page portent le
 * nonce de l'en-tete. Les blocs JSON-LD ne sont pas executes et n'en ont pas
 * besoin ; les scripts servis par le site (src="/...") sont admis par 'self'.
 */
it('pose le nonce sur chaque script et style en ligne', function (string $adresse) {
    // Les mentions sont en brouillon : /mentions-legales sert la page
    // d'attente, ecrite hors de Blade, dont le script recoit le nonce a part.
    $reponse = $this->get($adresse);
    $nonce = substr(directivesDe($reponse)['script-src'][1], strlen("'nonce-"), -1);

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="UTF-8">'.$reponse->getContent());
    $xpath = new DOMXPath($document);

    $enLigne = $xpath->query('//script[not(@src)][not(@type="application/ld+json")] | //style');
    $sansNonce = [];
    foreach ($enLigne as $balise) {
        if ($balise->getAttribute('nonce') !== $nonce) {
            $sansNonce[] = $balise->nodeName.' : '.mb_substr(trim($balise->textContent), 0, 60);
        }
    }

    expect($enLigne->length)->toBeGreaterThan(0)
        ->and($sansNonce)->toBe([]);

    // Et aucun script venu d'ailleurs que le site.
    foreach ($xpath->query('//script[@src]') as $script) {
        expect($script->getAttribute('src'))->toMatch('#^(/|'.preg_quote(url('/'), '#').'|assets/)#');
    }
})->with(PAGES_PUBLIQUES);

it('pose le nonce sur les scripts de l administration', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    $reponse = $this->actingAs($admin)->get('/dashboard')->assertOk();
    $directives = directivesDe($reponse);
    $nonce = substr($directives['script-src'][1], strlen("'nonce-"), -1);

    expect($directives['script-src'])->toContain("'unsafe-eval'")
        ->and(substr_count($reponse->getContent(), 'nonce="'.$nonce.'"'))->toBeGreaterThanOrEqual(3);
});

/* ------------------------------------------------ services tiers */

it('n autorise aucun service tiers tant qu aucun n est active', function () {
    $entete = $this->get('/')->headers->get('Content-Security-Policy');

    expect($entete)->not->toContain('google')->not->toContain('tawk');
});

it('ouvre Google Analytics quand l identifiant est saisi', function () {
    Parametre::poser('google_analytics', 'G-ESSAI00000');

    $directives = directivesDe($this->get('/services'));

    expect($directives['script-src'])->toContain('https://*.googletagmanager.com')
        ->and($directives['connect-src'])->toContain('https://*.google-analytics.com')
        ->and($directives['img-src'])->toContain('https://*.google-analytics.com');
});

it('ouvre le chat quand il est active', function () {
    Parametre::poser('chat_actif', '1');

    $directives = directivesDe($this->get('/faq'));

    expect($directives['script-src'])->toContain('https://embed.tawk.to')
        ->and($directives['connect-src'])->toContain('wss://*.tawk.to')
        ->and($directives['frame-src'])->toContain('https://*.tawk.to')
        // Le chat n'ouvre pas pour autant les scripts en ligne.
        ->and($directives['script-src'])->not->toContain("'unsafe-inline'");
});

/* ------------------------------------------------ observation */

it('passe en observation sans rien bloquer quand on le demande', function () {
    config(['app.csp_observation' => true]);

    $reponse = $this->get('/');

    expect($reponse->headers->has('Content-Security-Policy'))->toBeFalse()
        ->and($reponse->headers->get('Content-Security-Policy-Report-Only'))->toContain("default-src 'self'");
});
