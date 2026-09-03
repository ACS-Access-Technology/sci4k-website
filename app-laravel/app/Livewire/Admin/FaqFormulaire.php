<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Creation et edition d'une question de FAQ.
 *
 * La rubrique se choisit dans la liste, ou se cree ici meme. Sans cette
 * seconde possibilite il aurait fallu quitter le formulaire, creer la
 * rubrique ailleurs, puis revenir saisir la question — pour un classement
 * qui, la plupart du temps, se decide au moment ou l'on ecrit la question.
 */

#[Layout('layouts.app')]
class FaqFormulaire extends Component
{
    use RemplitParTraduction;

    /**
     * Le formulaire est-il rendu A L'INTERIEUR d'une liste ?
     *
     * Ce composant n'herite pas de FormulaireDeBloc : il porte donc lui-meme
     * ce que la classe mere apporte aux autres — en-tete masquee, « Annuler »
     * qui referme, et enregistrement qui previent la liste au lieu de
     * rediriger. Voir ServiceFormulaire::$embarque.
     */
    public bool $embarque = true;

    public ?QuestionFaq $question = null;

    /** Identifiant de la rubrique choisie, ou NOUVELLE_RUBRIQUE. */
    public string $rubriqueId = '';

    /** Valeur sentinelle de la liste deroulante : « créer une rubrique ». */
    public const NOUVELLE_RUBRIQUE = 'nouvelle';

    public string $nouvelleRubriqueFr = '';

    public string $nouvelleRubriqueEn = '';

    public string $questionFr = '';

    public string $questionEn = '';

    public string $reponseFr = '';

    public string $reponseEn = '';

    public bool $visible = true;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(?QuestionFaq $question = null): void
    {
        $this->langueActive = app()->getLocale();

        if (! $question?->exists) {
            return;
        }

        $this->question = $question;
        $this->rubriqueId = (string) $question->rubrique_id;
        $this->questionFr = $question->question_fr;
        $this->questionEn = $question->question_en;
        $this->reponseFr = $question->reponse_fr;
        $this->reponseEn = $question->reponse_en;
        $this->visible = (bool) $question->visible;
    }

    protected function rules(): array
    {
        return [
            'rubriqueId' => ['required', $this->creeUneRubrique()
                ? 'in:'.self::NOUVELLE_RUBRIQUE
                : 'exists:rubriques_faq,id'],
            // Exiges seulement quand on cree la rubrique, sans quoi le
            // formulaire refuserait toute question rangee dans une rubrique
            // existante.
            'nouvelleRubriqueFr' => [$this->creeUneRubrique() ? 'required' : 'nullable', 'string', 'max:190'],
            'nouvelleRubriqueEn' => [$this->creeUneRubrique() ? 'required' : 'nullable', 'string', 'max:190'],
            'questionFr' => ['required', 'string', 'max:500'],
            'questionEn' => ['required', 'string', 'max:500'],
            'reponseFr' => ['required', 'string'],
            'reponseEn' => ['required', 'string'],
        ];
    }

    /**
     * Champs traduisibles de la question, consommes par RemplitParTraduction.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        // « nouvelleRubrique » y figure pour que le nom d'une rubrique creee
        // ici beneficie du meme remplissage automatique que le reste. Quand
        // les deux cotes sont vides — cas ordinaire ou l'on choisit une
        // rubrique existante — le trait ne fait rien.
        return ['question', 'reponse', 'nouvelleRubrique'];
    }

    /** L'editeur a-t-il demande la creation d'une rubrique ? */
    public function creeUneRubrique(): bool
    {
        return $this->rubriqueId === self::NOUVELLE_RUBRIQUE;
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update. Une page laissee ouverte
        // par un editeur retrograde en lecteur continuerait sinon d'ecrire.
        abort_unless((bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);

        // Avant la validation : les champs remplis par traduction doivent
        // satisfaire les regles « required » comme s'ils avaient ete saisis.
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $rubrique = $this->creeUneRubrique()
            ? $this->creerLaRubrique()
            : RubriqueFaq::findOrFail($this->rubriqueId);

        $donnees = [
            'rubrique_id' => $rubrique->id,
            'question_fr' => $this->questionFr, 'question_en' => $this->questionEn,
            'reponse_fr' => $this->reponseFr, 'reponse_en' => $this->reponseEn,
            'visible' => $this->visible,
        ];

        if ($this->question) {
            $this->question->update($donnees);
        } else {
            // Le rang est relatif a la rubrique, pas a la table entiere : la
            // page publique groupe d'abord par rubrique puis trie par rang, et
            // un rang global ferait se croiser les questions de deux rubriques.
            $donnees['ordre'] = 1 + (int) QuestionFaq::where('rubrique_id', $rubrique->id)->max('ordre');
            $this->question = QuestionFaq::create($donnees);
        }

        $this->dispatch('toast', message: __('Question enregistrée.'), variant: 'success');

        // On ne redirige pas : on previent la liste, qui se referme.
        // Rediriger ferait quitter l'ecran de page au milieu d'une
        // modification — et depuis le retrait des ecrans par type de
        // contenu, il n'y a plus d'adresse a soi vers laquelle revenir.
        $this->dispatch('bloc-enregistre');
    }

    /**
     * Cree la rubrique demandee et la renvoie.
     *
     * Le slug vient de RubriqueFaq::slugLibrePour(), partage avec l'ecran
     * d'edition des rubriques : deux regles de nommage divergentes auraient
     * produit des slugs differents selon le point d'entree.
     */
    protected function creerLaRubrique(): RubriqueFaq
    {
        return RubriqueFaq::create([
            'slug' => RubriqueFaq::slugLibrePour($this->nouvelleRubriqueFr),
            'nom_fr' => $this->nouvelleRubriqueFr,
            'nom_en' => $this->nouvelleRubriqueEn,
            'ordre' => RubriqueFaq::rangSuivant(),
            'visible' => true,
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.faq-formulaire', [
            'rubriques' => RubriqueFaq::ordonnees()->get(),
            'creeUneRubrique' => $this->creeUneRubrique(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->question ? __('Modifier la question') : __('Nouvelle question'));
    }
}
