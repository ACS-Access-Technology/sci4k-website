<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\PorteDesTextesDeBloc;
use App\Livewire\Concerns\PorteDesImagesDeFond;
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
    use PorteDesImagesDeFond;
    use PorteDesTextesDeBloc;

    /**
     * Les textes du formulaire « poser une question ».
     *
     * Chaque entree porte son intitule dans le backoffice et la valeur que la
     * page publique affiche tant que rien n'est saisi — le meme texte que la
     * vue, pour qu'une base vierge rende exactement ce qu'elle rendait avant.
     *
     * L'ordre est celui du bloc sur le site : l'editeur relit son ecran de
     * haut en bas comme il relirait la page.
     */
    public const TEXTES_DU_FORMULAIRE = [
        'libelle_nom' => ['intitule' => 'Libellé du champ « nom »', 'defaut' => 'Nom complet *'],
        'exemple_nom' => ['intitule' => 'Exemple dans le champ « nom »', 'defaut' => 'Ex: Jean Kouassi'],
        'libelle_email' => ['intitule' => 'Libellé du champ « email »', 'defaut' => 'Adresse Email *'],
        'exemple_email' => ['intitule' => 'Exemple dans le champ « email »', 'defaut' => 'j.kouassi@email.com'],
        'libelle_question' => ['intitule' => 'Libellé du champ « question »', 'defaut' => 'Votre question *'],
        'exemple_question' => ['intitule' => 'Exemple dans le champ « question »', 'defaut' => 'Écrivez votre question ici...'],
        'libelle_bouton' => ['intitule' => 'Libellé du bouton', 'defaut' => 'Envoyer ma question →'],
        'confirmation' => [
            'intitule' => 'Message de confirmation',
            'defaut' => "✓ Votre question est prête : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour la transmettre à SCI4K.",
            'long' => true,
        ],
    ];

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
                'resume' => __('Tous les textes du bloc placé sous les questions.'),
                'section' => 'faq.ask',
                // Le bloc n'affiche ni etiquette ni image : le formulaire
                // ouvre une conversation WhatsApp, sans rien ecrire en base.
                'champsEntete' => ['titre', 'chapo'],
                // Les libelles du formulaire etaient ecrits en dur dans la vue
                // et traduits par __() : aucun ecran ne les a jamais exposes.
                // Ils vivent maintenant dans le sac d'options de la section,
                // sous des cles suffixees par langue — neuf textes ne valaient
                // pas neuf paires de colonnes.
                'textes' => self::TEXTES_DU_FORMULAIRE,
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
        $this->textes = [];

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

        $this->chargerLesTextes($section);
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (['etiquette', 'titre', 'chapo'] as $champ) {
            $regles['entete.'.$champ.'_fr'] = ['nullable', 'string', 'max:500'];
            $regles['entete.'.$champ.'_en'] = ['nullable', 'string', 'max:500'];
        }

        return $regles + $this->reglesDesTextes();
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « textes.libelle_bouton_fr ».
     */
    protected function validationAttributes(): array
    {
        return $this->intitulesDesTextes();
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

        // poserLesTextes() ne retient que les cles declarees par le module et
        // POSE sans enregistrer : le save() qui suit est ce qui les porte en
        // base.
        $this->poserLesTextes($section);

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


    public function render(): View
    {
        return view('livewire.admin.page-faq', [
            'modules' => $this->modules(),
            'description' => $this->moduleCourant(),
            'ecranEmbarque' => $this->ecranEmbarque(),
            'images' => $this->imagesDuModule(),
            'peutEcrire' => $this->peutEcrire(),
        ])->title(__('Page FAQ'));
    }
}
