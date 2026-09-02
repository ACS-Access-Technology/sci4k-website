<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Biens immobiliers, geree depuis un seul ecran.
 *
 * Troisieme ecran de la refonte, meme principe que l'accueil et la
 * presentation : un module par bloc de la page publique, dans l'ordre ou le
 * visiteur les voit, et l'ancien ecran ENTIER embarque partout ou un module
 * pilote une collection.
 *
 * Trois modules seulement, la page en ayant trois : la banniere, le panneau de
 * filtres, et le catalogue lui-meme.
 *
 * Le module Filtres embarque l'ecran des referentiels. Ce n'est pas un
 * rapprochement de circonstance : les cinq familles qu'il edite SONT le
 * vocabulaire des filtres de /biens et des fiches de bien — types, zones,
 * tranches de pieces, tranches de surface, statuts juridiques. Les modifier
 * ailleurs obligeait a deviner ou l'on agissait.
 */
#[Layout('layouts.app')]
class PageBiens extends Component
{
    /**
     * Les trois modules de la page, dans l'ordre du site.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'banniere' => [
                'intitule' => __('Bannière'),
                'resume' => __('Étiquette, titre, accroche et image de fond.'),
                'section' => 'biens.page',
                'fond' => 'banniere-biens',
            ],
            'filtres' => [
                'intitule' => __('Filtres'),
                'resume' => __('Titre du panneau et vocabulaire des filtres.'),
                'section' => 'biens.filters',
                // Ni etiquette ni accroche : le panneau n'affiche que son
                // titre. Les proposer aurait ete offrir deux champs dont rien
                // n'aurait jamais montre le contenu.
                'champsEntete' => ['titre'],
            ],
            'catalogue' => [
                'intitule' => __('Catalogue'),
                'resume' => __('Les biens publiés, leurs photos et leurs fiches.'),
                'section' => null,
            ],
        ];
    }

    /** Module ouvert. Contraint a la liste ci-dessus. */
    public string $module = 'banniere';

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
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

        $slug = $this->moduleCourant()['section'] ?? null;
        abort_unless($slug !== null, 404);

        $this->validate();

        $section = ReglageDeSection::firstOrNew(['slug' => $slug]);
        $section->fill($this->entete);
        $section->save();

        $this->charger();
        $this->message = __('Module enregistré.');
        $this->dispatch('toast',
            message: __(':module enregistré.', ['module' => $this->moduleCourant()['intitule']]),
            variant: 'success');
    }

    /**
     * L'ecran complet a embarquer dans le module ouvert, s'il y en a un.
     *
     * @return array{composant: string, intitule: string}|null
     */
    public function ecranEmbarque(): ?array
    {
        return [
            'filtres' => ['composant' => 'admin.referentiels', 'intitule' => __('Vocabulaire des filtres')],
            'catalogue' => ['composant' => 'admin.bien-liste', 'intitule' => __('Biens')],
        ][$this->module] ?? null;
    }

    public function fondDuModule(): ?ImageDeFond
    {
        $slug = $this->moduleCourant()['fond'] ?? null;

        return $slug ? ImageDeFond::where('slug', $slug)->first() : null;
    }

    public function render(): View
    {
        return view('livewire.admin.page-biens', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'fond' => $this->fondDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Biens immobiliers'));
    }
}
