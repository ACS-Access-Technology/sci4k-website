<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\Categorie;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * Edition d'un service.
 *
 * Deux mecanismes de langue se croisent, et ils sont independants : le bouton
 * FR/EN pilote l'INTERFACE, les onglets pilotent le CONTENU saisi. Leur seul
 * point de rencontre est l'etat initial. C'est le motif retenu au lot 1 pour
 * ArticleFormulaire.
 *
 * Les six services correspondent aux six metiers du site : ce formulaire ne
 * fait que modifier, jamais creer ni supprimer (cf. ServiceListe).
 */
#[Layout('layouts.app')]
class ServiceFormulaire extends Component
{
    use RemplitParTraduction;

    public Service $service;

    public string $nomFr = '';

    public string $nomEn = '';

    public string $accrocheFr = '';

    public string $accrocheEn = '';

    public string $descriptionFr = '';

    public string $descriptionEn = '';

    public string $atout1Fr = '';

    public string $atout1En = '';

    public string $atout2Fr = '';

    public string $atout2En = '';

    public string $atout3Fr = '';

    public string $atout3En = '';

    public string $libelleBoutonFr = '';

    public string $libelleBoutonEn = '';

    public string $categorieId = '';

    public bool $visible = true;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    public function mount(Service $service): void
    {
        $this->service = $service;
        $this->langueActive = app()->getLocale();

        $this->nomFr = $service->nom_fr;
        $this->nomEn = $service->nom_en;
        $this->accrocheFr = $service->accroche_fr;
        $this->accrocheEn = $service->accroche_en;
        $this->descriptionFr = $service->description_fr;
        $this->descriptionEn = $service->description_en;
        $this->atout1Fr = $service->atout1_fr ?? '';
        $this->atout1En = $service->atout1_en ?? '';
        $this->atout2Fr = $service->atout2_fr ?? '';
        $this->atout2En = $service->atout2_en ?? '';
        $this->atout3Fr = $service->atout3_fr ?? '';
        $this->atout3En = $service->atout3_en ?? '';
        $this->libelleBoutonFr = $service->libelle_bouton_fr ?? '';
        $this->libelleBoutonEn = $service->libelle_bouton_en ?? '';
        $this->categorieId = (string) $service->categorie_id;
        $this->visible = (bool) $service->visible;
    }

    protected function rules(): array
    {
        return [
            'nomFr' => ['required', 'string', 'max:190'],
            'nomEn' => ['required', 'string', 'max:190'],
            'accrocheFr' => ['required', 'string', 'max:255'],
            'accrocheEn' => ['required', 'string', 'max:255'],
            'descriptionFr' => ['required', 'string'],
            'descriptionEn' => ['required', 'string'],
            'atout1Fr' => ['nullable', 'string', 'max:190'],
            'atout1En' => ['nullable', 'string', 'max:190'],
            'atout2Fr' => ['nullable', 'string', 'max:190'],
            'atout2En' => ['nullable', 'string', 'max:190'],
            'atout3Fr' => ['nullable', 'string', 'max:190'],
            'atout3En' => ['nullable', 'string', 'max:190'],
            'libelleBoutonFr' => ['nullable', 'string', 'max:120'],
            'libelleBoutonEn' => ['nullable', 'string', 'max:120'],
            'categorieId' => ['required', 'exists:categories,id'],
        ];
    }

    /**
     * Champs traduisibles du service, consommes par RemplitParTraduction.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return ['nom', 'accroche', 'description', 'atout1', 'atout2', 'atout3', 'libelleBouton'];
    }

    public function enregistrer(): void
    {
        // Avant la validation : les champs remplis par traduction doivent
        // satisfaire les regles « required » comme s'ils avaient ete saisis.
        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $this->service->update([
            'nom_fr' => $this->nomFr, 'nom_en' => $this->nomEn,
            'accroche_fr' => $this->accrocheFr, 'accroche_en' => $this->accrocheEn,
            'description_fr' => $this->descriptionFr, 'description_en' => $this->descriptionEn,
            'atout1_fr' => $this->atout1Fr ?: null, 'atout1_en' => $this->atout1En ?: null,
            'atout2_fr' => $this->atout2Fr ?: null, 'atout2_en' => $this->atout2En ?: null,
            'atout3_fr' => $this->atout3Fr ?: null, 'atout3_en' => $this->atout3En ?: null,
            'libelle_bouton_fr' => $this->libelleBoutonFr ?: null,
            'libelle_bouton_en' => $this->libelleBoutonEn ?: null,
            'categorie_id' => $this->categorieId,
            'visible' => $this->visible,
        ]);

        session()->flash('message', __('Service enregistré.'));
        $this->redirectRoute('admin.services.liste');
    }

    public function render(): View
    {
        return view('livewire.admin.service-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title(__('Modifier le service'));
    }
}
