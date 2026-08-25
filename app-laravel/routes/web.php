<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\LangueController;
use Illuminate\Support\Facades\Route;

// La racine sert la page d'accueil du site, restee statique. Les dix pages non
// encore portees vivent dans public/, deposees par tools/sync-frontoffice.sh ;
// le serveur les sert directement, sans passer par une route.
// Le contenu est lu et renvoye plutot que servi par response()->file() : cette
// derniere produit une reponse binaire dont le corps reste vide en test, ce qui
// rendrait la page non verifiable.
Route::get('/', fn () => response(
    file_get_contents(public_path('index.html'))
)->header('Content-Type', 'text/html; charset=UTF-8'))->name('home');

Route::get('/actualites', [ActualiteController::class, 'index'])->name('actualites.index');

// L'ancienne adresse de la liste n'est plus servie : la page statique du meme
// nom est exclue de la synchronisation, pour qu'il n'existe jamais deux
// adresses rendant deux versions divergentes des memes actualites.
Route::permanentRedirect('/actualites.html', '/actualites');

Route::get('/langue/{code}', [LangueController::class, 'basculer'])->name('langue.basculer');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:administrateur|editeur|lecteur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => view('admin.tableau-de-bord'))->name('tableau-de-bord');
    });

require __DIR__.'/settings.php';
