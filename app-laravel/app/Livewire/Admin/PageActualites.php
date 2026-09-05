<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesImagesDeFond;
use App\Livewire\Concerns\PorteUnEnteteDeSection;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Actualites, geree depuis un seul ecran.
 *
 * Cinquieme ecran de la refonte, meme principe que les quatre precedents : un
 * module par bloc de la page publique, dans l'ordre ou le visiteur les voit,
 * et l'ancien ecran ENTIER embarque partout ou un module pilote une
 * collection.
 *
 * La barre de recherche de /actualites filtre cote navigateur, sur le texte,
 * la date et la CATEGORIE. Seule cette derniere est de la donnee : le champ
 * libre et les deux dates n'ont rien a regler. Le module Filtres ne porte donc
 * que les categories — qui n'etaient modifiables nulle part jusqu'ici.
 */
#[Layout('layouts.app')]
class PageActualites extends Component
{
    use PorteDesImagesDeFond;
    use PorteUnEnteteDeSection;

    /**
     * Les quatre modules de la page, dans l'ordre du site.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'banniere' => [
                'intitule' => __('Bannière'),
                'resume' => __('Étiquette, titre, accroche et image de fond.'),
                'section' => 'news.page',
                'fond' => 'banniere-actualites',
            ],
            'filtres' => [
                'intitule' => __('Filtres'),
                'resume' => __('Les catégories proposées au visiteur.'),
                // Le formulaire de recherche n'affiche aucun en-tete : lui
                // offrir un titre et une accroche aurait fait saisir un texte
                // que rien ne rend.
                'section' => null,
            ],
            'articles' => [
                'intitule' => __('Articles'),
                'resume' => __('Les articles publiés, leurs couvertures et leurs fiches.'),
                // La grille n'a pas davantage d'en-tete de section.
                'section' => null,
            ],
            'commentaires' => [
                'intitule' => __('Commentaires'),
                'resume' => __('Ce que les lecteurs écrivent sous les articles.'),
                // Les commentaires ne sont pas un bloc de la page Actualités
                // mais de chaque article. Ils sont ici parce que c'est ou
                // l'editeur les cherchera, et parce qu'aucun autre ecran ne
                // porte les articles.
                'section' => null,
            ],
            'appel' => [
                'intitule' => __('Appel à l’action'),
                'resume' => __('Le bloc de contact placé sous la liste.'),
                'section' => 'news.cta',
                // Le bloc n'affiche ni etiquette ni image : le libelle du
                // bouton est fixe et mene a la page Contact.
                'champsEntete' => ['titre', 'chapo'],
            ],
        ];
    }

    public string $module = 'banniere';

    public string $langueActive = 'fr';

    public array $entete = [];

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

    public function moduleCourant(): array
    {
        return $this->modules()[$this->module] ?? $this->modules()['banniere'];
    }

    protected function charger(): void
    {
        $this->entete = [];

        $slug = $this->moduleCourant()['section'] ?? null;

        if (! $slug) {
            return;
        }

        $section = ReglageDeSection::where('slug', $slug)->first();

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
            $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        return $regles;
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $description = $this->moduleCourant();
        $slug = $description['section'] ?? null;
        abort_unless($slug !== null, 404);

        $this->validate();

        $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
        // Les cles viennent du navigateur : seules celles que
        // l'ecran declare sont ecrites. Voir le trait.
        $section->fill($this->enteteFiltree());
        $section->save();

        $this->charger();
        $this->message = __('Module enregistré.');
        $this->dispatch('toast',
            message: __(':module enregistré.', ['module' => $description['intitule']]),
            variant: 'success');
    }

    /**
     * L'ecran complet a embarquer dans le module ouvert, s'il y en a un.
     *
     * @return array{composant: string, intitule: string, parametres?: array}|null
     */
    public function ecranEmbarque(): ?array
    {
        return [
            'filtres' => ['composant' => 'admin.categorie-ensemble', 'intitule' => __("Catégories d'articles")],
            'articles' => ['composant' => 'admin.article-liste', 'intitule' => __('Articles')],
            'commentaires' => ['composant' => 'admin.commentaire-liste', 'intitule' => __('Commentaires')],
        ][$this->module] ?? null;
    }


    public function render(): View
    {
        return view('livewire.admin.page-actualites', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Actualités'));
    }
}
