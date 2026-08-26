<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\Categorie;
use App\Models\Service;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/*
 * Edition d'un service.
 *
 * Deux mecanismes de langue se croisent, et ils sont independants : le bouton
 * FR/EN pilote l'INTERFACE, les onglets pilotent le CONTENU saisi. Leur seul
 * point de rencontre est l'etat initial. C'est le motif retenu au lot 1 pour
 * ArticleFormulaire.
 *
 * Le formulaire cree et modifie. Le slug, lui, ne se saisit qu'a la creation :
 * il porte a la fois l'ancre du pied de page (#foncier), l'identifiant de la
 * tuile et la classe CSS du fond (service-bg-foncier). Le changer apres coup
 * casserait les trois d'un coup, sans que rien ne le signale avant qu'un
 * visiteur ne tombe sur une tuile sans image ou un lien mort.
 */
#[Layout('layouts.app')]
class ServiceFormulaire extends Component
{
    use RemplitParTraduction;
    use WithFileUploads;

    public ?Service $service = null;

    /** Identifiant d'adresse, fige des la creation. */
    public string $slug = '';

    /** Fichier choisi dans le navigateur, pas encore enregistre. */
    public $image = null;

    /** Chemin de l'image actuelle, tel qu'il sera ecrit en base. */
    public ?string $imageActuelle = null;

    /** L'editeur a demande le retrait de l'image existante. */
    public bool $imageARetirer = false;

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

    public function mount(?Service $service = null): void
    {
        $this->langueActive = app()->getLocale();

        // Creation : rien a precharger, les valeurs par defaut des proprietes
        // suffisent. Le test `->exists` distingue le modele vide que Laravel
        // injecte quand la route n'a pas de parametre.
        if (! $service?->exists) {
            return;
        }

        $this->service = $service;
        $this->imageActuelle = $service->image_source;
        $this->slug = $service->slug;

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

    /** Le service est-il en cours de creation ? */
    public function estCreation(): bool
    {
        return ! $this->service?->exists;
    }

    protected function rules(): array
    {
        return [
            // Le slug n'est valide qu'a la creation : ensuite il est fige, et
            // `enregistrer()` ne l'ecrit plus. Le format est impose parce que
            // le slug devient une ancre publique et un nom de classe CSS : une
            // majuscule, un espace ou un accent y produirait un lien mort et
            // un fond absent, que rien ne signalerait avant le premier clic.
            'slug' => $this->estCreation()
                ? ['required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:services,slug']
                : ['nullable'],
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
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function messages(): array
    {
        return [
            'slug.regex' => __("L'identifiant d'adresse n'accepte que des minuscules, des chiffres et des traits d'union, par exemple : gestion-location."),
            'slug.unique' => __('Un service utilise déjà cet identifiant d’adresse.'),
        ];
    }

    /** Valide le fichier des son choix, sans attendre l'enregistrement. */
    public function updatedImage(): void
    {
        $this->validateOnly('image');
    }

    /**
     * Retire l'image. Le fichier n'est efface qu'a l'enregistrement : tant
     * que l'editeur n'a pas valide, il peut encore changer d'avis.
     */
    public function supprimerImage(): void
    {
        $this->image = null;
        $this->imageARetirer = true;
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

        $image = $this->resoudreImage();

        $donnees = [
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
            'image_source' => $image,
        ];

        if ($this->estCreation()) {
            // Le slug n'est ecrit qu'ici, et le rang place le service en fin
            // de liste plutot qu'en tete (cf. CollectionOrdonnable).
            $this->service = Service::create($donnees + [
                'slug' => $this->slug,
                'ordre' => Service::rangSuivant(),
            ]);
        } else {
            $this->service->update($donnees);
        }

        session()->flash('message', __('Service enregistré.'));
        $this->redirectRoute('admin.services.liste');
    }

    /**
     * Determine le chemin d'image a enregistrer, et fait le menage.
     *
     * Meme logique que ArticleFormulaire::resoudreCouverture() : l'ancien
     * fichier n'est efface que s'il vient de l'administration. Les visuels
     * repris du site statique vivent dans public/images/, deposes par
     * tools/sync-frontoffice.sh depuis frontoffice/ : les effacer detruirait
     * la source du site public.
     */
    protected function resoudreImage(): ?string
    {
        $ancienne = $this->imageActuelle;

        if ($this->image) {
            $chemin = $this->image->store('services', 'public');
            $this->effacerSiTeleversee($ancienne);
            $this->imageActuelle = 'storage/'.$chemin;

            return $this->imageActuelle;
        }

        if ($this->imageARetirer) {
            $this->effacerSiTeleversee($ancienne);
            $this->imageActuelle = null;
            $this->imageARetirer = false;

            return null;
        }

        return $ancienne;
    }

    protected function effacerSiTeleversee(?string $chemin): void
    {
        if ($chemin && str_starts_with($chemin, Service::DOSSIER_COUVERTURES.'/')) {
            Storage::disk('public')->delete(substr($chemin, strlen('storage/')));
        }
    }

    public function render(): View
    {
        return view('livewire.admin.service-formulaire', [
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
            'estCreation' => $this->estCreation(),
        ])->title($this->estCreation() ? __('Nouveau service') : __('Modifier le service'));
    }
}
