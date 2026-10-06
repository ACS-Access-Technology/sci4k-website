<?php

use App\Livewire\Admin\PagesStatiques;
use App\Models\PageStatique;
use App\Models\User;
use App\Support\HtmlEditorial;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Le HTML des pages legales, rendu sans echappement chez chaque visiteur.
 *
 * Il n'etait filtre nulle part, et les editeurs pouvaient l'ecrire : un script
 * saisi dans le backoffice s'executait chez tout visiteur, administrateurs
 * compris. Deux defenses, chacune testee ici :
 *
 *   - le filtre a la sortie (HtmlEditorial), qui ne laisse passer que les
 *     balises editoriales ;
 *   - l'ecran reserve aux administrateurs, a la route ET dans le composant.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Enregistre un contenu depuis l'ecran, comme le ferait l'administrateur. */
function enregistrerLaPolitique(User $auteur, string $contenu): void
{
    Livewire::actingAs($auteur)
        ->test(PagesStatiques::class)
        ->set('page', 'politique-confidentialite')
        ->set('titreFr', 'Politique de confidentialité')
        ->set('contenuFr', '<div class="legal-block"><p>Texte conservé.</p></div>'.$contenu)
        ->set('publie', true)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

/* ------------------------------------------------ ce que le visiteur recoit */

it('ne rend jamais un script saisi dans le contenu', function () {
    enregistrerLaPolitique($this->admin, '<script>alert(1)</script>');

    $this->get('/politique-confidentialite')
        ->assertOk()
        ->assertSee('Texte conservé.', false)
        ->assertDontSee('alert(1)', false);
});

it('retire l image piegee et son attribut d evenement', function () {
    enregistrerLaPolitique($this->admin, '<img src=x onerror=alert(1)>');

    $corps = $this->get('/politique-confidentialite')->assertOk()->getContent();

    expect($corps)->toContain('Texte conservé.')
        ->not->toContain('onerror')
        ->not->toContain('alert(1)')
        ->not->toContain('<img src="x"');
});

/**
 * Le filtre joue a la SORTIE : un texte deja en base, qui n'est jamais passe
 * par l'ecran — saisi avant le filtre, verse par une migration — est filtre
 * lui aussi.
 */
it('filtre aussi un contenu ecrit directement en base', function () {
    PageStatique::where('slug', 'politique-confidentialite')->update([
        'contenu_fr' => '<p>Avant.</p><script>alert(1)</script><p onclick="alert(1)">Après.</p>',
        'publie' => true,
    ]);

    $corps = $this->get('/politique-confidentialite')->assertOk()->getContent();

    expect($corps)->toContain('Avant.')->toContain('Après.')
        ->not->toContain('alert(1)')
        ->not->toContain('onclick');
});

it('filtre la version anglaise comme la francaise', function () {
    PageStatique::where('slug', 'politique-confidentialite')->update([
        'contenu_en' => '<p>English.</p><img src=x onerror=alert(1)>',
        'publie' => true,
    ]);

    $corps = $this->get('/en/politique-confidentialite')->assertOk()->getContent();

    expect($corps)->toContain('English.')->not->toContain('onerror');
});

it('retire les balises et les adresses dangereuses', function (string $piege, string $interdit) {
    expect(HtmlEditorial::nettoyer('<p>Texte.</p>'.$piege))
        ->toContain('<p>Texte.</p>')
        ->not->toContain($interdit);
})->with([
    'iframe' => ['<iframe src="https://exemple.test"></iframe>', '<iframe'],
    'object' => ['<object data="x.swf"></object>', '<object'],
    'embed' => ['<embed src="x.swf">', '<embed'],
    'formulaire' => ['<form action="https://exemple.test"><input name="mdp"></form>', '<form'],
    'style' => ['<style>body{display:none}</style>', 'display:none'],
    'svg' => ['<svg onload="alert(1)"></svg>', 'onload'],
    'lien javascript' => ['<a href="javascript:alert(1)">clic</a>', 'javascript:'],
    'lien javascript deguise' => ['<a href="JaVaScRiPt:alert(1)">clic</a>', 'alert(1)'],
    'attribut style' => ['<p style="position:fixed">x</p>', 'position:fixed'],
]);

/* ------------------------------------------------ le HTML legitime survit */

it('garde le HTML editorial legitime', function () {
    $html = <<<'HTML'
<div class="legal-block">
  <h2>1. Éditeur</h2>
  <p>Texte <strong>gras</strong>, <em>souligné</em>,<br>sur deux lignes.</p>
  <h3>Sous-titre</h3>
  <h4>Détail</h4>
  <ul><li>Un</li><li>Deux</li></ul>
  <ol><li>Premier</li></ol>
  <p>RCCM : <span class="legal-placeholder">[à compléter]</span></p>
  <p><a href="mailto:contact@sci4k.com">Écrire</a>, <a href="/politique-confidentialite">lire</a>,
     <a href="https://www.exemple.ci/page">visiter</a>, <a href="tel:+2250700000000">appeler</a>.</p>
</div>
HTML;

    $propre = HtmlEditorial::nettoyer($html);

    foreach (['<div class="legal-block">', '<h2>', '<strong>gras</strong>', '<em>', '<br />', '<h3>', '<h4>',
        '<ul><li>Un</li>', '<ol>', '<span class="legal-placeholder">', 'href="mailto:contact',
        'href="/politique-confidentialite"', 'href="https://www.exemple.ci/page"', 'href="tel:'] as $attendu) {
        expect($propre)->toContain($attendu);
    }
});

/**
 * Le contenu reellement en base — la politique de confidentialite versee par
 * migration, publiee en ligne — doit sortir du filtre avec toute sa structure.
 * Le filtre reencode le texte (&#039;) ; le visiteur lit la meme chose.
 */
it('laisse intacte la structure de la politique de confidentialite publiee', function () {
    $source = PageStatique::where('slug', 'politique-confidentialite')->value('contenu_fr');
    $propre = HtmlEditorial::nettoyer($source);

    foreach (['<div class="legal-block">', '<h2>', '<p>', '<a ', '<strong>'] as $balise) {
        expect(substr_count($propre, $balise))->toBe(substr_count($source, $balise), "Balise perdue : $balise");
    }

    $texte = fn (string $html) => preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    expect($texte($propre))->toBe($texte($source));
});

it('ne tronque pas une page longue', function () {
    $long = str_repeat('<p>'.str_repeat('mot ', 40).'</p>', 250).'<p>FIN-DE-PAGE</p>';
    expect(strlen($long))->toBeGreaterThan(40000);

    expect(HtmlEditorial::nettoyer($long))->toContain('FIN-DE-PAGE');
});

/**
 * La description de la page est tiree du contenu filtre, qui porte des
 * entites. Non decodees, l'echappement de Blade les aurait doublees : la
 * balise aurait affiche « n&#039;est » dans les resultats de recherche.
 */
it('ne double pas l echappement dans la description de la page', function () {
    PageStatique::where('slug', 'politique-confidentialite')->update([
        'contenu_fr' => "<p>Ce site n'est pas un test. Écrire à contact@sci4k.com.</p>",
        'publie' => true,
    ]);

    $corps = $this->get('/politique-confidentialite')->assertOk()->getContent();

    expect($corps)->not->toContain('&amp;#')
        ->toMatch('/<meta name="description" content="Ce site n&#0?39;est pas un test\./');
});

/* ------------------------------------------------ qui peut ecrire */

it('reserve l ecran aux administrateurs', function () {
    $editeur = User::factory()->create();
    $editeur->assignRole('editeur');

    $this->actingAs($editeur)->get('/admin/pages-editables')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin/pages-editables')->assertOk();
});

/**
 * La route ne suffit pas : Livewire ne rejoue pas son middleware sur
 * /livewire/update. Le composant doit refuser de lui-meme.
 */
it('refuse l enregistrement a un editeur meme par le composant', function () {
    $editeur = User::factory()->create();
    $editeur->assignRole('editeur');
    $avant = PageStatique::where('slug', 'politique-confidentialite')->value('contenu_fr');

    Livewire::actingAs($editeur)->test(PagesStatiques::class)->assertForbidden();

    expect(PageStatique::where('slug', 'politique-confidentialite')->value('contenu_fr'))->toBe($avant);
});
