<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Creation et edition d'une question de FAQ.
 *
 * Contrairement aux services, la creation est permise : ajouter une question
 * ne touche pas la structure des pages publiques.
 */
#[Layout('layouts.app')]
class FaqFormulaire extends Component
{
    use RemplitParTraduction;

    public ?QuestionFaq $question = null;

    public string $serviceId = '';

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
        $this->serviceId = (string) $question->service_id;
        $this->questionFr = $question->question_fr;
        $this->questionEn = $question->question_en;
        $this->reponseFr = $question->reponse_fr;
        $this->reponseEn = $question->reponse_en;
        $this->visible = (bool) $question->visible;
    }

    protected function rules(): array
    {
        return [
            'serviceId' => ['required', 'exists:services,id'],
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
        return ['question', 'reponse'];
    }

    public function enregistrer(): void
    {
        // Avant la validation : les champs remplis par traduction doivent
        // satisfaire les regles « required » comme s'ils avaient ete saisis.
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $donnees = [
            'service_id' => $this->serviceId,
            'question_fr' => $this->questionFr, 'question_en' => $this->questionEn,
            'reponse_fr' => $this->reponseFr, 'reponse_en' => $this->reponseEn,
            'visible' => $this->visible,
        ];

        if ($this->question) {
            $this->question->update($donnees);
        } else {
            // Une question creee se range en fin de son groupe.
            $donnees['ordre'] = 1 + (int) QuestionFaq::where('service_id', $this->serviceId)->max('ordre');
            $this->question = QuestionFaq::create($donnees);
        }

        session()->flash('message', __('Question enregistrée.'));
        $this->redirectRoute('admin.faq.liste');
    }

    public function render(): View
    {
        return view('livewire.admin.faq-formulaire', [
            'services' => Service::ordonnees()->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->question ? __('Modifier la question') : __('Nouvelle question'));
    }
}
