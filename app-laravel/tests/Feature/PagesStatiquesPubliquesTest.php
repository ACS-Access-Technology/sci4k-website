<?php

use App\Models\PageStatique;

/**
 * Les trois pages editables servaient une coquille vide.
 *
 * La migration a cree leurs lignes avec un contenu vide et publie => true,
 * puis les routes ont ete branchees dessus sans que le contenu des pages HTML
 * ne soit transfere. La ligne existant et etant publiee, firstOrFail() la
 * trouvait : /contact rendait un titre, puis directement le pied de page.
 */
it('sert la page HTML d origine tant que le contenu est vide', function (string $slug) {
    expect(PageStatique::where('slug', $slug)->value('contenu_fr'))->toBe('');

    $reponse = $this->get('/'.$slug);

    $reponse->assertOk();
    // La page d'origine, et non la coquille : le gabarit public.page-statique
    // n'est alors pas rendu du tout.
    $reponse->assertDontSee('page-statique');
    expect(strlen($reponse->getContent()))
        ->toBeGreaterThan(2000, 'La page servie doit etre la page HTML complete, pas une coquille.');
})->with(['contact', 'mentions-legales', 'politique-confidentialite']);

it('sert le formulaire de contact et la carte', function () {
    $reponse = $this->get('/contact');

    $reponse->assertOk();
    $reponse->assertSee('id="contactForm"', false);
    $reponse->assertSee('<iframe', false);
});

it('sert le contenu de la base des qu il est saisi', function () {
    PageStatique::where('slug', 'mentions-legales')->update([
        'contenu_fr' => 'Editeur du site : SCI4K, Societe Civile Immobiliere.',
    ]);

    $reponse = $this->get('/mentions-legales');

    $reponse->assertOk();
    $reponse->assertSee('Editeur du site : SCI4K', false);
});

/**
 * Un contenu fait d'espaces n'est pas un contenu : il rendrait une page
 * blanche la ou la page d'origine existe encore.
 */
it('traite un contenu fait d espaces comme un contenu vide', function () {
    PageStatique::where('slug', 'contact')->update(['contenu_fr' => "  \n  "]);

    $this->get('/contact')
        ->assertOk()
        ->assertSee('id="contactForm"', false);
});

it('refuse un slug hors de la liste', function () {
    $this->get('/contact-inexistant')->assertNotFound();
});

/**
 * Une page depubliee ne doit pas rendre la coquille non plus : elle retombe
 * sur la page d'origine, qui existe toujours dans public/.
 */
it('retombe aussi quand la page est depubliee', function () {
    PageStatique::where('slug', 'contact')->update([
        'contenu_fr' => 'Un texte quelconque.',
        'publie' => false,
    ]);

    $this->get('/contact')
        ->assertOk()
        ->assertSee('id="contactForm"', false);
});
