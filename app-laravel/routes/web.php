<?php

use App\Http\Controllers\AbonnementNewsletterController;
use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\BienPublicController;
use App\Http\Controllers\DemandeDeVisiteController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\MessageDeContactController;
use App\Http\Controllers\PagePubliqueController;
use App\Http\Controllers\PlanDuSiteController;
use App\Livewire\Admin\AbonneNewsletterListe;
use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\ArticleListe;
use App\Livewire\Admin\BienFormulaire;
use App\Livewire\Admin\BienListe;
use App\Livewire\Admin\ChiffreCleEnsemble;
use App\Livewire\Admin\CommuneBandeauEnsemble;
use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\DemandeDeVisiteListe;
use App\Livewire\Admin\EncartFormulaire;
use App\Livewire\Admin\EncartListe;
use App\Livewire\Admin\EtapeProcessusEnsemble;
use App\Livewire\Admin\FaqFormulaire;
use App\Livewire\Admin\FaqListe;
use App\Livewire\Admin\ImageDeFondFormulaire;
use App\Livewire\Admin\ImageDeFondListe;
use App\Livewire\Admin\JournalActivite;
use App\Livewire\Admin\MembreEquipeFormulaire;
use App\Livewire\Admin\MembreEquipeListe;
use App\Livewire\Admin\Menus;
use App\Livewire\Admin\MessageListe;
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
use App\Livewire\Public\CatalogueDesBiens;
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

// Le catalogue est un COMPOSANT et non une vue : ses cinq filtres tournent sur
// le serveur, la ou le site rendait ses biens d'un bloc puis les masquait en
// JavaScript. Voir CatalogueDesBiens pour le raisonnement.
Route::get('/biens', CatalogueDesBiens::class)->name('biens.index');
Route::get('/biens/{slug}', BienPublicController::class)->name('biens.detail');

// L'ancienne adresse statique. La page du meme nom est exclue de la
// synchronisation, sans quoi elle masquerait ces routes.
Route::permanentRedirect('/biens.html', '/biens');

Route::get('/services', [PagePubliqueController::class, 'services'])->name('services.index');
Route::get('/faq', [PagePubliqueController::class, 'faq'])->name('faq.index');
Route::get('/presentation', [PagePubliqueController::class, 'presentation'])->name('presentation.index');

// Memes raisons que pour /actualites.html ci-dessus : les pages statiques du
// meme nom sont exclues de tools/sync-frontoffice.sh.
Route::permanentRedirect('/services.html', '/services');
Route::permanentRedirect('/faq.html', '/faq');
Route::permanentRedirect('/presentation.html', '/presentation');

// Le SEUL point d'ecriture ouvert au public. La limitation de debit y remplace
// l'authentification : le formulaire vit dans une page statique de public/, qui
// ne traverse pas la session et n'a donc pas de jeton CSRF a presenter. Le
// controleur ajoute un champ piege et borne toutes les longueurs.
Route::post('/messages', MessageDeContactController::class)
    ->middleware('throttle:5,1')
    ->name('messages.reception');

// Inscription a la lettre d'information, depuis le pied de page de toutes les
// pages du site. Memes protections que ci-dessus.
Route::post('/newsletter', AbonnementNewsletterController::class)
    ->middleware('throttle:5,1')
    ->name('newsletter.inscription');

// Demandes de visite depuis la fiche d'un bien. Memes protections.
Route::post('/visites', DemandeDeVisiteController::class)
    ->middleware('throttle:5,1')
    ->name('visites.reception');

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
        // /admin servait une page souche du lot 1 — un titre et « connecte en
        // tant que » — pendant que le vrai tableau de bord vivait sur
        // /dashboard. Deux adresses, dont une vide : celle-ci renvoie
        // desormais vers celle qui montre quelque chose.
        Route::redirect('/', '/dashboard')->name('tableau-de-bord');
        Route::get('/articles', ArticleListe::class)->name('articles.liste');
        Route::get('/biens', BienListe::class)->name('biens.liste');
        Route::get('/services', ServiceListe::class)->name('services.liste');
        Route::get('/faq', FaqListe::class)->name('faq.liste');
        Route::get('/rubriques-faq', RubriqueFaqListe::class)->name('rubriques-faq.liste');

        // Journal en lecture seule, ouvert a tous les roles qui entrent dans
        // l'administration : savoir qui a touche a quoi n'est pas un privilege.
        Route::get('/journal', JournalActivite::class)->name('journal');

        // Demandes recues du site public.
        Route::get('/messages', MessageListe::class)->name('messages');
        Route::get('/visites', DemandeDeVisiteListe::class)->name('visites');
        Route::get('/newsletter', AbonneNewsletterListe::class)->name('newsletter');

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
            // « creation » avant « {bien} » : sinon le mot serait capture comme
            // identifiant de bien.
            Route::get('/biens/creation', BienFormulaire::class)->name('biens.creation');
            Route::get('/biens/{bien}/edition', BienFormulaire::class)->name('biens.edition');
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
