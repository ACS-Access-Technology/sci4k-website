<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\PagePubliqueController;
use Illuminate\Support\Facades\Route;

// La racine est servie depuis la base depuis le lot 2b. Les pages non encore
// portees — biens, contact, mentions legales, politique de confidentialite —
// vivent dans public/, deposees par tools/sync-frontoffice.sh, et le serveur
// les sert directement sans passer par une route.
Route::get('/', [PagePubliqueController::class, 'accueil'])->name('home');

// L'ancienne adresse de l'accueil, que le site s'ecrivait a lui-meme dans ses
// propres liens. La page statique du meme nom est exclue de la synchronisation.
Route::permanentRedirect('/index.html', '/');

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
Route::get('/presentation', [PagePubliqueController::class, 'presentation'])->name('presentation.index');

// Memes raisons que pour /actualites.html ci-dessus : les pages statiques du
// meme nom sont exclues de tools/sync-frontoffice.sh.
Route::permanentRedirect('/services.html', '/services');
Route::permanentRedirect('/faq.html', '/faq');
Route::permanentRedirect('/presentation.html', '/presentation');

Route::get('/langue/{code}', [LangueController::class, 'basculer'])->name('langue.basculer');

// Plan du site rendu depuis la base : le fichier fige de frontoffice/ annonçait
// des adresses qui ne repondent plus que par une redirection, et ne connaissait
// aucun article. Il est exclu de la synchronisation, sans quoi il masquerait
// cette route — le serveur sert un fichier de public/ avant d'entrer dans PHP.
Route::get('/sitemap.xml', \App\Http\Controllers\PlanDuSiteController::class)->name('plan-du-site');

Route::middleware(['auth', 'verified'])->group(function () {
    // Le tableau de bord etait une vue statique aux quatre rectangles haches
    // du starter kit. Il compte desormais le contenu, d'ou un composant.
    Route::get('dashboard', \App\Livewire\Admin\TableauDeBord::class)->name('dashboard');
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

        // Petits ensembles edites d'un bloc : un seul ecran chacun, ni liste ni
        // formulaire separes. Un lecteur peut les consulter, le composant
        // refusant l'enregistrement.
        // Les six collections de blocs. Trois d'entre elles n'ont pas de route
        // de creation : leur slug designe un emplacement du site, si bien
        // qu'un element cree ne s'afficherait nulle part.
        Route::get('/temoignages', \App\Livewire\Admin\TemoignageListe::class)->name('temoignages.liste');
        Route::get('/partenaires', \App\Livewire\Admin\PartenaireListe::class)->name('partenaires.liste');
        Route::get('/equipe', \App\Livewire\Admin\MembreEquipeListe::class)->name('equipe.liste');
        Route::get('/encarts', \App\Livewire\Admin\EncartListe::class)->name('encarts.liste');
        Route::get('/images-de-fond', \App\Livewire\Admin\ImageDeFondListe::class)->name('images-de-fond.liste');
        Route::get('/reglages-de-section', \App\Livewire\Admin\ReglageDeSectionListe::class)->name('reglages-de-section.liste');

        Route::get('/valeurs', \App\Livewire\Admin\ValeurEnsemble::class)->name('valeurs');
        Route::get('/chiffres-cles', \App\Livewire\Admin\ChiffreCleEnsemble::class)->name('chiffres-cles');
        Route::get('/etapes-processus', \App\Livewire\Admin\EtapeProcessusEnsemble::class)->name('etapes-processus');

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

            // Formulaires des blocs. Les trois collections a slug fige n'ont
            // pas de route de creation : leur formulaire la refuse aussi, la
            // route absente ne suffisant pas — Livewire monte le composant.
            Route::get('/temoignages/creation', \App\Livewire\Admin\TemoignageFormulaire::class)->name('temoignages.creation');
            Route::get('/temoignages/{element}/edition', \App\Livewire\Admin\TemoignageFormulaire::class)->name('temoignages.edition');

            Route::get('/partenaires/creation', \App\Livewire\Admin\PartenaireFormulaire::class)->name('partenaires.creation');
            Route::get('/partenaires/{element}/edition', \App\Livewire\Admin\PartenaireFormulaire::class)->name('partenaires.edition');

            Route::get('/equipe/creation', \App\Livewire\Admin\MembreEquipeFormulaire::class)->name('equipe.creation');
            Route::get('/equipe/{element}/edition', \App\Livewire\Admin\MembreEquipeFormulaire::class)->name('equipe.edition');

            Route::get('/encarts/{element}/edition', \App\Livewire\Admin\EncartFormulaire::class)->name('encarts.edition');
            Route::get('/images-de-fond/{element}/edition', \App\Livewire\Admin\ImageDeFondFormulaire::class)->name('images-de-fond.edition');
            Route::get('/reglages-de-section/{element}/edition', \App\Livewire\Admin\ReglageDeSectionFormulaire::class)->name('reglages-de-section.edition');
        });
    });

require __DIR__.'/settings.php';
