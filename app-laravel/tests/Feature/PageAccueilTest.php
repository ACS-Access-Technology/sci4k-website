<?php

use App\Models\Article;
use App\Models\Categorie;
use App\Models\ChiffreCle;
use App\Models\Encart;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Temoignage;

/*
 * Page d'accueil, portee depuis frontoffice/index.html.
 *
 * Sept sections, sept origines. Ces tests verifient que chacune suit bien sa
 * table, que les sections vides disparaissent plutot que d'afficher un titre
 * suivi de rien, et que le titre du bandeau ne laisse jamais passer de
 * balisage.
 */
beforeEach(function () {
    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);
});

it('sert la racine', function () {
    $this->get('/')->assertOk();
});

it('redirige l ancienne adresse de l accueil', function () {
    $this->get('/index.html')->assertRedirect('/');
});

/* ----------------------------------------------------------- bandeau */

it('affiche le titre du bandeau sur deux lignes', function () {
    ReglageDeSection::factory()->create([
        'slug' => 'home.hero',
        'titre_fr' => "Votre propriété,\nnotre priorité.",
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->toContain('Votre propriété,');
    expect($corps)->toContain('<em>notre priorité.</em>');
});

it('convertit le balisage herite d un import ancien', function () {
    // Les premiers imports gardaient le <br> et le <em> du site statique. Sans
    // conversion, la page afficherait « &lt;em&gt; » en clair au visiteur.
    ReglageDeSection::factory()->create([
        'slug' => 'home.hero',
        'titre_fr' => 'Votre propriété,<br><em>notre priorité.</em>',
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->not->toContain('&lt;em&gt;');
    expect($corps)->toContain('<em>notre priorité.</em>');
});

it('ne laisse jamais passer de balisage saisi dans le titre', function () {
    // Un champ que l'administration ecrit ne doit pas pouvoir injecter de
    // balisage dans la page. Le texte reste, les balises partent.
    ReglageDeSection::factory()->create([
        'slug' => 'home.hero',
        'titre_fr' => 'Notre titre<script>alert(1)</script>',
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    // L'assertion porte sur le TITRE et non sur la page : celle-ci contient
    // des scripts legitimes — le theme pose avant premier rendu, main.js.
    preg_match('/<h1 class="reveal".*?<\/h1>/s', $corps, $titre);

    expect($titre)->not->toBeEmpty();
    expect($titre[0])->not->toContain('<script');
    expect($titre[0])->toContain('Notre titre');
});

it('affiche les compteurs avec leur suffixe', function () {
    ChiffreCle::factory()->create([
        'ordre' => 1, 'visible' => true, 'valeur' => 96, 'suffixe' => ' %',
        'intitule_fr' => 'clients satisfaits',
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    // Le suffixe est HORS du compteur : main.js anime data-target jusqu'a la
    // valeur, et le suffixe s'affiche a cote sans defiler avec elle.
    expect($corps)->toContain('data-target="96"');
    expect($corps)->toContain('clients satisfaits');
});

it('masque un compteur retire du site', function () {
    ChiffreCle::factory()->create(['ordre' => 1, 'visible' => true, 'intitule_fr' => 'Compteur affiché']);
    ChiffreCle::factory()->create(['ordre' => 2, 'visible' => false, 'intitule_fr' => 'Compteur masqué']);

    $reponse = $this->get('/')->assertOk();

    $reponse->assertSee('Compteur affiché');
    $reponse->assertDontSee('Compteur masqué');
});

/* ---------------------------------------------------------- sections */

it('affiche les services visibles et leur panneau de modale', function () {
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'foncier',
        'nom_fr' => 'Foncier', 'visible' => true, 'ordre' => 1,
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->toContain('data-svc="foncier"');
    expect($corps)->toContain('id="svcPanel-foncier"');
});

it('affiche les trois derniers articles publies', function () {
    foreach (range(1, 5) as $rang) {
        Article::factory()->create([
            'categorie_id' => $this->categorie->id,
            'slug' => 'article-'.$rang,
            'titre_fr' => 'Article '.$rang,
            'statut' => 'publie',
            'date_publication' => now()->subDays(10 - $rang),
        ]);
    }

    $reponse = $this->get('/')->assertOk();

    // Les plus recents, donc les rangs 5, 4 et 3.
    $reponse->assertSee('Article 5');
    $reponse->assertSee('Article 4');
    $reponse->assertSee('Article 3');
    $reponse->assertDontSee('Article 1');
});

it('n annonce pas un brouillon sur l accueil', function () {
    Article::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'pas-pret',
        'titre_fr' => 'Article en brouillon', 'statut' => 'brouillon',
    ]);

    $this->get('/')->assertOk()->assertDontSee('Article en brouillon');
});

it('affiche les temoignages avec leur note', function () {
    Temoignage::factory()->create([
        'ordre' => 1, 'visible' => true, 'note' => 5,
        'auteur' => 'Mireille K.', 'initiales' => 'MK',
        'citation_fr' => 'Un vrai sérieux.',
    ]);

    $reponse = $this->get('/')->assertOk();

    $reponse->assertSee('Mireille K.');
    $reponse->assertSee('Un vrai sérieux.');
    $reponse->assertSee('★★★★★');
});

it('presente un partenaire sans site comme un bloc, pas comme un lien', function () {
    // En faire un lien vide aurait donne un element focalisable qui ne mene
    // nulle part.
    Partenaire::factory()->create([
        'ordre' => 1, 'visible' => true, 'nom' => 'Avec site', 'site' => 'https://exemple.ci',
    ]);
    Partenaire::factory()->create([
        'ordre' => 2, 'visible' => true, 'nom' => 'Sans site', 'site' => null,
    ]);

    $corps = $this->get('/')->assertOk()->getContent();

    expect($corps)->toContain('<a class="partner-logo-card" href="https://exemple.ci"');
    expect($corps)->toContain('<div class="partner-logo-card">');
});

it('affiche la banderole d appel a l action', function () {
    Encart::factory()->create([
        'slug' => 'accueil', 'ordre' => 1, 'visible' => true,
        'titre_fr' => 'Prêt à concrétiser votre projet ?',
        'libelle_bouton_fr' => 'Consulter les biens',
        'cible_bouton' => '/biens.html',
    ]);

    $reponse = $this->get('/')->assertOk();

    $reponse->assertSee('Prêt à concrétiser votre projet ?');
    $reponse->assertSee('/biens.html', false);
});

it('escamote une section vide plutot que d afficher un titre seul', function () {
    // Sans temoignage, la section entiere disparait : un titre suivi de rien
    // vaut moins qu'un blanc.
    ReglageDeSection::factory()->create([
        'slug' => 'home.testimonials', 'titre_fr' => 'Ce que disent nos clients',
    ]);

    $this->get('/')->assertOk()->assertDontSee('Ce que disent nos clients');
});

/* ------------------------------------------------------------- langue */

it('sert l accueil en anglais quand la langue est basculee', function () {
    Temoignage::factory()->create([
        'ordre' => 1, 'visible' => true,
        'citation_fr' => 'Un vrai sérieux.', 'citation_en' => 'Genuinely thorough.',
    ]);

    $this->get('/en')->assertOk()->assertSee('Genuinely thorough.');
});

it('n a plus aucun attribut data-i18n', function () {
    $corps = $this->get('/')->assertOk()->getContent();

    expect(substr_count($corps, 'data-i18n'))->toBe(0);
});

it('tire les liens de service du pied de page depuis la base', function () {
    // Constat I9 de la relecture, dernier volet : index.html annonçait les six
    // services en dur. Portee, elle herite du composer de vue.
    Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'expertise',
        'nom_fr' => 'Expertise', 'visible' => true,
    ]);

    $this->get('/')->assertOk()->assertSee('/services#expertise', false);
});
