<?php

use App\Models\Article;
use App\Models\Categorie;
use App\Models\PageStatique;
use App\Models\Visite;
use Illuminate\Support\Facades\Schema;

/*
 * Ce que le site conserve du visiteur, et ce que sa politique en dit.
 *
 * Les deux doivent coincider. Ils ne coincidaient pas : la politique de
 * confidentialite promet, en gras, « Aucune adresse IP n'est conservee », et le
 * controleur des commentaires en enregistrait une a chaque message.
 *
 * Ces tests tiennent la promesse par du code. Une colonne qui reviendrait les
 * ferait tomber, ce qui est le seul moyen fiable de ne pas redevenir menteur
 * six mois plus tard.
 */
it('ne garde aucune adresse IP', function () {
    expect(Schema::hasColumn('commentaires', 'adresse_ip'))->toBeFalse();

    // Aucune colonne du schema, quelle que soit la table, ne stocke une IP :
    // le controle ne vaudrait rien s'il ne regardait que la table connue.
    foreach (Schema::getTableListing() as $table) {
        $nom = str_contains($table, '.') ? substr($table, strrpos($table, '.') + 1) : $table;

        foreach (Schema::getColumnListing($nom) as $colonne) {
            expect($colonne)->not->toContain('_ip')
                ->and($colonne)->not->toBe('ip');
        }
    }
});

it("n'enregistre pas le navigateur du visiteur", function () {
    expect(Schema::hasColumn('visites', 'user_agent'))->toBeFalse();

    $this->get('/')->assertOk();

    $visite = Visite::latest('id')->first();

    // La mesure de frequentation reste : ce sont les pages vues et les
    // visiteurs distincts qui comptent, pas leur equipement.
    expect($visite)->not->toBeNull()
        ->and($visite->chemin)->toBe('/')
        ->and($visite->session_hash)->not->toBeEmpty();
});

it('depose un commentaire sans rien retenir de son auteur au-dela du formulaire', function () {
    $article = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'publie',
        'date_publication' => now()->subDay(),
        'commentaires_ouverts' => true,
    ]);

    $this->post(route('commentaires.depot', $article), [
        'auteur' => 'Awa Traoré',
        'email' => 'awa@example.com',
        'message' => 'Merci pour cet article, très clair et utile pour mon projet.',
    ])->assertRedirect();

    $commentaire = $article->commentaires()->latest('id')->first();

    expect($commentaire)->not->toBeNull()
        ->and($commentaire->getAttributes())->not->toHaveKey('adresse_ip');
});

it('annonce dans sa politique exactement ce qu elle conserve', function () {
    $politique = PageStatique::where('slug', 'politique-confidentialite')->first();

    expect($politique)->not->toBeNull()
        ->and($politique->publie)->toBeTrue();

    foreach (['contenu_fr' => "Aucune adresse IP n'est conservée", 'contenu_en' => 'No IP address is kept'] as $colonne => $promesse) {
        expect($politique->$colonne)->toContain($promesse);
    }

    // La collecte du navigateur ayant cesse, la phrase qui l'annoncait a ete
    // retiree : une politique qui declare PLUS que ce qui est collecte inquiete
    // pour rien, et finit par n'etre plus relue.
    expect($politique->contenu_fr)->not->toContain('le type de navigateur')
        ->and($politique->contenu_en)->not->toContain('the browser type');
});
