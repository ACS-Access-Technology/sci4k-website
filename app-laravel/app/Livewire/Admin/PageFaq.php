<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page FAQ, geree depuis un seul ecran.
 *
 * Sixieme ecran de la refonte, meme principe que les cinq precedents : un
 * module par bloc de la page publique, dans l'ordre ou le visiteur les voit,
 * et l'ancien ecran ENTIER embarque partout ou un module pilote une
 * collection.
 *
 * Les rubriques ont leur propre module, avant les questions : sur le site,
 * le titre de chaque groupe EST le nom de la rubrique, et masquer une
 * rubrique retire aussi ses questions. Les fondre dans le module des
 * questions aurait cache une decision qui gouverne la page entiere.
 *
 * Le formulaire « poser une question » n'a rien a regler : il n'ecrit pas en
 * base et ouvre une conversation WhatsApp depuis assets/main.js. Seuls son
 * titre et son accroche viennent de la section faq.ask.
 */
#[Layout('layouts.app')]
class PageFaq extends Component
{
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
                'section' => 'faq.page',
                'fond' => 'banniere-faq',
            ],
            'rubriques' => [
                'intitule' => __('Rubriques'),
                'resume' => __('Les titres de groupe, leur ordre et leur visibilité.'),
                // Le titre de groupe EST le nom de la rubrique : la page
                // n'affiche aucun en-tete au-dessus des groupes.
                'section' => null,
            ],
            'questions' => [
                'intitule' => __('Questions'),
                'resume' => __('Les questions et leurs réponses, rubrique par rubrique.'),
                'section' => null,
            ],
            'demande' => [
                'intitule' => __('Poser une question'),
                'resume' => __('Titre et accroche du bloc placé sous les questions.'),
                'section' => 'faq.ask',
                // Le bloc n'affiche ni etiquette ni image : le formulaire
                // ouvre une conversation WhatsApp, sans rien ecrire en base.
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
        $section->fill($this->entete);
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
            'rubriques' => ['composant' => 'admin.rubrique-faq-liste', 'intitule' => __('Rubriques de la FAQ')],
            'questions' => ['composant' => 'admin.faq-liste', 'intitule' => __('Questions')],
        ][$this->module] ?? null;
    }

    public function fondDuModule(): ?ImageDeFond
    {
        $slug = $this->moduleCourant()['fond'] ?? null;

        return $slug ? ImageDeFond::where('slug', $slug)->first() : null;
    }

    public function render(): View
    {
        return view('livewire.admin.page-faq', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'fond' => $this->fondDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page FAQ'));
    }
}
