<?php

use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

/*
 * LA LANGUE EST DANS L'ADRESSE
 *
 * Elle vivait en session : la MEME adresse servait deux contenus. Un moteur de
 * recherche n'a pas de session — il ne voyait donc que le francais, et tout le
 * site anglais lui etait invisible. Un lien anglais partage s'ouvrait de meme
 * en francais chez le destinataire, ce qu'aucun visiteur ne pouvait
 * comprendre.
 *
 * Le francais garde ses adresses, l'anglais prend un prefixe. Ces tests fixent
 * les quatre proprietes qui doivent tenir : les deux adresses repondent, elles
 * servent des langues differentes, elles se declarent l'une l'autre, et les
 * liens d'une page restent dans sa langue.
 */
it('sert les deux adresses de chaque page publique', function (string $chemin) {
    $this->get($chemin)->assertOk();
    $this->get('/en'.($chemin === '/' ? '' : $chemin))->assertOk();
})->with(['/', '/services', '/faq', '/presentation', '/contact', '/actualites', '/biens']);

it('sert des langues differentes sous les deux adresses', function () {
    $this->get('/services')->assertOk()->assertSee('<html lang="fr">', false);
    $this->get('/en/services')->assertOk()->assertSee('<html lang="en">', false);
});

/*
 * Sans ces balises, un moteur voit deux adresses au contenu proche sans aucun
 * moyen de savoir qu'il s'agit de la meme page en deux langues : il en choisit
 * une et ignore l'autre, ou penalise les deux pour duplication.
 */
it('declare chaque page comme la traduction de l autre', function () {
    foreach (['/services', '/en/services'] as $adresse) {
        $this->get($adresse)->assertOk()
            ->assertSee('hreflang="fr" href="'.url('/services').'"', false)
            ->assertSee('hreflang="en" href="'.url('/en/services').'"', false)
            // x-default designe la version servie a qui ne demande aucune
            // langue : le francais, marche principal de l'agence.
            ->assertSee('hreflang="x-default" href="'.url('/services').'"', false);
    }
});

it('donne a chaque page son propre canonique', function () {
    $this->get('/services')->assertOk()->assertSee('rel="canonical" href="'.url('/services').'"', false);
    $this->get('/en/services')->assertOk()->assertSee('rel="canonical" href="'.url('/en/services').'"', false);
});

/*
 * Le point qui aurait ete le plus couteux a manquer : un seul lien oublie
 * ramenait le visiteur anglophone au francais au milieu de sa navigation, sans
 * qu'aucune erreur ne soit levee nulle part.
 */
it('garde les liens d une page anglaise en anglais', function () {
    $corps = $this->get('/en/services')->assertOk()->getContent();

    // Le pied de page renvoie vers le contact et les services : sur une page
    // anglaise, ces liens doivent porter le prefixe.
    expect($corps)->toContain(url('/en/contact'))
        ->and($corps)->toContain(url('/en/services'));
});

it('mene la bascule a la meme page dans l autre langue', function () {
    $this->get('/services')->assertOk()->assertSee(url('/en/services'), false);
    $this->get('/en/services')->assertOk()->assertSee(url('/services'), false);
});

it('sert les deux adresses d un article et d un bien', function () {
    $article = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'slug' => 'acd-securiser',
        'statut' => 'publie',
        'date_publication' => now()->subDay(),
    ]);

    $bien = Bien::factory()->create(['slug' => 'villa-cocody', 'statut' => Bien::PUBLIE]);

    $this->get('/actualites/'.$article->slug)->assertOk()->assertSee('<html lang="fr">', false);
    $this->get('/en/actualites/'.$article->slug)->assertOk()->assertSee('<html lang="en">', false);

    $this->get('/biens/'.$bien->slug)->assertOk()->assertSee('<html lang="fr">', false);
    $this->get('/en/biens/'.$bien->slug)->assertOk()->assertSee('<html lang="en">', false);
});

/*
 * Le backoffice n'a PAS de version anglaise de ses adresses, et n'en aura pas :
 * il n'est pas indexe, et prefixer cent routes d'administration n'apporterait
 * rien. Sa langue se retient en session, comme avant.
 */
it('ne traduit pas les adresses du backoffice', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    $this->actingAs($admin)->get('/langue/en');

    // L'adresse ne change pas ; seule la langue affichee change.
    $this->actingAs($admin)->get('/dashboard')->assertOk();

    expect(Route::has('en.admin.configuration'))->toBeFalse()
        ->and(Route::has('admin.configuration'))->toBeTrue();
});

it('annonce les deux langues dans le plan du site', function () {
    // Un plan qui ne listerait que le francais laisserait la moitie du site
    // hors des resultats de recherche.
    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($contenu)->toContain('<loc>'.url('/services').'</loc>')
        ->and($contenu)->toContain('<loc>'.url('/en/services').'</loc>');
});

it('n annonce dans le plan que des adresses qui repondent', function () {
    $contenu = $this->get('/sitemap.xml')->assertOk()->getContent();

    preg_match_all('/<loc>([^<]+)<\/loc>/', $contenu, $trouvees);

    expect($trouvees[1])->not->toBeEmpty();

    foreach ($trouvees[1] as $adresse) {
        $chemin = (string) parse_url($adresse, PHP_URL_PATH);

        expect($this->get($chemin === '' ? '/' : $chemin)->getStatusCode())
            ->toBe(200, "Le plan du site annonce $adresse, qui ne repond pas 200.");
    }
});
