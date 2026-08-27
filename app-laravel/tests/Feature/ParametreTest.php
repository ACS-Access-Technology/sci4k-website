<?php

use App\Models\Parametre;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * Les reglages generaux du site.
 *
 * Table cle / valeur : la base ne contraint plus les types, donc les garanties
 * qui restent — cache vide a l'ecriture, secrets chiffres — doivent etre tenues
 * par le modele, et verifiees ici.
 */

it('rend le defaut quand le reglage n existe pas', function () {
    expect(Parametre::lire('inconnu', 'defaut'))->toBe('defaut')
        ->and(Parametre::lire('inconnu'))->toBeNull();
});

it('rend le defaut quand le reglage existe mais est vide', function () {
    Parametre::poser('telephone', '', 'contact');

    // Une chaine vide n'est pas une valeur : l'editeur a vide le champ, il
    // attend le comportement par defaut, pas un telephone vide affiche.
    expect(Parametre::lire('telephone', '+225 00'))->toBe('+225 00');
});

it('relit ce qu il a ecrit', function () {
    Parametre::poser('nom_du_site', 'SCI4K', 'general');

    expect(Parametre::lire('nom_du_site'))->toBe('SCI4K');
});

it('vide le cache des qu un reglage change', function () {
    Parametre::poser('slogan', 'Premier', 'general');
    expect(Parametre::lire('slogan'))->toBe('Premier');

    Parametre::poser('slogan', 'Second', 'general');

    // Sans purge, la seconde lecture rendrait encore « Premier » : c'est le
    // defaut classique d'un cache pose sur une table editable.
    expect(Parametre::lire('slogan'))->toBe('Second');
});

it('interprete les cases a cocher', function () {
    Parametre::poser('mode_maintenance', '1', 'general');
    Parametre::poser('autoriser_indexation', '', 'seo');

    expect(Parametre::actif('mode_maintenance'))->toBeTrue()
        ->and(Parametre::actif('autoriser_indexation', true))->toBeTrue()
        ->and(Parametre::actif('jamais_pose'))->toBeFalse()
        ->and(Parametre::actif('jamais_pose', true))->toBeTrue();
});

/*
 * Le mot de passe SMTP est le seul secret de cette table. Les deux tests qui
 * suivent visent le point sensible plutot que son voisinage : ce qui compte
 * n'est pas que la lecture rende la bonne valeur, mais que la COLONNE ne la
 * contienne pas en clair.
 */
it('chiffre le mot de passe SMTP dans la colonne', function () {
    Parametre::poser('smtp_mot_de_passe', 'secret-tres-devinable', 'messagerie');

    $brut = DB::table('parametres')->where('cle', 'smtp_mot_de_passe')->value('valeur');

    expect($brut)->not->toBe('secret-tres-devinable')
        ->and($brut)->not->toContain('secret-tres-devinable')
        ->and(Parametre::lire('smtp_mot_de_passe'))->toBe('secret-tres-devinable');
});

it('ne chiffre pas les reglages ordinaires', function () {
    Parametre::poser('smtp_identifiant', 'contact@sci4k.com', 'messagerie');

    // Chiffrer ce qui n'est pas secret rendrait la base illisible pour un
    // administrateur legitime, sans rien proteger.
    expect(DB::table('parametres')->where('cle', 'smtp_identifiant')->value('valeur'))
        ->toBe('contact@sci4k.com');
});

it('rend le defaut plutot que d exploser si le secret est indechiffrable', function () {
    // Cas reel : la cle d'application a change entre l'enregistrement et la
    // lecture. Laisser remonter l'exception casserait l'ecran entier pour un
    // seul champ.
    DB::table('parametres')->insert([
        'cle' => 'smtp_mot_de_passe',
        'valeur' => 'ceci-n-est-pas-un-chiffre-valide',
        'groupe' => 'messagerie',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    Cache::forget(Parametre::CACHE);

    expect(Parametre::lire('smtp_mot_de_passe', 'rien'))->toBe('rien');
});
