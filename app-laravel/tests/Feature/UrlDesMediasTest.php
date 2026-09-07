<?php

use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/*
 * Ou pointent les adresses des fichiers televerses.
 *
 * Les modeles construisaient l'adresse avec asset(), qui prefixe toujours par
 * APP_URL. Tant que les fichiers vivent sur le serveur, cela tombe juste. Des
 * que le disque public part vers un stockage objet, l'adresse designe un
 * fichier local qui n'existe pas : le site affiche des cadres vides sans qu'une
 * seule erreur ne soit levee.
 *
 * L'adresse doit donc venir du DISQUE, qui sait ou il range ce qu'on lui
 * confie. Les visuels livres avec le depot, eux, restent servis par asset() :
 * ce sont des fichiers de public/, deposes a la construction.
 */

beforeEach(function () {
    $this->categorie = Categorie::factory()->create();
});

it('demande au disque l adresse d un fichier televerse', function () {
    // La configuration passe par fake() : celui-ci fige le disque au moment de
    // l'appel, et un config() pose apres coup ne serait plus lu.
    Storage::fake('public', ['url' => 'https://medias.exemple.ci']);

    $article = Article::factory()->create(['categorie_id' => $this->categorie->id, 'image_source' => 'storage/actualites/couverture.jpg']);

    expect($article->urlCouverture())->toBe('https://medias.exemple.ci/actualites/couverture.jpg');
});

it('sert par asset les visuels livres avec le depot', function () {
    config(['filesystems.disks.public.url' => 'https://medias.exemple.ci']);

    // Pas de prefixe « storage/ » : ce fichier vient de maquettes-frontoffice/
    // et vit dans public/. Il n'a rien a faire sur le stockage objet.
    $article = Article::factory()->create(['categorie_id' => $this->categorie->id, 'image_source' => 'images/actualites/hero.jpg']);

    expect($article->urlCouverture())->toBe(asset('images/actualites/hero.jpg'));
});

it('vaut aussi pour la photo d un compte', function () {
    // La configuration passe par fake() : celui-ci fige le disque au moment de
    // l'appel, et un config() pose apres coup ne serait plus lu.
    Storage::fake('public', ['url' => 'https://medias.exemple.ci']);

    $compte = User::factory()->create(['photo' => 'storage/comptes/portrait.jpg']);

    expect($compte->urlPhoto())->toBe('https://medias.exemple.ci/comptes/portrait.jpg');
});
