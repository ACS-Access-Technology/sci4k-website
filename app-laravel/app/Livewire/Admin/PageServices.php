<?php

namespace App\Livewire\Admin;

use App\Models\Encart;
use App\Livewire\Concerns\PorteDesImagesDeFond;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Services, geree depuis un seul ecran.
 *
 * Quatriieme ecran de la refonte, meme principe que les trois precedents : un
 * module par bloc de la page publique, dans l'ordre ou le visiteur les voit,
 * et l'ancien ecran ENTIER embarque partout ou un module pilote une
 * collection.
 */
#[Layout('layouts.app')]
class PageServices extends Component
{
    use PorteDesImagesDeFond;

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
                'section' => 'services.page',
                'fond' => 'banniere-services',
            ],
            'services' => [
                'intitule' => __('Services'),
                'resume' => __('Les prestations détaillées et leurs visuels.'),
                // Pas d'en-tete : la page n'affiche rien au-dessus de la
                // grille des services.
                'section' => null,
                // Le fond des six tuiles. Il ne vient PAS de la fiche du
                // service mais de la table des images de fond, sous la classe
                // .service-bg-{slug} : c'est elle qui l'emporte dans le
                // gabarit. Ces six images n'etaient atteignables nulle part
                // depuis le retrait de l'ecran « Images de fond ».
                'fonds' => [
                    'service-foncier', 'service-construction', 'service-gestion',
                    'service-achat', 'service-vente', 'service-administration',
                ],
            ],
            'processus' => [
                'intitule' => __('Processus'),
                'resume' => __("En-tête du bloc, étapes et mise en page."),
                'section' => 'services.process',
                'fond' => 'processus',
                // La mise en page vit dans les options de la section. Elle
                // etait reglable depuis le panneau de l'editeur des etapes,
                // que l'embarquement masque : sans cette reprise, elle
                // deviendrait inatteignable.
                'options' => [
                    'mise_en_page' => [
                        'intitule' => 'Mise en page',
                        'choix' => ['frise' => 'Frise', 'liste' => 'Liste'],
                        'defaut' => 'frise',
                    ],
                ],
            ],
            'annonce' => [
                'intitule' => __('Annonce'),
                'resume' => __('Encart promotionnel affiché après le processus.'),
                'section' => null,
                'encart' => 'services.annonce',
            ],
        ];
    }

    public string $module = 'banniere';

    public string $langueActive = 'fr';

    public array $entete = [];

    /** Options d'apparence du module ouvert. */
    public array $options = [];

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
        $this->options = [];

        $description = $this->moduleCourant();
        $slug = $description['section'] ?? null;

        if (! $slug) {
            return;
        }

        $section = ReglageDeSection::where('slug', $slug)->first();

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
            $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
        }

        foreach ($description['options'] ?? [] as $nom => $decrit) {
            $this->options[$nom] = (string) ($section?->option($nom, $decrit['defaut']) ?? $decrit['defaut']);
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        // Une option n'accepte que les valeurs qu'elle propose : une valeur
        // forgee depuis le navigateur ferait retomber la page sur son defaut
        // sans que personne ne comprenne pourquoi.
        foreach ($this->moduleCourant()['options'] ?? [] as $nom => $decrit) {
            $regles['options.'.$nom] = ['required', Rule::in(array_keys($decrit['choix']))];
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

        // poserOptions() POSE les options mais n'enregistre pas : sans ce
        // second save(), la mise en page se perdrait sans un mot.
        if ($this->options !== []) {
            $section->poserOptions($this->options);
            $section->save();
        }

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
        // L'encart embarque son PROPRE formulaire : lui seul sait televerser
        // une image, la remplacer et faire le menage de l'ancienne.
        if ($slug = $this->moduleCourant()['encart'] ?? null) {
            return [
                'composant' => 'admin.encart-formulaire',
                'intitule' => $this->moduleCourant()['intitule'],
                'parametres' => [
                    'element' => Encart::firstOrCreate(['slug' => $slug]),
                    'embarque' => true,
                ],
            ];
        }

        return [
            'services' => ['composant' => 'admin.service-liste', 'intitule' => __('Services')],
            'processus' => ['composant' => 'admin.etape-processus-ensemble', 'intitule' => __('Étapes du processus')],
        ][$this->module] ?? null;
    }


    public function render(): View
    {
        return view('livewire.admin.page-services', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Services'));
    }
}
