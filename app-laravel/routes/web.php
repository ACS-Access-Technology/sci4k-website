<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\PagePubliqueController;
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
Route::get('/actualites/{article:slug}', [ActualiteController::class, 'detail'])->name('actualites.detail');

// Les douze articles partageaient cette adresse unique, distingues par ?id=.
// Declaree avant que le fichier statique du meme nom ne soit servi — il est
// d'ailleurs exclu de tools/sync-frontoffice.sh.
Route::get('/actualite-detail.html', [ActualiteController::class, 'ancienneAdresse']);

// L'ancienne adresse de la liste n'est plus servie : la page statique du meme
// nom est exclue de la synchronisation, pour qu'il n'existe jamais deux
// adresses rendant deux versions divergentes des memes actualites.
Route::permanentRedirect('/actualites.html', '/actualites');

Route::get('/services', [PagePubliqueController::class, 'services'])->name('services.index');
Route::get('/faq', [PagePubliqueController::class, 'faq'])->name('faq.index');

// Memes raisons que pour /actualites.html ci-dessus : les pages statiques du
// meme nom sont exclues de tools/sync-frontoffice.sh.
Route::permanentRedirect('/services.html', '/services');
Route::permanentRedirect('/faq.html', '/faq');

Route::get('/langue/{code}', [LangueController::class, 'basculer'])->name('langue.basculer');

// Plan du site rendu depuis la base : le fichier fige de frontoffice/ annonçait
// des adresses qui ne repondent plus que par une redirection, et ne connaissait
// aucun article. Il est exclu de la synchronisation, sans quoi il masquerait
// cette route — le serveur sert un fichier de public/ avant d'entrer dans PHP.
Route::get('/sitemap.xml', \App\Http\Controllers\PlanDuSiteController::class)->name('plan-du-site');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:administrateur|editeur|lecteur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => view('admin.tableau-de-bord'))->name('tableau-de-bord');
        Route::get('/articles', \App\Livewire\Admin\ArticleListe::class)->name('articles.liste');
        Route::get('/services', \App\Livewire\Admin\ServiceListe::class)->name('services.liste');
        Route::get('/faq', \App\Livewire\Admin\FaqListe::class)->name('faq.liste');
        Route::get('/rubriques-faq', \App\Livewire\Admin\RubriqueFaqListe::class)->name('rubriques-faq.liste');

        // Un lecteur consulte la liste mais n'ecrit pas : la restriction est
        // posee ici, et non sur le groupe entier.
        Route::middleware('role:administrateur|editeur')->group(function () {
            Route::get('/articles/creation', \App\Livewire\Admin\ArticleFormulaire::class)->name('articles.creation');
            Route::get('/articles/{article}/edition', \App\Livewire\Admin\ArticleFormulaire::class)->name('articles.edition');
            Route::get('/services/creation', \App\Livewire\Admin\ServiceFormulaire::class)->name('services.creation');
            Route::get('/services/{service}/edition', \App\Livewire\Admin\ServiceFormulaire::class)->name('services.edition');

            // /faq/creation doit precede /faq/{question}/edition : sinon
            // « creation » serait capture comme identifiant de question.
            Route::get('/faq/creation', \App\Livewire\Admin\FaqFormulaire::class)->name('faq.creation');
            Route::get('/faq/{question}/edition', \App\Livewire\Admin\FaqFormulaire::class)->name('faq.edition');

            Route::get('/rubriques-faq/creation', \App\Livewire\Admin\RubriqueFaqFormulaire::class)->name('rubriques-faq.creation');
            Route::get('/rubriques-faq/{rubrique}/edition', \App\Livewire\Admin\RubriqueFaqFormulaire::class)->name('rubriques-faq.edition');
        });
    });

require __DIR__.'/settings.php';
