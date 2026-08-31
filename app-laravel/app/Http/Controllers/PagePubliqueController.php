<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\ChiffreCle;
use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Models\EtapeProcessus;
use App\Models\MembreEquipe;
use App\Models\PageStatique;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Temoignage;
use App\Models\Valeur;
use Illuminate\Contracts\View\View;

class PagePubliqueController extends Controller
{
    public function pageStatique(string $slug): View
    {
        abort_unless(in_array($slug, ['contact', 'mentions-legales', 'politique-confidentialite'], true), 404);
        $page = PageStatique::where('slug', $slug)->where('publie', true)->firstOrFail();
        return view('public.page-statique', ['page' => $page, 'langue' => app()->getLocale()]);
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
            'home.testimonials', 'home.partners', 'ad.house',
            CommuneDuBandeau::SECTION,
        ])->get()->keyBy('slug');

        // Reglages d'apparence du bandeau. Ils vivent dans les options de son
        // en-tete de section, comme la duree d'animation des chiffres cles.
        $reglageBandeau = $enTetes->get(CommuneDuBandeau::SECTION);

        $banderole = Encart::visibles()->ordonnees()->first();
        $banderole?->increment('impressions');

        return view('public.accueil', [
            'hero' => $enTetes->get('home.hero'),
            'enteteServices' => $enTetes->get('home.services'),
            'enteteArticles' => $enTetes->get('home.articles'),
            'enteteTemoignages' => $enTetes->get('home.testimonials'),
            'entetePartenaires' => $enTetes->get('home.partners'),
            'annonce' => $enTetes->get('ad.house'),
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

        return view('public.services', [
            'banniere' => $enTetes->get('services.page'),
            'services' => Service::visibles()->ordonnees()->get(),
            'etapes' => EtapeProcessus::where('visible', true)->orderBy('ordre')->orderBy('id')->get(),
            'enteteProcessus' => $enteteProcessus,
            'miseEnPageProcessus' => $enteteProcessus?->option('mise_en_page', 'frise') ?? 'frise',
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

        return view('public.presentation', [
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
