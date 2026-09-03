<?php

namespace App\Http\Controllers;

use App\Livewire\Admin\PageContact;
use App\Models\Article;
use App\Models\ChiffreCle;
use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\ImageDeFond;
use App\Models\MembreEquipe;
use App\Models\PageStatique;
use App\Models\Parametre;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PagePubliqueController extends Controller
{
    /**
     * Sert une page editable, ou la page HTML d'origine si elle est vide.
     *
     * La migration qui a cree ces trois lignes les a posees avec un contenu
     * VIDE et publie => true. Les routes ont ete branchees dessus sans que le
     * contenu des pages HTML n'ait ete transfere : /contact, /mentions-legales
     * et /politique-confidentialite servaient donc une coquille — un titre,
     * puis directement le pied de page. La panne etait silencieuse parce que
     * la ligne existe et qu'elle est publiee : firstOrFail() la trouvait.
     *
     * La page contact est le cas le plus couteux : son formulaire est le seul
     * point d'ecriture ouvert au public, et le menu principal y renvoie.
     *
     * Le repli n'est pas une solution definitive. Mentions legales et
     * politique de confidentialite sont du texte pur et ont vocation a etre
     * saisies depuis le backoffice ; la page contact, elle, porte un
     * formulaire, une carte et des horaires, et devra etre portee en Blade
     * comme l'ont ete l'accueil et la presentation. En attendant, mieux vaut
     * servir la vraie page que rien.
     */
    public function pageStatique(string $slug): SymfonyResponse|View
    {
        abort_unless(in_array($slug, PageStatique::slugsEditables(), true), 404);

        $page = PageStatique::where('slug', $slug)->where('publie', true)->first();
        $langue = app()->getLocale();

        if (! $page || trim($page->contenu($langue)) === '') {
            $fichier = public_path($slug.'.html');

            abort_unless(is_file($fichier), 404);

            // Le contenu est renvoye dans le corps de la reponse, et non
            // diffuse par response()->file() : ces pages font une vingtaine de
            // kilo-octets, et un fichier diffuse ne traverse pas le corps de la
            // reponse — aucun test ne pourrait alors verifier ce qui est servi.
            //
            // Sans cache : le jour ou l'editeur saisit le contenu, la page doit
            // basculer sans attendre l'expiration d'un en-tete.
            return response(file_get_contents($fichier), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Cache-Control' => 'no-cache, must-revalidate',
            ]);
        }

        return view('public.page-statique', ['page' => $page, 'langue' => $langue]);
    }
    /**
     * Page d'accueil.
     *
     * Sept sections, sept origines : en-tetes par ReglageDeSection, chiffres,
     * services, articles, temoignages, partenaires et banderole par leurs
     * tables. Les en-tetes sont charges d'une seule requete plutot que d'une
     * par section.
     */
    public function accueil(): View
    {
        $langue = app()->getLocale();

        $enTetes = ReglageDeSection::whereIn('slug', [
            'home.hero', 'home.services', 'home.articles',
            'home.testimonials', 'home.partners',
            CommuneDuBandeau::SECTION,
        ])->get()->keyBy('slug');

        // Reglages d'apparence du bandeau. Ils vivent dans les options de son
        // en-tete de section, comme la duree d'animation des chiffres cles.
        $reglageBandeau = $enTetes->get(CommuneDuBandeau::SECTION);

        // Annonce reelle de l'accueil, geree depuis « Annonces & Actions »
        // du backoffice (slug accueil.annonce).
        $annonce = Encart::where('slug', 'accueil.annonce')
            ->where('visible', true)
            ->first();
        if ($annonce) {
            $de = $annonce->diffusion_de;
            $a = $annonce->diffusion_a;
            if (($de && $de->isFuture()) || ($a && $a->isPast())) {
                $annonce = null;
            } else {
                $annonce->increment('impressions');
            }
        }

        // Bandeau CTA en bas de page, geree depuis « Annonces & Actions »
        // (slug accueil).
        $banderole = Encart::where('slug', 'accueil')
            ->where('visible', true)
            ->first();
        if ($banderole) {
            $de = $banderole->diffusion_de;
            $a = $banderole->diffusion_a;
            if (($de && $de->isFuture()) || ($a && $a->isPast())) {
                $banderole = null;
            } else {
                $banderole->increment('impressions');
            }
        }

        return view('public.accueil', [
            'hero' => $enTetes->get('home.hero'),
            'enteteServices' => $enTetes->get('home.services'),
            'enteteArticles' => $enTetes->get('home.articles'),
            'enteteTemoignages' => $enTetes->get('home.testimonials'),
            'entetePartenaires' => $enTetes->get('home.partners'),
            'annonce' => $annonce,
            'banderole' => $banderole,
            'chiffres' => ChiffreCle::where('visible', true)->orderBy('ordre')->orderBy('id')->get(),
            'communesDuBandeau' => CommuneDuBandeau::visibles()->ordonnees()->get(),
            'bandeauFond' => $reglageBandeau?->option('fond', 'sombre') ?? 'sombre',
            'bandeauSeparateur' => $reglageBandeau?->option('separateur', '·') ?? '·',
            'bandeauCasse' => $reglageBandeau?->option('casse', 'majuscules') ?? 'majuscules',
            'services' => Service::visibles()->ordonnees()->get(),
            // Les trois derniers articles publies, les plus recents d'abord.
            'articles' => Article::publies()->with('categorie')->latest('date_publication')->limit(3)->get(),
            'temoignages' => Temoignage::visibles()->ordonnees()->get(),
            'partenaires' => Partenaire::visibles()->ordonnees()->get(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'WebPage',
                '@id' => rtrim(url('/'), '/').'/#page',
                'url' => rtrim(url('/'), '/').'/',
                'name' => 'SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    public function services(): View
    {
        $langue = app()->getLocale();

        // L'en-tete du bloc « processus » et sa mise en page vivent sur la
        // section services.process, editee depuis l'ecran des etapes.
        $enTetes = ReglageDeSection::whereIn('slug', ['services.page', 'services.process'])->get()->keyBy('slug');
        $enteteProcessus = $enTetes->get('services.process');

        // Annonce promo apres la section processus, geree depuis l'ecran
        // « Encarts » du backoffice (slug services.annonce).
        $annonce = Encart::where('slug', 'services.annonce')
            ->where('visible', true)
            ->first();
        if ($annonce) {
            $de = $annonce->diffusion_de;
            $a = $annonce->diffusion_a;
            if (($de && $de->isFuture()) || ($a && $a->isPast())) {
                $annonce = null;
            } else {
                $annonce->increment('impressions');
            }
        }

        return view('public.services', [
            'banniere' => $enTetes->get('services.page'),
            'services' => Service::visibles()->ordonnees()->get(),
            'etapes' => EtapeProcessus::where('visible', true)->orderBy('ordre')->orderBy('id')->get(),
            'enteteProcessus' => $enteteProcessus,
            'miseEnPageProcessus' => $enteteProcessus?->option('mise_en_page', 'frise') ?? 'frise',
            'annonce' => $annonce,
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'CollectionPage',
                '@id' => route('services.index').'#page',
                'url' => route('services.index'),
                'name' => __('Nos services').' — SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    /**
     * Page de contact.
     *
     * Portee depuis la page HTML, qui vivait dans public/ et servait des
     * coordonnees ecrites en dur. Deux reglages de l'onglet « Contact » de la
     * configuration n'etaient lus par personne — les horaires et les
     * coordonnees de la carte : ils s'appliquent desormais ici.
     *
     * Chaque valeur se replie sur le texte d'origine, pour qu'une installation
     * neuve serve une page complete plutot qu'une page a trous.
     */
    public function contact(): View
    {
        $langue = app()->getLocale();

        $enTetes = ReglageDeSection::whereIn('slug', ['contact.page', 'contact.form', 'contact.map'])
            ->get()->keyBy('slug');

        $adresse = Parametre::lire('adresse_postale', implode("\n", [
            __('Cocody, Cité des Arts'),
            __('Résidence Paon, 3ème étage'),
            __("Abidjan, Côte d'Ivoire"),
        ]));

        // Le bandeau de la carte annonce l'adresse en deux temps : la ligne de
        // rue, puis le reste. Le decoupage suit donc les retours a la ligne
        // saisis dans la configuration, au lieu d'un texte fige.
        $lignesAdresse = array_values(array_filter(array_map('trim', explode("\n", $adresse)), 'strlen'));

        // Les sujets proposes viennent du backoffice, un par ligne, comme
        // l'adresse et les horaires. La liste d'origine est celle que declare
        // l'ecran de la page : elle n'est ecrite qu'a un seul endroit.
        $formulaire = $enTetes->get('contact.form');
        $sujetsSaisis = $formulaire?->texteBilingue('sujets', $langue) ?: '';

        if (trim($sujetsSaisis) === '') {
            $sujetsSaisis = __(PageContact::TEXTES_DU_FORMULAIRE['sujets']['defaut']);
        }

        $sujets = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $sujetsSaisis) ?: []),
            'strlen',
        ));

        return view('public.contact', [
            'banniere' => $enTetes->get('contact.page'),
            'enteteFormulaire' => $formulaire,
            'enteteCarte' => $enTetes->get('contact.map'),
            'sujets' => $sujets,
            // Passe explicitement : le composer qui alimente le gabarit ne
            // porte pas jusqu'ici, Blade rendant la vue fille AVANT la mise en
            // page dont elle herite.
            'nomDuSite' => Parametre::lire('nom_du_site', 'SCI4K'),
            'adressePostale' => $adresse,
            'premiereLigneAdresse' => $lignesAdresse[1] ?? ($lignesAdresse[0] ?? ''),
            'resteDeLAdresse' => implode(' — ', array_diff($lignesAdresse, [$lignesAdresse[1] ?? null])),
            'telephonePublic' => Parametre::lire('telephone', '+225 07 06 16 50 29'),
            'emailPublic' => Parametre::lire('email_public', 'contact@sci4k.com'),
            'horaires' => Parametre::lire('horaires', implode("\n", [
                __('Lundi — Vendredi : 08h00 - 18h00'),
                __('Samedi : 09h00 - 13h00'),
            ])),
            // Le repli reprend les coordonnees de l'ancienne carte embarquee.
            'coordonneesCarte' => Parametre::lire('coordonnees_carte', '5.3593,-3.9927'),
            'whatsappPublic' => preg_replace('/\D+/', '', (string) Parametre::lire('whatsapp', '2250706165029')) ?: '2250706165029',
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'ContactPage',
                '@id' => route('contact.index').'#page',
                'url' => route('contact.index'),
                'name' => __('Contact').' — SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    /**
     * Page de presentation.
     *
     * Les en-tetes de section viennent de ReglageDeSection, les valeurs et les
     * membres de leurs tables. Chaque en-tete est facultatif : la vue se replie
     * sur le texte d'origine, de sorte que la page reste complete meme avant
     * que l'import ne soit rejoue.
     */
    public function presentation(): View
    {
        $langue = app()->getLocale();

        $enTetes = ReglageDeSection::whereIn('slug', [
            'about.page', 'about.overview', 'about.dg', 'about.values', 'about.team',
        ])->get()->keyBy('slug');

        // Les deux illustrations de la page etaient ecrites en dur dans le
        // gabarit : les changer demandait de toucher au code. Elles passent
        // par « Images de fond », comme tous les autres visuels du site.
        $visuels = ImageDeFond::parSlugs(['presentation-apercu', 'presentation-directeur']);

        return view('public.presentation', [
            'visuelApercu' => $visuels->get('presentation-apercu'),
            'visuelDirecteur' => $visuels->get('presentation-directeur'),
            'banniere' => $enTetes->get('about.page'),
            'apercu' => $enTetes->get('about.overview'),
            'motDuDirecteur' => $enTetes->get('about.dg'),
            'enteteValeurs' => $enTetes->get('about.values'),
            'enteteEquipe' => $enTetes->get('about.team'),
            'valeurs' => Valeur::where('visible', true)->orderBy('ordre')->orderBy('id')->get(),
            'membres' => MembreEquipe::visibles()->ordonnees()->get(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'AboutPage',
                '@id' => route('presentation.index').'#page',
                'url' => route('presentation.index'),
                'name' => __('Présentation').' — SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    public function faq(): View
    {
        $langue = app()->getLocale();

        // Groupees par rubrique, dans l'ordre des rubriques puis des questions :
        // sur le site, le titre de chaque groupe EST le nom de la rubrique.
        //
        // La visibilite de la rubrique compte autant que celle de la question :
        // masquer une rubrique sans ce filtre laisserait sur la page un groupe
        // entier, titre compris, que l'editeur croyait avoir retire.
        $questions = QuestionFaq::visibles()
            ->whereHas('rubrique', fn ($r) => $r->where('visible', true))
            ->with('rubrique')
            ->get()
            ->sortBy(fn ($q) => [$q->rubrique->ordre, $q->ordre])
            ->values();

        return view('public.faq', [
            'groupes' => $questions->groupBy(fn ($q) => $q->rubrique->id),
            'banniere' => ReglageDeSection::where('slug', 'faq.page')->first(),
            'sectionQuestion' => ReglageDeSection::where('slug', 'faq.ask')->first(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'FAQPage',
                '@id' => route('faq.index').'#page',
                'url' => route('faq.index'),
                'inLanguage' => $langue,
                // La meme collection que la page, et non une seconde requete :
                // les donnees structurees annonçaient sinon un ordre et un
                // contenu qui pouvaient differer de ce que le visiteur lit.
                'mainEntity' => $questions->map(fn ($q) => [
                    '@type' => 'Question',
                    'name' => $q->question($langue),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q->reponse($langue)],
                ])->all(),
            ],
        ]);
    }
}
