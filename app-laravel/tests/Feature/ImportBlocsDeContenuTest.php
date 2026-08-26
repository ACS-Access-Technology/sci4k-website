<?php

use App\Models\ChiffreCle;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Support\Facades\Artisan;

/*
 * Reprise des neuf familles de blocs.
 *
 * Memes controles qu'aux deux imports precedents : le compte attendu, aucun
 * texte vide, aucun texte corrompu, et rejouabilite sans doublon. Le controle
 * de corruption se fait EN PHP et non en SQL — la collation du projet est
 * insensible aux accents, un LIKE '%Ã%' y signalerait des faux positifs.
 */
beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'BlocsDeContenuSeeder', '--force' => true]);
});

it('reprend le compte attendu de chaque famille', function () {
    expect(ReglageDeSection::count())->toBe(23);
    expect(Temoignage::count())->toBe(3);
    expect(Partenaire::count())->toBe(7);
    expect(MembreEquipe::count())->toBe(4);
    expect(Encart::count())->toBe(1);
    expect(ImageDeFond::count())->toBe(20);
    expect(Valeur::count())->toBe(4);
    expect(ChiffreCle::count())->toBe(3);
    expect(EtapeProcessus::count())->toBe(4);
});

it('n importe aucun texte anglais manquant', function () {
    // L'anglais du site est humain : un champ vide signalerait que le
    // dictionnaire n'a pas ete lu, pas que la traduction n'existe pas.
    foreach (Temoignage::all() as $t) {
        expect($t->citation_en)->not->toBe('');
        expect($t->role_en)->not->toBe('');
    }

    foreach (MembreEquipe::all() as $m) {
        expect($m->fonction_en)->not->toBe('');
        expect($m->biographie_en)->not->toBe('');
    }

    foreach (Valeur::all() as $v) {
        expect($v->titre_en)->not->toBe('');
        expect($v->texte_en)->not->toBe('');
    }

    foreach (EtapeProcessus::all() as $e) {
        expect($e->titre_en)->not->toBe('');
        expect($e->texte_en)->not->toBe('');
    }

    foreach (ChiffreCle::all() as $c) {
        expect($c->intitule_en)->not->toBe('');
    }
});

it('n importe aucun texte corrompu', function () {
    // Signature d'un decodage par unicode_escape : « é » devient « Ã© ». Le
    // piege avait corrompu les douze articles du lot 1 sans qu'aucun test ne
    // le voie.
    $suspects = ['Ã', 'â€', 'Â«', 'Ã©', 'ï»¿'];

    $textes = collect()
        ->concat(Temoignage::all()->flatMap(fn ($t) => [$t->citation_fr, $t->role_fr, $t->auteur]))
        ->concat(MembreEquipe::all()->flatMap(fn ($m) => [$m->nom, $m->fonction_fr, $m->biographie_fr]))
        ->concat(Valeur::all()->flatMap(fn ($v) => [$v->titre_fr, $v->texte_fr]))
        ->concat(Partenaire::all()->pluck('nom'))
        ->concat(ReglageDeSection::all()->flatMap(fn ($r) => [$r->titre_fr, $r->chapo_fr]))
        ->filter();

    foreach ($textes as $texte) {
        foreach ($suspects as $motif) {
            expect(str_contains($texte, $motif))->toBeFalse();
        }
    }
});

it('conserve les accents francais', function () {
    // Le controle inverse : verifier qu'il RESTE des accents. Un texte vide de
    // tout accent passerait le controle de corruption sans rien prouver.
    expect(Valeur::where('titre_fr', 'like', '%Sécurité%')->exists())->toBeTrue();
    expect(Partenaire::where('nom', 'CNPS Côte d\'Ivoire')->exists())->toBeTrue();
});

it('accepte un partenaire sans site', function () {
    // Deux des sept n'en ont pas : leur carte n'est pas un lien sur le site.
    $sansSite = Partenaire::whereNull('site')->orWhere('site', '')->get();

    expect($sansSite)->toHaveCount(2);
    expect($sansSite->every(fn ($p) => ! $p->aUnSite()))->toBeTrue();
});

it('est rejouable sans creer de doublon', function () {
    Artisan::call('db:seed', ['--class' => 'BlocsDeContenuSeeder', '--force' => true]);

    expect(ReglageDeSection::count())->toBe(23);
    expect(Partenaire::count())->toBe(7);
    expect(ImageDeFond::count())->toBe(20);
});

it('ne reecrit pas l ordre ni la visibilite en rejouant', function () {
    // Un `db:seed` de routine ne doit pas defaire un glisser-deposer ni
    // reafficher ce que l'editeur a masque — defaut trouve au lot 2a.
    $temoignage = Temoignage::ordonnees()->first();
    $temoignage->update(['ordre' => 99, 'visible' => false]);

    Artisan::call('db:seed', ['--class' => 'BlocsDeContenuSeeder', '--force' => true]);

    $apres = $temoignage->fresh();

    expect($apres->ordre)->toBe(99);
    expect($apres->visible)->toBeFalse();
});

it('corrige en revanche un texte modifie hors administration', function () {
    // La contrepartie : le contenu editorial du site reste la reference tant
    // qu'on rejoue l'import volontairement.
    $valeur = Valeur::first();
    $valeur->update(['titre_fr' => 'Texte casse']);

    Artisan::call('db:seed', ['--class' => 'BlocsDeContenuSeeder', '--force' => true]);

    expect(Valeur::find($valeur->id)->titre_fr)->not->toBe('Texte casse');
});
