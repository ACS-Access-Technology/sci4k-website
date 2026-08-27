<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\RubriqueFaq;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/*
 * Creation et edition d'une rubrique de la FAQ.
 *
 * Une rubrique se cree le plus souvent depuis le formulaire d'une question,
 * la ou le besoin apparait. Cet ecran sert a ce que la creation a la volee ne
 * couvre pas : renommer, et corriger une saisie.
 *
 * Le slug n'est pas modifiable une fois pose. Il ne sert aujourd'hui qu'a
 * relier les rubriques reprises des six services, mais c'est justement ce lien
 * que la migration utilise pour rendre les questions a leur service en cas de
 * retour en arriere : le changer romprait le chemin de retour.
 */
#[Layout('layouts.app')]
class RubriqueFaqFormulaire extends Component
{
    use RemplitParTraduction;

    /**
     * Verrouille : Livewire expose au navigateur toute propriete publique, et
     * celle-ci designe la ligne que l'enregistrement va ecrire.
     */
    #[Locked]
    public ?RubriqueFaq $rubrique = null;

    public string $nomFr = '';

    public string $nomEn = '';

    public bool $visible = true;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(?RubriqueFaq $rubrique = null): void
    {
        $this->langueActive = app()->getLocale();

        if (! $rubrique?->exists) {
            return;
        }

        $this->rubrique = $rubrique;
        $this->nomFr = $rubrique->nom_fr;
        $this->nomEn = $rubrique->nom_en;
        $this->visible = (bool) $rubrique->visible;
    }

    protected function rules(): array
    {
        return [
            'nomFr' => ['required', 'string', 'max:190'],
            'nomEn' => ['required', 'string', 'max:190'],
        ];
    }

    /**
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return ['nom'];
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update, si bien qu'une page laissee
        // ouverte par un editeur retrograde continuerait d'enregistrer.
        abort_unless((bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);

        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $donnees = [
            'nom_fr' => $this->nomFr,
            'nom_en' => $this->nomEn,
            'visible' => $this->visible,
        ];

        if ($this->rubrique) {
            $this->rubrique->update($donnees);
        } else {
            $this->rubrique = RubriqueFaq::create($donnees + [
                'slug' => RubriqueFaq::slugLibrePour($this->nomFr),
                'ordre' => RubriqueFaq::rangSuivant(),
            ]);
        }

        session()->flash('message', __('Rubrique enregistrée.'));
        $this->redirectRoute('admin.rubriques-faq.liste');
    }

    public function render(): View
    {
        return view('livewire.admin.rubrique-faq-formulaire', [
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
            'estCreation' => ! $this->rubrique?->exists,
        ])->title($this->rubrique?->exists ? __('Modifier la rubrique') : __('Nouvelle rubrique'));
    }
}
