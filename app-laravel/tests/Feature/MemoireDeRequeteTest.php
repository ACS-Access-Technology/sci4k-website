<?php

use App\Livewire\Admin\Menus;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use Illuminate\Support\Facades\DB;

/**
 * Les lectures repetees d'une page publique, et la memoire qui les evite.
 *
 * Mesure avant correction, avec le cache « database » de la production :
 * 82 a 93 requetes SQL par page publique, dont 36 Schema::hasTable() et 32 a
 * 39 lectures de la table `cache` — la meme verification et le meme bloc de
 * reglages, relus pour chaque partiel. Apres : 12 a 24, pour un HTML
 * identique sur les vingt pages mesurees (FR et EN).
 *
 * La memoire (once()) vaut pour UNE requete : le middleware
 * OublieLaMemoireDeLaRequete la vide au debut de la suivante, et un reglage
 * enregistre la vide aussitot. Les derniers tests le verifient — une memoire
 * qui survit a sa requete sert une valeur perimee, defaut deja rencontre sur
 * ce projet avec une variable `static`.
 */
beforeEach(function () {
    // Le pilote de la production : chaque lecture du cache y est une requete.
    config(['cache.default' => 'database']);
    $this->seed();
    Parametre::poser('nom_du_site', 'SCI4K');
});

/** Les requetes SQL d'un appel, par categorie. */
function requetesDe(callable $appel): array
{
    $requetes = [];
    DB::listen(function ($requete) use (&$requetes) {
        $requetes[] = $requete->sql;
    });

    $appel();

    return [
        'total' => count($requetes),
        'hasTable' => count(array_filter($requetes, fn ($sql) => str_contains($sql, 'sqlite_master') || str_contains($sql, 'information_schema'))),
        'cache' => count(array_filter($requetes, fn ($sql) => preg_match('/from ["`]cache["`]/', $sql) === 1)),
    ];
}

it('ne repete plus les memes lectures sur une page publique', function (string $adresse) {
    $this->get($adresse); // le cache des reglages se remplit

    $mesure = requetesDe(fn () => $this->get($adresse)->assertOk());

    // Une verification par table, une lecture du bloc de reglages.
    expect($mesure['hasTable'])->toBeLessThanOrEqual(3)
        ->and($mesure['cache'])->toBeLessThanOrEqual(1)
        ->and($mesure['total'])->toBeLessThan(30);
})->with(['/', '/services', '/faq', '/contact', '/en', '/en/services']);

/* ------------------------------------------------ fraicheur */

it('sert un reglage enregistre des la requete suivante', function () {
    $this->get('/')->assertSee('SCI4K');

    Parametre::poser('nom_du_site', 'Agence Renommée');

    $this->get('/')->assertSee('Agence Renommée');
});

it('relit un reglage enregistre dans la meme requete', function () {
    expect(Parametre::lire('nom_du_site'))->toBe('SCI4K');

    Parametre::poser('nom_du_site', 'Agence Renommée');

    // Sans l'oubli a l'enregistrement, once() rendrait l'ancienne valeur
    // jusqu'a la fin de la requete.
    expect(Parametre::lire('nom_du_site'))->toBe('Agence Renommée');
});

/**
 * La section de l'habillage est memorisee sans passer par le cache : ecrite
 * sans evenement de modele, elle ne peut etre rafraichie QUE par l'oubli de
 * debut de requete. C'est donc lui que ce test eprouve.
 */
it('ne garde pas la memoire d une requete a la suivante', function () {
    ReglageDeSection::updateOrCreate(['slug' => Menus::SECTION], ['options' => ['libelle_fermer_fr' => 'Fermer']]);
    $this->get('/')->assertOk();

    DB::table('reglages_de_section')->where('slug', Menus::SECTION)
        ->update(['options' => json_encode(['libelle_fermer_fr' => 'Refermer la fenêtre'])]);

    $this->get('/')->assertSee('Refermer la fenêtre');
});
