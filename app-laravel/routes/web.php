<?php

use App\Http\Controllers\AbonnementNewsletterController;
use App\Http\Controllers\ActualiteController;
use App\Http\Controllers\BienPublicController;
use App\Http\Controllers\CommentaireController;
use App\Http\Controllers\DemandeDeVisiteController;
use App\Http\Controllers\LangueController;
use App\Http\Controllers\MessageDeContactController;
use App\Http\Controllers\PagePubliqueController;
use App\Http\Controllers\DesinscriptionNewsletterController;
use App\Http\Controllers\PlanDuSiteController;
use App\Http\Controllers\RobotsController;
use App\Livewire\Admin\AbonneNewsletterListe;
use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\DemandeDeVisiteListe;
use App\Livewire\Admin\Frequentation;
use App\Livewire\Admin\PageAccueil;
use App\Livewire\Admin\PageActualites;
use App\Livewire\Admin\PageBiens;
use App\Livewire\Admin\PageContact;
use App\Livewire\Admin\PageFaq;
use App\Livewire\Admin\PageServices;
use App\Livewire\Admin\PagePresentation;
use App\Livewire\Admin\PagesStatiques;
use App\Livewire\Admin\JournalActivite;
use App\Livewire\Admin\Menus;
use App\Livewire\Admin\Mediatheque;
use App\Livewire\Admin\MessageListe;
use App\Livewire\Admin\Referentiels;
use App\Livewire\Admin\TableauDeBord;
use App\Livewire\Admin\UtilisateurListe;
use App\Livewire\Public\CatalogueDesBiens;
use Illuminate\Support\Facades\Route;

/*
 * LA LANGUE EST DANS L'ADRESSE
 *
 * Elle vivait en session : la MEME adresse servait deux contenus. Un moteur de
 * recherche n'a pas de session — il ne voyait donc que le francais, et tout le
 * site anglais lui etait invisible. Un lien anglais partage s'ouvrait de meme
 * en francais chez le destinataire.
 *
 * Le francais garde ses adresses, l'anglais prend un prefixe : /services et
 * /en/services. C'est la forme que Google recommande, et le marche principal
 * n'a pas a porter un prefixe qui ne lui apprend rien.
 *
 * Le segment est FACULTATIF et contraint a « en » : une seule declaration par
 * page sert les deux langues, et aucun appel a route() n'a eu a changer.
 * `URL::defaults()` — pose par le middleware AppliqueLangue — decide lequel des
 * deux est produit.
 *
 * Les POST n'en portent pas : un formulaire n'est pas une page, il n'est pas
 * indexe, et le dupliquer aurait double des routes limitees en debit.
 */

// La racine est servie depuis la base depuis le lot 2b. Les pages non encore
// portees — biens, contact, mentions legales, politique de confidentialite —
// vivent dans public/, deposees par tools/sync-frontoffice.sh, et le serveur
// les sert directement sans passer par une route.
/**
 * Les pages publiques, enregistrees DEUX FOIS : nues pour le francais, sous
 * « /en » pour l'anglais.
 *
 * Une seule declaration avec un segment facultatif ne fonctionne pas : Laravel
 * ne rend facultatif qu'un parametre FINAL, et « {langue?}/services » compile
 * en « /en/services » obligatoire. Verifie, et c'est ce qui a fait tomber cent
 * cinquante et un tests d'un coup.
 *
 * Les noms de route anglais portent le prefixe « en. ». Aucune vue n'a eu a le
 * savoir : GenerateurDUrlBilingue traduit les appels a route() selon la langue
 * en cours. Voir cette classe pour le raisonnement.
 *
 * Les POST n'y sont pas : un formulaire n'est pas une page, il n'est pas
 * indexe, et le dupliquer aurait double des routes limitees en debit.
 */
$pagesPubliques = function () {
    Route::get('/', [PagePubliqueController::class, 'accueil'])->name('home');

    // L'ancienne adresse de l'accueil, que le site s'ecrivait a lui-meme dans ses
    // propres liens. La page statique du meme nom est exclue de la synchronisation.

    Route::get('/actualites', [ActualiteController::class, 'index'])->name('actualites.index');
    Route::get('/actualites/{article:slug}', [ActualiteController::class, 'detail'])->name('actualites.detail');

    // Les douze articles partageaient cette adresse unique, distingues par ?id=.
    // Declaree avant que le fichier statique du meme nom ne soit servi — il est
    // d'ailleurs exclu de tools/sync-frontoffice.sh.

    // L'ancienne adresse de la liste n'est plus servie : la page statique du meme
    // nom est exclue de la synchronisation, pour qu'il n'existe jamais deux
    // adresses rendant deux versions divergentes des memes actualites.

    // Le catalogue est un COMPOSANT et non une vue : ses cinq filtres tournent sur
    // le serveur, la ou le site rendait ses biens d'un bloc puis les masquait en
    // JavaScript. Voir CatalogueDesBiens pour le raisonnement.
    Route::get('/biens', CatalogueDesBiens::class)->name('biens.index');
    Route::get('/biens/{slug}', BienPublicController::class)->name('biens.detail');

    // L'ancienne adresse statique. La page du meme nom est exclue de la
    // synchronisation, sans quoi elle masquerait ces routes.

    Route::get('/services', [PagePubliqueController::class, 'services'])->name('services.index');
    Route::get('/faq', [PagePubliqueController::class, 'faq'])->name('faq.index');
    Route::get('/presentation', [PagePubliqueController::class, 'presentation'])->name('presentation.index');
    Route::get('/contact', [PagePubliqueController::class, 'contact'])->name('contact.index');
    Route::get('/mentions-legales', fn () => app(PagePubliqueController::class)->pageStatique('mentions-legales'))->name('mentions-legales.index');
    Route::get('/politique-confidentialite', fn () => app(PagePubliqueController::class)->pageStatique('politique-confidentialite'))->name('politique-confidentialite.index');
};

Route::group([], $pagesPubliques);
Route::prefix('en')->name('en.')->group($pagesPubliques);

Route::permanentRedirect('/index.html', '/');
Route::get('/actualite-detail.html', [ActualiteController::class, 'ancienneAdresse']);
Route::permanentRedirect('/actualites.html', '/actualites');
Route::permanentRedirect('/biens.html', '/biens');

// Memes raisons que pour /actualites.html ci-dessus : les pages statiques du
// meme nom sont exclues de tools/sync-frontoffice.sh.
Route::permanentRedirect('/services.html', '/services');
Route::permanentRedirect('/faq.html', '/faq');
Route::permanentRedirect('/presentation.html', '/presentation');
Route::permanentRedirect('/contact.html', '/contact');

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

// Le retrait, par l'interesse lui-meme. On pouvait s'inscrire, et le backoffice
// pouvait desinscrire quelqu'un — mais l'abonne n'avait aucun moyen de partir
// sans le demander a l'agence.
//
// EN DEUX TEMPS : le lien ouvre une page qui demande confirmation, et c'est le
// bouton qui retire. Un retrait declenche par le simple chargement de l'adresse
// partirait au premier antivirus de messagerie, qui VISITE les liens d'un
// message pour les inspecter.
Route::get('/newsletter/desinscription/{jeton}', [DesinscriptionNewsletterController::class, 'formulaire'])
    ->name('newsletter.desinscription');
Route::post('/newsletter/desinscription/{jeton}', [DesinscriptionNewsletterController::class, 'retirer'])
    ->middleware('throttle:10,1')
    ->name('newsletter.desinscription.retirer');

// Commentaires sous un article. Memes protections que les trois ecritures
// publiques ci-dessus, plus un filtre qui met de cote ce qui ressemble a du
// courrier indesirable : le commentaire parait tout de suite, et « tout de
// suite » vaut aussi pour la publicite si rien ne la retient.
Route::post('/actualites/{article:slug}/commentaires', CommentaireController::class)
    ->middleware('throttle:5,1')
    ->name('commentaires.depot');

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

// Meme raison, meme piege : le fichier fige de public/ etait servi avant PHP,
// et le champ « Fichier robots.txt » de l'ecran Configuration n'etait lu nulle
// part. Le fichier a ete retire pour que cette route reponde.
Route::get('/robots.txt', RobotsController::class)->name('robots');

// Ces deux adresses n'exigeaient qu'un compte connecte, la ou tout le reste de
// l'administration exige un role. « verified » n'y ajoutait rien : le
// middleware ne bloque que si le modele implemente MustVerifyEmail, ce que
// User ne fait pas. Un compte sans role lisait donc le tableau de bord et le
// journal des activites qu'il affiche. Meme garde que le groupe ci-dessous.
Route::middleware(['auth', 'verified', 'role:administrateur|editeur|redacteur|lecteur'])->group(function () {
    // Le tableau de bord etait une vue statique aux quatre rectangles haches
    // du starter kit. Il compte desormais le contenu, d'ou un composant.
    Route::get('dashboard', TableauDeBord::class)->name('dashboard');
    Route::get('/admin/pages-editables', PagesStatiques::class)->name('admin.pages-statiques');
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

        // Refonte en cours : une page d'administration par page publique. Ces
        // ecrans s'ajoutent aux ecrans par type de contenu, qui restent en
        // place le temps que toutes les pages soient couvertes. Les deux
        // lisent et ecrivent les memes tables.
        Route::get('/pages/accueil', PageAccueil::class)->name('pages.accueil');
        Route::get('/pages/presentation', PagePresentation::class)->name('pages.presentation');
        Route::get('/pages/biens', PageBiens::class)->name('pages.biens');
        Route::get('/pages/services', PageServices::class)->name('pages.services');
        Route::get('/pages/actualites', PageActualites::class)->name('pages.actualites');
        Route::get('/pages/faq', PageFaq::class)->name('pages.faq');
        Route::get('/pages/contact', PageContact::class)->name('pages.contact');

        // Journal en lecture seule, ouvert a tous les roles qui entrent dans
        // l'administration : savoir qui a touche a quoi n'est pas un privilege.
        Route::get('/journal', JournalActivite::class)->name('journal');

        // Demandes recues du site public.
        Route::get('/messages', MessageListe::class)->name('messages');
        Route::get('/visites', DemandeDeVisiteListe::class)->name('visites');
        Route::get('/newsletter', AbonneNewsletterListe::class)->name('newsletter');
        Route::get('/mediatheque', Mediatheque::class)->name('mediatheque');
        Route::get('/frequentation', Frequentation::class)->name('frequentation');

        // Les ecrans par TYPE de contenu ont ete retires : chaque collection
        // s'edite depuis l'ecran de la page qui l'affiche, ou elle est
        // embarquee avec son formulaire. Leurs composants vivent toujours —
        // ce sont eux que les ecrans de page embarquent — mais ils n'ont plus
        // d'adresse a eux. Deux adresses pour une meme table, c'etait deux
        // endroits ou corriger le meme defaut.

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
