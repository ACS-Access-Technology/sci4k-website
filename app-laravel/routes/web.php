<?php

use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\PagePubliqueController;
use App\Http\Controllers\PlanDuSiteController;
use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ArticleListe;
use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\CommuneBandeauEnsemble;
use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\EncartFormulaire;
use App\Livewire\Admin\EncartListe;
use App\Livewire\Admin\EtapeProcessusEnsemble;
use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Livewire\Admin\ImageDeFondFormulaire;
use App\Livewire\Admin\ImageDeFondListe;
use App\Livewire\Admin\MembreEquipeFormulaire;
use App\Livewire\Admin\MembreEquipeListe;
use App\Livewire\Admin\Menus;
use App\Livewire\Admin\PartenaireFormulaire;
use App\Livewire\Admin\PartenaireListe;
use App\Livewire\Admin\Referentiels;
use App\Livewire\Admin\ReglageDeSectionFormulaire;
use App\Livewire\Admin\ReglageDeSectionListe;
use App\Livewire\Admin\RubriqueFaqFormulaire;
use App\Livewire\Admin\RubriqueFaqListe;
use App\Livewire\Admin\ServiceFormulaire;
use App\Livewire\Admin\ServiceListe;
use App\Livewire\Admin\TableauDeBord;
use App\Livewire\Admin\TemoignageFormulaire;
use App\Livewire\Admin\TemoignageListe;
use App\Livewire\Admin\UtilisateurListe;
use App\Livewire\Admin\ValeurEnsemble;
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
Route::get('/sitemap.xml', PlanDuSiteController::class)->name('plan-du-site');

Route::middleware(['auth', 'verified'])->group(function () {
    // Le tableau de bord etait une vue statique aux quatre rectangles haches
    // du starter kit. Il compte desormais le contenu, d'ou un composant.
    Route::get('dashboard', TableauDeBord::class)->name('dashboard');
});

Route::middleware(['auth', 'role:administrateur|editeur|redacteur|lecteur'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', fn () => view('admin.tableau-de-bord'))->name('tableau-de-bord');
        Route::get('/articles', ArticleListe::class)->name('articles.liste');
        Route::get('/services', ServiceListe::class)->name('services.liste');
        Route::get('/faq', FaqListe::class)->name('faq.liste');
        Route::get('/rubriques-faq', RubriqueFaqListe::class)->name('rubriques-faq.liste');

        // Petits ensembles edites d'un bloc : un seul ecran chacun, ni liste ni
        // formulaire separes. Un lecteur peut les consulter, le composant
        // refusant l'enregistrement.
        // Les six collections de blocs. Trois d'entre elles n'ont pas de route
        // de creation : leur slug designe un emplacement du site, si bien
        // qu'un element cree ne s'afficherait nulle part.
        Route::get('/temoignages', TemoignageListe::class)->name('temoignages.liste');
        Route::get('/partenaires', PartenaireListe::class)->name('partenaires.liste');
        Route::get('/equipe', MembreEquipeListe::class)->name('equipe.liste');
        Route::get('/encarts', EncartListe::class)->name('encarts.liste');
        Route::get('/images-de-fond', ImageDeFondListe::class)->name('images-de-fond.liste');
        Route::get('/reglages-de-section', ReglageDeSectionListe::class)->name('reglages-de-section.liste');

        Route::get('/valeurs', ValeurEnsemble::class)->name('valeurs');
        Route::get('/chiffres-cles', ChiffreCleEnsemble::class)->name('chiffres-cles');
        Route::get('/etapes-processus', EtapeProcessusEnsemble::class)->name('etapes-processus');
        Route::get('/banderole', CommuneBandeauEnsemble::class)->name('banderole');

        // Un lecteur consulte la liste mais n'ecrit pas : la restriction est
        // posee ici, et non sur le groupe entier.
        // Le redacteur ecrit des ARTICLES, et rien d'autre : ses deux routes
        // sont donc ouvertes a part, hors du groupe qui donne acces aux
        // services, a la FAQ et aux blocs. Le composant refuse ensuite qu'il
        // publie, et qu'il touche aux articles d'un autre.
        Route::middleware('role:administrateur|editeur|redacteur')->group(function () {
            Route::get('/articles/creation', ArticleFormulaire::class)->name('articles.creation');
            Route::get('/articles/{article}/edition', ArticleFormulaire::class)->name('articles.edition');
        });

        Route::middleware('role:administrateur|editeur')->group(function () {
            Route::get('/services/creation', ServiceFormulaire::class)->name('services.creation');
            Route::get('/services/{service}/edition', ServiceFormulaire::class)->name('services.edition');

            // /faq/creation doit precede /faq/{question}/edition : sinon
            // « creation » serait capture comme identifiant de question.
            Route::get('/faq/creation', FaqFormulaire::class)->name('faq.creation');
            Route::get('/faq/{question}/edition', FaqFormulaire::class)->name('faq.edition');

            Route::get('/rubriques-faq/creation', RubriqueFaqFormulaire::class)->name('rubriques-faq.creation');
            Route::get('/rubriques-faq/{rubrique}/edition', RubriqueFaqFormulaire::class)->name('rubriques-faq.edition');

            // Formulaires des blocs. Les trois collections a slug fige n'ont
            // pas de route de creation : leur formulaire la refuse aussi, la
            // route absente ne suffisant pas — Livewire monte le composant.
            Route::get('/temoignages/creation', TemoignageFormulaire::class)->name('temoignages.creation');
            Route::get('/temoignages/{element}/edition', TemoignageFormulaire::class)->name('temoignages.edition');

            Route::get('/partenaires/creation', PartenaireFormulaire::class)->name('partenaires.creation');
            Route::get('/partenaires/{element}/edition', PartenaireFormulaire::class)->name('partenaires.edition');

            Route::get('/equipe/creation', MembreEquipeFormulaire::class)->name('equipe.creation');
            Route::get('/equipe/{element}/edition', MembreEquipeFormulaire::class)->name('equipe.edition');

            Route::get('/encarts/{element}/edition', EncartFormulaire::class)->name('encarts.edition');
            Route::get('/images-de-fond/{element}/edition', ImageDeFondFormulaire::class)->name('images-de-fond.edition');
            Route::get('/reglages-de-section/{element}/edition', ReglageDeSectionFormulaire::class)->name('reglages-de-section.edition');
        });

        // La configuration touche au referencement, au mode maintenance et aux
        // identifiants d'envoi : elle est reservee aux administrateurs, la ou
        // les ecrans de contenu s'ouvrent aussi aux editeurs. Le composant
        // reverifie le role a l'enregistrement — Livewire ne rejoue pas ce
        // middleware sur /livewire/update.
        Route::middleware('role:administrateur')->group(function () {
            Route::get('/configuration', Configuration::class)->name('configuration');
            Route::get('/referentiels', Referentiels::class)->name('referentiels');
            Route::get('/menus', Menus::class)->name('menus');
            Route::get('/utilisateurs', UtilisateurListe::class)->name('utilisateurs');
        });
    });

require __DIR__.'/settings.php';
