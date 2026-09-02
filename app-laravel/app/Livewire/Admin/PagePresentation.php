<?php

namespace App\Livewire\Admin;

use App\Models\ImageDeFond;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * La page Presentation du site, geree depuis un seul ecran.
 *
 * Deuxieme ecran de la refonte, batie sur le meme principe que « Pages du site
 * → Accueil » : un module par bloc de la page publique, dans l'ordre ou le
 * visiteur les voit, et l'ancien ecran ENTIER embarque partout ou un module
 * pilote une collection.
 *
 * L'ordre des modules n'est pas modifiable — il est fixe dans
 * presentation.blade.php — et l'ecran ne propose donc aucune poignee de
 * deplacement. Voir PageAccueil pour le raisonnement complet.
 */
#[Layout('layouts.app')]
class PagePresentation extends Component
{
    /**
     * Les cinq modules de la page, dans l'ordre du site.
     *
     * @return array<string, array<string, mixed>>
     */
    public function modules(): array
    {
        return [
            'banniere' => [
                'intitule' => __('Bannière'),
                'resume' => __('Étiquette, titre, accroche et image de fond.'),
                'section' => 'about.page',
                'fond' => 'banniere-presentation',
            ],
            'presentation' => [
                'intitule' => __('Présentation générale'),
                'resume' => __('Texte de présentation, deux atouts et illustration.'),
                'section' => 'about.overview',
                'fond' => 'presentation-apercu',
                'contenu' => true,
                'atouts' => true,
                // Pas d'accroche, pour la meme raison que le mot du directeur :
                // le gabarit public n'en affiche pas ici. Le texte du bloc
                // passe par « contenu ».
                'champsEntete' => ['etiquette', 'titre'],
            ],
            'directeur' => [
                'intitule' => __('Mot du Directeur'),
                'resume' => __('Texte, portrait et compteur mis en avant.'),
                'section' => 'about.dg',
                'fond' => 'presentation-directeur',
                'contenu' => true,
                'compteur' => true,
                // Pas d'accroche : ce bloc porte une etiquette, un titre et un
                // corps de texte. Le gabarit public n'affiche jamais de chapo
                // ici — le champ n'aurait rien montre.
                'champsEntete' => ['etiquette', 'titre'],
            ],
            'valeurs' => [
                'intitule' => __('Valeurs'),
                'resume' => __('En-tête du bloc, valeurs affichées et image de fond.'),
                'section' => 'about.values',
                'fond' => 'valeurs',
            ],
            'equipe' => [
                'intitule' => __('Équipe'),
                'resume' => __("En-tête du bloc et membres de l'équipe."),
                'section' => 'about.team',
            ],
        ];
    }

    /** Module ouvert. Contraint a la liste ci-dessus. */
    public string $module = 'banniere';

    /** Langue du CONTENU saisi, sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Champs de l'en-tete de section, corps de texte compris. */
    public array $entete = [];

    /** Les deux atouts de la presentation generale. */
    public array $atouts = [];

    /** Le compteur du mot du directeur. */
    public array $compteur = [];

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
        $description = $this->moduleCourant();
        $this->entete = [];
        $this->atouts = [];
        $this->compteur = [];

        $section = ReglageDeSection::where('slug', $description['section'])->first();

        foreach (['etiquette', 'titre', 'chapo', 'contenu'] as $champ) {
            $this->entete[$champ.'_fr'] = (string) ($section?->{$champ.'_fr'} ?? '');
            $this->entete[$champ.'_en'] = (string) ($section?->{$champ.'_en'} ?? '');
        }

        // Le corps de texte a longtemps loge dans « chapo ». Sur une base ou la
        // migration de recopie n'a pas encore tourne, on le montre quand meme,
        // sans quoi l'editeur croirait son texte perdu.
        if (($description['contenu'] ?? false) && $this->entete['contenu_fr'] === '') {
            $this->entete['contenu_fr'] = $this->entete['chapo_fr'];
            $this->entete['contenu_en'] = $this->entete['chapo_en'];
        }

        if ($description['atouts'] ?? false) {
            foreach (['atout1', 'atout2'] as $atout) {
                foreach (['titre', 'texte'] as $champ) {
                    $this->atouts[$atout.'_'.$champ.'_fr'] = (string) ($section?->option($atout.'_'.$champ.'_fr') ?? '');
                    $this->atouts[$atout.'_'.$champ.'_en'] = (string) ($section?->option($atout.'_'.$champ.'_en') ?? '');
                }
            }
        }

        if ($description['compteur'] ?? false) {
            $this->compteur = [
                'valeur' => (string) ($section?->option('compteur_valeur') ?? ''),
                'libelle_fr' => (string) ($section?->option('compteur_libelle_fr') ?? ''),
                'libelle_en' => (string) ($section?->option('compteur_libelle_en') ?? ''),
            ];
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        $regles['entete.contenu_fr'] = ['nullable', 'string', 'max:20000'];
        $regles['entete.contenu_en'] = ['nullable', 'string', 'max:20000'];

        foreach (['atout1', 'atout2'] as $atout) {
            $regles['atouts.'.$atout.'_titre_fr'] = ['nullable', 'string', 'max:120'];
            $regles['atouts.'.$atout.'_titre_en'] = ['nullable', 'string', 'max:120'];
            $regles['atouts.'.$atout.'_texte_fr'] = ['nullable', 'string', 'max:500'];
            $regles['atouts.'.$atout.'_texte_en'] = ['nullable', 'string', 'max:500'];
        }

        // Un compteur s'anime : une valeur non numerique le laisserait a zero
        // sans rien dire.
        $regles['compteur.valeur'] = ['nullable', 'integer', 'min:0', 'max:100000'];
        $regles['compteur.libelle_fr'] = ['nullable', 'string', 'max:190'];
        $regles['compteur.libelle_en'] = ['nullable', 'string', 'max:190'];

        return $regles;
    }

    protected function validationAttributes(): array
    {
        return ['compteur.valeur' => __('la valeur du compteur')];
    }

    public function enregistrer(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $this->validate();

        $description = $this->moduleCourant();
        $section = ReglageDeSection::firstOrNew(['slug' => $description['section']]);
        $section->fill($this->entete);
        $section->save();

        // poserOptions() POSE les options sur le modele mais n'enregistre pas :
        // sans ce second save(), les atouts et le compteur se perdaient sans un
        // mot. Meme piege que sur l'apparence de la banderole.
        $options = [];

        if ($description['atouts'] ?? false) {
            $options += $this->atouts;
        }

        if ($description['compteur'] ?? false) {
            $options += [
                'compteur_valeur' => $this->compteur['valeur'] ?? '',
                'compteur_libelle_fr' => $this->compteur['libelle_fr'] ?? '',
                'compteur_libelle_en' => $this->compteur['libelle_en'] ?? '',
            ];
        }

        if ($options !== []) {
            $section->poserOptions($options);
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
     * @return array{composant: string, intitule: string}|null
     */
    public function ecranEmbarque(): ?array
    {
        return [
            'valeurs' => ['composant' => 'admin.valeur-ensemble', 'intitule' => __('Valeurs affichées')],
            'equipe' => ['composant' => 'admin.membre-equipe-liste', 'intitule' => __("Membres de l'équipe")],
        ][$this->module] ?? null;
    }

    /** Le fond du module ouvert, s'il en a un. */
    public function fondDuModule(): ?ImageDeFond
    {
        $slug = $this->moduleCourant()['fond'] ?? null;

        return $slug ? ImageDeFond::where('slug', $slug)->first() : null;
    }

    public function render(): View
    {
        return view('livewire.admin.page-presentation', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'fond' => $this->fondDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page Présentation'));
    }
}
