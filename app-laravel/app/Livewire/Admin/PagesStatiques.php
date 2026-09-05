<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Models\PageStatique;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PagesStatiques extends Component
{
    use PorteDesTextesDeBloc;

    /** La section ou vit le gabarit commun aux pages editables. */
    public const SECTION = 'pages.statiques';

    /**
     * Les textes du GABARIT, communs a toutes les pages editables.
     *
     * La ligne de date s'affiche sous le titre de chaque page legale. Elle
     * etait ecrite en dur : c'est le dernier texte du site que personne ne
     * pouvait changer.
     *
     * Elle vit ici et non dans le contenu d'une page : elle est la MEME sur
     * toutes, et la recopier dans chacune aurait fait diverger ce qui doit
     * rester identique.
     */
    public const TEXTES_DU_GABARIT = [
        'mention_mise_a_jour' => [
            'intitule' => 'Ligne de date sous le titre (:date sera remplacé)',
            'defaut' => 'Dernière mise à jour : :date',
        ],
    ];

    /**
     * Le trait interroge d'ordinaire le module ouvert. Cet ecran n'a pas de
     * modules : il porte une seule liste de textes.
     */
    protected function textesDeclares(): array
    {
        return self::TEXTES_DU_GABARIT;
    }

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /**
     * La page ouverte au chargement : la PREMIERE des pages editables.
     *
     * Elle valait « contact » en dur. Or « contact » a quitte la liste quand
     * la page est devenue une vraie page rendue par son controleur : le
     * charger() du montage tombait donc sur son propre abort(404), et l'ecran
     * repondait 404 a tout le monde, toujours. Mentions legales et politique
     * de confidentialite n'etaient plus modifiables nulle part.
     *
     * La valeur suit desormais la liste : en retirer une n'emporte plus
     * l'ecran entier.
     */
    public string $page = '';

    public string $titreFr = '';

    public string $titreEn = '';

    public string $contenuFr = '';

    public string $contenuEn = '';

    public bool $publie = true;

    public function mount(): void
    {
        $this->page = PageStatique::slugsEditables()[0];
        $this->langueActive = app()->getLocale();

        $this->chargerLesTextes(ReglageDeSection::where('slug', self::SECTION)->first());
        $this->charger();
    }

    public function updatedPage(): void
    {
        $this->charger();
    }

    /**
     * Charge la page choisie, apres avoir verifie QUI demande et LAQUELLE.
     *
     * Les deux controles manquaient ici, alors qu'enregistrer() en portait un.
     * Or cette methode ecrit aussi : firstOrCreate() cree la ligne absente.
     * Un compte sans droit d'edition pouvait donc, en fixant la propriete
     * publique $page depuis le navigateur, semer autant de lignes qu'il
     * voulait sur des slugs que le site public ne sert jamais.
     */
    protected function charger(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);
        abort_unless(in_array($this->page, PageStatique::slugsEditables(), true), 404);

        $page = PageStatique::firstOrCreate(['slug' => $this->page], [
            'titre_fr' => ucfirst($this->page),
            'titre_en' => ucfirst($this->page),
            'contenu_fr' => '',
            'contenu_en' => '',
        ]);
        $this->titreFr = $page->titre_fr;
        $this->titreEn = $page->titre_en;
        $this->contenuFr = (string) $page->contenu_fr;
        $this->contenuEn = (string) $page->contenu_en;
        $this->publie = $page->publie;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);
        abort_unless(in_array($this->page, PageStatique::slugsEditables(), true), 404);
        $this->validate([
            'titreFr' => ['required', 'string', 'max:190'],
            'titreEn' => ['nullable', 'string', 'max:190'],
            'contenuFr' => ['nullable', 'string', 'max:50000'],
            'contenuEn' => ['nullable', 'string', 'max:50000'],
        ] + $this->reglesDesTextes());
        PageStatique::updateOrCreate(['slug' => $this->page], [
            'titre_fr' => $this->titreFr, 'titre_en' => $this->titreEn,
            'contenu_fr' => $this->contenuFr, 'contenu_en' => $this->contenuEn,
            'publie' => $this->publie,
        ]);
        // Le gabarit est commun a toutes les pages editables : il vit dans sa
        // propre section, et non dans le contenu de celle qu'on enregistre.
        $section = ReglageDeSection::firstOrNew(['slug' => self::SECTION]);
        $this->poserLesTextes($section);
        $section->save();

        $this->dispatch('toast', message: __('Page enregistrée.'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.pages-statiques', [
            'description' => ['textes' => self::TEXTES_DU_GABARIT],
        ])->title(__('Pages éditables'));
    }
}
