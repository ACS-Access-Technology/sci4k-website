<?php

use App\Models\PageStatique;

/**
 * Les deux pages legales, et le repli qui les protege.
 *
 * Historique : la migration qui a cree ces lignes les a posees avec un contenu
 * VIDE et publie => true, puis les routes ont ete branchees dessus sans que le
 * texte des pages HTML ne soit transfere. La ligne existant et etant publiee,
 * firstOrFail() la trouvait et rendait une coquille — un titre, puis
 * directement le pied de page. Le repli sur la page d'origine a corrige cela ;
 * le contenu est desormais en base, mais le repli reste, et ces controles
 * verifient les deux.
 */
it('retombe sur la page HTML d origine quand le contenu est vide', function (string $slug) {
    PageStatique::where('slug', $slug)->update(['contenu_fr' => '', 'contenu_en' => '']);

    $reponse = $this->get('/'.$slug);

    $reponse->assertOk();
    // La page d'origine, et non la coquille : le gabarit public.page-statique
    // n'est alors pas rendu du tout.
    $reponse->assertDontSee('page-statique');
    expect(strlen($reponse->getContent()))
        ->toBeGreaterThan(2000, 'La page servie doit etre la page HTML complete, pas une coquille.');
})->with(['mentions-legales', 'politique-confidentialite']);

/**
 * Un contenu fait d'espaces n'est pas un contenu : il rendrait une page
 * blanche la ou la page d'origine existe encore.
 */
it('traite un contenu fait d espaces comme un contenu vide', function () {
    PageStatique::where('slug', 'politique-confidentialite')->update(['contenu_fr' => "  \n  "]);

    $reponse = $this->get('/politique-confidentialite');

    $reponse->assertOk();
    $reponse->assertDontSee('page-statique');
});

it('refuse un slug hors de la liste', function () {
    $this->get('/contact-inexistant')->assertNotFound();
});

/* ------------------------------------------------------------------ */
/* Contenu verse par la migration */
/* ------------------------------------------------------------------ */

/**
 * La politique de confidentialite est PUBLIEE : ses corrections sont
 * factuelles et ne laissent aucun trou.
 */
it('sert la politique de confidentialite depuis la base', function () {
    $reponse = $this->get('/politique-confidentialite');

    $reponse->assertOk();
    $reponse->assertSee('page-statique', false);
    $reponse->assertSee('Cookies et stockage local', false);
});

/**
 * Trois affirmations de la page d'origine sont devenues fausses depuis que le
 * site est servi par Laravel, et la nouvelle version les corrige.
 */
it('corrige les affirmations devenues fausses sur les cookies', function () {
    $reponse = $this->get('/politique-confidentialite');

    // La page d'origine affirmait n'employer QUE le stockage local. Un cookie
    // de session est pose depuis que les pages traversent la session Laravel.
    $reponse->assertDontSee("Ce site n'utilise pas de cookies", false);
    $reponse->assertSee('cookie de session', false);

    // La mesure de frequentation n'etait pas declaree du tout.
    $reponse->assertSee('fréquentation', false);
    $reponse->assertSee("Aucune adresse IP n'est conservée", false);
});

/**
 * Les mentions legales restent NON PUBLIEES : elles reclament des donnees que
 * seul le client detient. Le visiteur voit donc toujours la page d'origine, et
 * aucun « [a fournir] » ne fuit sur le site.
 */
it('ne publie pas les mentions legales tant que les blancs subsistent', function () {
    expect(PageStatique::where('slug', 'mentions-legales')->value('publie'))->toBeFalse();
    expect(PageStatique::where('slug', 'mentions-legales')->value('contenu_fr'))->toContain('[à fournir');

    $reponse = $this->get('/mentions-legales');

    $reponse->assertOk();
    $reponse->assertDontSee('page-statique');
    $reponse->assertDontSee('[à fournir', false);
});

/** Une fois les blancs remplis, il suffit de cocher « publiée ». */
it('sert les mentions legales des qu elles sont publiees', function () {
    PageStatique::where('slug', 'mentions-legales')->update([
        'contenu_fr' => '<div class="legal-block"><h2>1. Éditeur du site</h2><p>SCI4K, RCCM CI-ABJ-0000.</p></div>',
        'publie' => true,
    ]);

    $reponse = $this->get('/mentions-legales');

    $reponse->assertOk();
    $reponse->assertSee('page-statique', false);
    $reponse->assertSee('RCCM CI-ABJ-0000', false);
});

/**
 * Le gabarit employait « prose », classe de Tailwind Typography absente de la
 * feuille du site : le contenu sortait sans aucune mise en forme. Il reprend
 * desormais les classes que les pages d'origine utilisent deja.
 */
it('habille le contenu avec les classes du site', function () {
    $reponse = $this->get('/politique-confidentialite');

    $reponse->assertSee('legal-hero', false);
    $reponse->assertSee('legal-section', false);
    $reponse->assertSee('legal-block', false);
    $reponse->assertDontSee('class="wrap prose', false);
});
