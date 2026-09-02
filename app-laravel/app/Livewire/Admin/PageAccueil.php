<?php

namespace App\Livewire\Admin;

use App\Models\ChiffreCle;
use App\Models\CommuneDuBandeau;
use App\Models\Encart;
use App\Models\ImageDeFond;
use App\Models\Partenaire;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Temoignage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * L'accueil du site, gere depuis un seul ecran.
 *
 * PREMIER ECRAN DE LA REFONTE : une page d'administration par page publique,
 * au lieu d'un rangement par type de contenu. Ce que montre l'accueil venait
 * jusqu'ici de HUIT ecrans differents — en-tetes de section, chiffres cles,
 * banderole des communes, services, annonces & actions, temoignages,
 * partenaires, images de fond. Pour changer le titre du hero puis son image,
 * il fallait en traverser deux, et rien n'indiquait lequel.
 *
 * Les anciens ecrans restent en place et continuent de fonctionner : les deux
 * lisent et ecrivent LES MEMES tables. Aucune donnee n'est dupliquee, aucune
 * migration n'est necessaire, et une modification faite d'un cote se voit de
 * l'autre.
 *
 * L'ORDRE DES MODULES N'EST PAS MODIFIABLE, et l'ecran ne propose donc aucune
 * poignee de deplacement. Il est fixe dans le gabarit public accueil.blade.php
 * — offrir un glisser-deposer qui ne changerait rien serait un ecran menteur
 * de plus. Le jour ou l'ordre viendra de la base, la poignee aura un sens.
 */
#[Layout('layouts.app')]
class PageAccueil extends Component
{
    /**
     * Les huit modules de l'accueil, dans l'ordre ou le visiteur les voit.
     *
     * Chaque entree decrit ce que le module pilote, et d'ou vient la donnee —
     * l'ecran l'affiche, pour qu'un editeur sache ce qu'il touche.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'hero' => [
                'intitule' => __('Bannière principale'),
                'resume' => __('Étiquette, titre, accroche, chiffres clés et image de fond.'),
                'section' => 'home.hero',
                'fond' => 'accueil-hero',
                'ancre' => '#accueil',
            ],
            'bandeau' => [
                'intitule' => __('Bande déroulante'),
                'resume' => __('Communes défilantes sous la bannière, et leur apparence.'),
                'section' => CommuneDuBandeau::SECTION,
                'ancre' => null,
            ],
            'services' => [
                'intitule' => __('Services'),
                'resume' => __('En-tête du bloc et services mis en avant.'),
                'section' => 'home.services',
                'ancre' => '#services',
            ],
            'annonce' => [
                'intitule' => __('Annonce'),
                'resume' => __('Encart promotionnel affiché après les services.'),
                'encart' => 'accueil.annonce',
                'ancre' => '#encart-accueil',
            ],
            'cta' => [
                'intitule' => __("Bandeau d'appel à l'action"),
                'resume' => __('Bloc pleine largeur avant les actualités.'),
                'encart' => 'accueil',
                'fond' => 'cta',
                'ancre' => null,
            ],
            'articles' => [
                'intitule' => __('Articles'),
                'resume' => __('En-tête du bloc. Les trois articles affichés sont les plus récents publiés.'),
                'section' => 'home.articles',
                'ancre' => '#articles',
            ],
            'temoignages' => [
                'intitule' => __('Avis clients'),
                'resume' => __('En-tête du bloc, avis affichés et image de fond.'),
                'section' => 'home.testimonials',
                'fond' => 'temoignages',
                'ancre' => null,
            ],
            'partenaires' => [
                'intitule' => __('Partenaires'),
                'resume' => __('En-tête du bloc et logos affichés.'),
                'section' => 'home.partners',
                'ancre' => null,
            ],
        ];
    }

    /** Module ouvert. Contraint a la liste ci-dessus. */
    public string $module = 'hero';

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Valeurs de l'en-tete de section du module ouvert. */
    public array $entete = [];

    /** Valeurs de l'encart du module ouvert. */
    public array $encart = [];

    /** Reglages d'apparence de la bande deroulante. */
    public array $bandeau = [];

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur', 'redacteur', 'lecteur']), 403);

        $this->langueActive = app()->getLocale();
        $this->charger();
    }

    public function ouvrir(string $module): void
    {
        abort_unless(array_key_exists($module, $this->modules()), 404);

        $this->module = $module;
        $this->message = null;
        $this->resetValidation();
        $this->charger();
    }

    /** Description du module ouvert. */
    public function moduleCourant(): array
    {
        return $this->modules()[$this->module] ?? $this->modules()['hero'];
    }

    /**
     * Recharge les champs du module ouvert depuis la base.
     *
     * Les valeurs sont relues a chaque changement de module plutot que toutes
     * chargees d'un coup : huit modules, c'est huit fois plus d'etat expose au
     * navigateur, et autant de champs qu'une validation devrait couvrir.
     */
    protected function charger(): void
    {
        $description = $this->moduleCourant();
        $this->entete = [];
        $this->encart = [];
        $this->bandeau = [];

        if ($slug = $description['section'] ?? null) {
            $section = ReglageDeSection::where('slug', $slug)->first();

            foreach (['etiquette', 'titre', 'chapo'] as $champ) {
                $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
                $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
            }

            if ($this->module === 'bandeau') {
                $this->bandeau = [
                    'fond' => (string) ($section?->option('fond', 'sombre') ?? 'sombre'),
                    'separateur' => (string) ($section?->option('separateur', '·') ?? '·'),
                    'casse' => (string) ($section?->option('casse', 'majuscules') ?? 'majuscules'),
                ];
            }
        }

        if ($slug = $description['encart'] ?? null) {
            $encart = Encart::where('slug', $slug)->first();

            foreach (['etiquette', 'titre', 'texte', 'libelle_bouton'] as $champ) {
                $this->encart[$champ.'_fr'] = (string) ($encart?->{$champ.'_fr'} ?? '');
                $this->encart[$champ.'_en'] = (string) ($encart?->{$champ.'_en'} ?? '');
            }

            $this->encart['cible_bouton'] = (string) ($encart?->cible_bouton ?? '');
            $this->encart['visible'] = (bool) ($encart?->visible ?? false);
            $this->encart['diffusion_de'] = $encart?->diffusion_de?->format('Y-m-d') ?? '';
            $this->encart['diffusion_a'] = $encart?->diffusion_a?->format('Y-m-d') ?? '';
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        foreach (['etiquette', 'titre', 'texte', 'libelle_bouton'] as $champ) {
            $regles['encart.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['encart.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        $regles['encart.cible_bouton'] = ['nullable', 'string', 'max:190'];
        $regles['encart.visible'] = ['nullable', 'boolean'];
        $regles['encart.diffusion_de'] = ['nullable', 'date'];
        $regles['encart.diffusion_a'] = ['nullable', 'date', 'after_or_equal:encart.diffusion_de'];

        $regles['bandeau.fond'] = ['nullable', 'in:sombre,clair'];
        $regles['bandeau.separateur'] = ['nullable', 'string', 'max:5'];
        $regles['bandeau.casse'] = ['nullable', 'in:majuscules,normale'];

        return $regles;
    }

    protected function validationAttributes(): array
    {
        return [
            'encart.diffusion_de' => __('la date de début'),
            'encart.diffusion_a' => __('la date de fin'),
        ];
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->validate();

        $description = $this->moduleCourant();

        if ($slug = $description['section'] ?? null) {
            $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
            $section->fill($this->entete);
            $section->save();

            if ($this->module === 'bandeau' && $this->bandeau !== []) {
                // poserOptions() ne fait que POSER les valeurs sur le modele :
                // il n'enregistre pas. Sans ce second save(), l'apparence de
                // la banderole se perdait sans un mot.
                $section->poserOptions($this->bandeau);
                $section->save();
            }
        }

        if ($slug = $description['encart'] ?? null) {
            $encart = Encart::firstOrNew(['slug' => $slug]);

            // Seules les colonnes de cet ecran sont ecrites : « impressions »
            // et « ordre » sont fillable, et un tableau public Livewire est
            // fixe par le navigateur, cles comprises.
            foreach (['etiquette', 'titre', 'texte', 'libelle_bouton'] as $champ) {
                $encart->{$champ.'_fr'} = $this->encart[$champ.'_fr'] ?? '';
                $encart->{$champ.'_en'} = $this->encart[$champ.'_en'] ?? '';
            }

            $encart->cible_bouton = $this->encart['cible_bouton'] ?: null;
            $encart->visible = (bool) ($this->encart['visible'] ?? false);
            $encart->diffusion_de = $this->encart['diffusion_de'] ?: null;
            $encart->diffusion_a = $this->encart['diffusion_a'] ?: null;
            $encart->save();
        }

        $this->charger();
        $this->message = __('Module enregistré.');
        $this->dispatch('toast', message: __(':module enregistré.', ['module' => $description['intitule']]), variant: 'success');
    }

    /**
     * Bascule la visibilite d'un element d'une collection du module ouvert.
     *
     * C'est le geste le plus courant sur ces listes — retirer un temoignage,
     * masquer un partenaire — et il ne vaut pas un aller-retour vers l'ecran
     * dedie. Les modifications de fond (photo, texte long) y restent.
     */
    public function basculer(string $famille, int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $modele = $this->modeleDeLaFamille($famille);
        abort_unless($modele !== null, 404);

        $element = $modele::findOrFail($id);
        $element->visible = ! $element->visible;
        $element->save();

        $this->dispatch('toast', message: __('Affichage mis à jour.'), variant: 'success');
    }

    /** Les collections que cet ecran sait masquer ou afficher. */
    protected function modeleDeLaFamille(string $famille): ?string
    {
        return [
            'chiffres' => ChiffreCle::class,
            'communes' => CommuneDuBandeau::class,
            'services' => Service::class,
            'temoignages' => Temoignage::class,
            'partenaires' => Partenaire::class,
        ][$famille] ?? null;
    }

    /**
     * Comment nommer un element de collection a l'ecran.
     *
     * Chaque famille porte son intitule sur une colonne differente — « nom »
     * pour une commune ou un partenaire, « auteur » pour un avis, une methode
     * traduite pour un service ou un chiffre cle. La vue enchainait des ?? sur
     * des appels de methode, ce qui ne protege de rien : l'operateur teste une
     * valeur nulle, pas une methode absente, et l'ecran tombait sur un
     * Temoignage.
     */
    public function libelleDeLElement(string $famille, mixed $element): string
    {
        $libelle = match ($famille) {
            'communes', 'partenaires' => $element->nom,
            'temoignages' => $element->auteur,
            'chiffres' => trim(($element->valeur ?? '').($element->suffixe ?? '').' '.$element->intitule($this->langueActive)),
            'services' => $element->titre($this->langueActive),
            default => null,
        };

        return trim((string) $libelle) ?: __('(sans titre)');
    }

    /** Le fond du module ouvert, s'il en a un. */
    public function fondDuModule(): ?ImageDeFond
    {
        $slug = $this->moduleCourant()['fond'] ?? null;

        return $slug ? ImageDeFond::where('slug', $slug)->first() : null;
    }

    public function render(): View
    {
        $collections = match ($this->module) {
            'hero' => ['chiffres' => ChiffreCle::query()->orderBy('ordre')->orderBy('id')->get()],
            'bandeau' => ['communes' => CommuneDuBandeau::query()->orderBy('ordre')->orderBy('id')->get()],
            'services' => ['services' => Service::query()->orderBy('ordre')->orderBy('id')->get()],
            'temoignages' => ['temoignages' => Temoignage::query()->orderBy('ordre')->orderBy('id')->get()],
            'partenaires' => ['partenaires' => Partenaire::query()->orderBy('ordre')->orderBy('id')->get()],
            default => [],
        };

        return view('livewire.admin.page-accueil', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'collections' => $collections,
            'fond' => $this->fondDuModule(),
            // Les trois articles que l'accueil affichera : ils ne se choisissent
            // pas, ce sont les plus recents publies. L'ecran les montre pour
            // que l'editeur sache ce qui sortira, sans laisser croire qu'il
            // peut les selectionner ici.
            'articles' => $this->module === 'articles'
                ? \App\Models\Article::publies()->latest('date_publication')->limit(3)->get()
                : collect(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__("Page d'accueil"));
    }
}
