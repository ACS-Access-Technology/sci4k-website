<?php

use App\Models\MembreEquipe;
use App\Models\ReglageDeSection;
use App\Models\Valeur;

/*
 * Page de presentation, portee depuis frontoffice/presentation.html.
 *
 * Le balisage est repris tel quel ; seuls les textes changent de source. Ces
 * tests verifient que la page suit bien la base — et qu'elle reste complete
 * quand la base ne dit rien, la vue se repliant alors sur le texte d'origine.
 */
beforeEach(function () {
    $this->enteteValeurs = ReglageDeSection::factory()->create([
        'slug' => 'about.values',
        'titre_fr' => 'Les engagements de SCI4K', 'titre_en' => "SCI4K's commitments",
        'etiquette_fr' => 'Nos Piliers', 'etiquette_en' => 'Our Pillars',
        'chapo_fr' => 'Quatre principes.', 'chapo_en' => 'Four principles.',
    ]);
});

it('sert la page', function () {
    $this->get('/presentation')->assertOk();
});

it('redirige l ancienne adresse', function () {
    $this->get('/presentation.html')->assertRedirect('/presentation');
});

it('affiche les valeurs de la base, dans leur ordre', function () {
    // Intitules choisis pour n'apparaitre nulle part ailleurs : « Transparence »
    // figure aussi dans le titre de banniere, et la mesure aurait porte sur
    // cette occurrence-la.
    Valeur::factory()->create(['ordre' => 2, 'titre_fr' => 'Deuxième pilier', 'visible' => true]);
    Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Premier pilier', 'visible' => true]);

    $corps = $this->get('/presentation')->assertOk()->getContent();

    expect(strpos($corps, 'Premier pilier'))->toBeLessThan(strpos($corps, 'Deuxième pilier'));
});

it('numerote les valeurs selon leur rang, pas leur identifiant', function () {
    // Reordonner doit renumeroter la grille, sinon la page montrerait
    // « 01, 03, 02 » apres un glisser-deposer.
    Valeur::factory()->create(['ordre' => 5, 'titre_fr' => 'Première', 'visible' => true]);
    Valeur::factory()->create(['ordre' => 9, 'titre_fr' => 'Seconde', 'visible' => true]);

    $corps = $this->get('/presentation')->assertOk()->getContent();

    expect($corps)->toContain('<div class="value-num">01</div>');
    expect($corps)->toContain('<div class="value-num">02</div>');
    expect($corps)->not->toContain('<div class="value-num">05</div>');
});

it('retire du site une valeur masquee', function () {
    Valeur::factory()->create(['ordre' => 1, 'titre_fr' => 'Valeur affichée', 'visible' => true]);
    Valeur::factory()->create(['ordre' => 2, 'titre_fr' => 'Valeur masquée', 'visible' => false]);

    $reponse = $this->get('/presentation')->assertOk();

    $reponse->assertSee('Valeur affichée');
    $reponse->assertDontSee('Valeur masquée');
});

it('affiche les membres de l equipe avec leur fonction', function () {
    MembreEquipe::factory()->create([
        'ordre' => 1, 'visible' => true,
        'nom' => 'M. Jean-Philippe Yao',
        'fonction_fr' => 'Directeur Général & Fondateur',
        'etiquette_fr' => 'Direction',
    ]);

    $reponse = $this->get('/presentation')->assertOk();

    $reponse->assertSee('M. Jean-Philippe Yao');
    $reponse->assertSee('Directeur Général &amp; Fondateur', false);
    $reponse->assertSee('Direction');
});

it('affiche la photo d un membre quand il en a une', function () {
    MembreEquipe::factory()->create([
        'ordre' => 1, 'visible' => true, 'nom' => 'Avec photo',
        'photo' => 'storage/equipe/portrait.jpg',
    ]);

    $this->get('/presentation')->assertOk()->assertSee('storage/equipe/portrait.jpg', false);
});

it('retombe sur la silhouette quand le membre n a pas de photo', function () {
    // Une vignette vide serait plus visible qu'un pictogramme neutre.
    MembreEquipe::factory()->create(['ordre' => 1, 'visible' => true, 'photo' => null]);

    $corps = $this->get('/presentation')->assertOk()->getContent();

    expect($corps)->toContain('<circle cx="12" cy="8" r="4"/>');
});

it('prend l en-tete de section dans la base', function () {
    // La section entiere disparait sans valeur : un titre suivi de rien vaut
    // moins qu'un blanc.
    Valeur::factory()->create(['ordre' => 1, 'visible' => true]);

    $this->get('/presentation')->assertOk()
        ->assertSee('Les engagements de SCI4K')
        ->assertSee('Nos Piliers');
});

it('se replie sur le texte d origine quand la base ne dit rien', function () {
    // La page doit rester complete meme avant que l'import ne soit rejoue.
    $this->enteteValeurs->delete();

    Valeur::factory()->create(['ordre' => 1, 'visible' => true]);

    $this->get('/presentation')->assertOk()->assertSee('Les engagements de SCI4K');
});

it('decoupe le chapo en paragraphes', function () {
    // Deux sections portent leur prose dans le chapo, paragraphes separes par
    // une ligne vide — meme convention que le contenu d'un article.
    ReglageDeSection::factory()->create([
        'slug' => 'about.overview',
        'titre_fr' => 'Présentation Générale',
        'chapo_fr' => "Premier paragraphe.\n\nSecond paragraphe.",
    ]);

    $corps = $this->get('/presentation')->assertOk()->getContent();

    expect($corps)->toContain('<p>Premier paragraphe.</p>');
    expect($corps)->toContain('<p>Second paragraphe.</p>');
});

it('sert la page en anglais quand la langue est basculee', function () {
    MembreEquipe::factory()->create([
        'ordre' => 1, 'visible' => true,
        'fonction_fr' => 'Directeur Général', 'fonction_en' => 'Chief Executive Officer',
    ]);
    Valeur::factory()->create(['ordre' => 1, 'visible' => true]);


    $reponse = $this->get('/en/presentation')->assertOk();

    $reponse->assertSee('Chief Executive Officer');
    // Sans echappement du terme cherche, l'apostrophe ne correspondrait pas :
    // Blade la rend sous la forme &#039;.
    $reponse->assertSee("SCI4K's commitments");
});

it('n a plus aucun attribut data-i18n', function () {
    // La page est rendue par le serveur : le dictionnaire client n'a plus rien
    // a y echanger, et l'y laisser ferait cohabiter deux mecanismes.
    $corps = $this->get('/presentation')->assertOk()->getContent();

    expect(substr_count($corps, 'data-i18n'))->toBe(0);
});

it('tire les liens de service du pied de page depuis la base', function () {
    // Constat I9 de la relecture : la page statique annonçait les six services
    // en dur. Portée, elle hérite du composer de vue.
    $categorie = App\Models\Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1,
    ]);

    App\Models\Service::factory()->create([
        'categorie_id' => $categorie->id, 'slug' => 'expertise',
        'nom_fr' => 'Expertise', 'visible' => true,
    ]);

    $this->get('/presentation')->assertOk()->assertSee('/services#expertise', false);
});
