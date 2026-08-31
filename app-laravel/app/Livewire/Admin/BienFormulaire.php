<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\Bien;
use App\Models\PhotoDeBien;
use App\Models\Referentiel;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Fiche d'un bien.
 *
 * Le plus gros formulaire du backoffice : quarante champs, une galerie, et le
 * vocabulaire du referentiel a respecter. Il est ecrit a part plutot que plie
 * dans FormulaireDeBloc, dont la description declarative ne sait pas exprimer
 * une galerie ni des listes d'equipements bilingues.
 */
#[Layout('layouts.app')]
class BienFormulaire extends Component
{
    use RemplitParTraduction;
    use WithFileUploads;

    /** Verrouille : designe la ligne que l'enregistrement va ecrire. */
    #[Locked]
    public ?Bien $bien = null;

    public string $langueActive = 'fr';

    public string $onglet = 'general';

    /* --------------------------------------------------- champs */

    public string $reference = '';

    public string $slug = '';

    public string $titreFr = '';

    public string $titreEn = '';

    public string $sousTitreFr = '';

    public string $sousTitreEn = '';

    public string $accrocheFr = '';

    public string $accrocheEn = '';

    public string $descriptionFr = '';

    public string $descriptionEn = '';

    public string $type = '';

    public string $offre = Bien::VENTE;

    public string $zone = '';

    public string $statutJuridique = '';

    public string $numeroTitre = '';

    public string $quartier = '';

    public string $prix = '';

    public string $prixUnite = 'total';

    public string $surfaceHabitable = '';

    public string $surfaceTerrain = '';

    public string $nombrePieces = '';

    public string $nombreChambres = '';

    public string $nombreSallesEau = '';

    /** Equipements, une ligne par element, dans chaque langue. */
    public string $equipementsFr = '';

    public string $equipementsEn = '';

    public string $metaTitreFr = '';

    public string $metaTitreEn = '';

    public string $metaDescriptionFr = '';

    public string $metaDescriptionEn = '';

    public string $statut = Bien::BROUILLON;

    public string $dateMiseEnLigne = '';

    public bool $enAvant = false;

    public bool $urgent = false;

    /* --------------------------------------------------- galerie */

    /** Photos choisies dans le navigateur, pas encore enregistrees. */
    public $nouvellesPhotos = [];

    public ?string $message = null;

    /** Nombre maximal de photos par bien, comme sur la maquette. */
    public const PHOTOS_MAX = 10;

    /** @return list<string> */
    protected function champsTraduisibles(): array
    {
        return ['titre', 'sousTitre', 'accroche', 'description', 'metaTitre', 'metaDescription'];
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function mount(?Bien $bien = null): void
    {
        $this->langueActive = app()->getLocale();

        if (! $bien?->exists) {
            $this->dateMiseEnLigne = now()->format('Y-m-d');
            $this->type = Referentiel::deLaFamille('types_de_bien')->ordonnees()->value('valeur') ?? '';
            $this->zone = Referentiel::deLaFamille('zones')->ordonnees()->value('valeur') ?? '';

            return;
        }

        $this->bien = $bien;

        $this->reference = (string) $bien->reference;
        $this->slug = (string) $bien->slug;
        $this->titreFr = (string) $bien->titre_fr;
        $this->titreEn = (string) $bien->titre_en;
        $this->sousTitreFr = (string) $bien->sous_titre_fr;
        $this->sousTitreEn = (string) $bien->sous_titre_en;
        $this->accrocheFr = (string) $bien->accroche_fr;
        $this->accrocheEn = (string) $bien->accroche_en;
        $this->descriptionFr = (string) $bien->description_fr;
        $this->descriptionEn = (string) $bien->description_en;

        $this->type = (string) $bien->type;
        $this->offre = (string) $bien->offre;
        $this->zone = (string) $bien->zone;
        $this->statutJuridique = (string) $bien->statut_juridique;
        $this->numeroTitre = (string) $bien->numero_titre;
        $this->quartier = (string) $bien->quartier;

        $this->prix = (string) ($bien->prix ?? '');
        $this->prixUnite = (string) $bien->prix_unite;
        $this->surfaceHabitable = (string) ($bien->surface_habitable ?? '');
        $this->surfaceTerrain = (string) ($bien->surface_terrain ?? '');
        $this->nombrePieces = (string) ($bien->nombre_pieces ?? '');
        $this->nombreChambres = (string) ($bien->nombre_chambres ?? '');
        $this->nombreSallesEau = (string) ($bien->nombre_salles_eau ?? '');

        // Une ligne par equipement : c'est la forme la plus simple a saisir,
        // et elle se relit sans separateur a retenir.
        $this->equipementsFr = implode("\n", $bien->equipements('fr'));
        $this->equipementsEn = implode("\n", $bien->equipements['en'] ?? []);

        $this->metaTitreFr = (string) $bien->meta_titre_fr;
        $this->metaTitreEn = (string) $bien->meta_titre_en;
        $this->metaDescriptionFr = (string) $bien->meta_description_fr;
        $this->metaDescriptionEn = (string) $bien->meta_description_en;

        $this->statut = (string) $bien->statut;
        $this->dateMiseEnLigne = $bien->date_mise_en_ligne?->format('Y-m-d') ?? '';
        $this->enAvant = (bool) $bien->en_avant;
        $this->urgent = (bool) $bien->urgent;
    }

    public function estCreation(): bool
    {
        return ! $this->bien?->exists;
    }

    protected function rules(): array
    {
        return [
            'reference' => ['nullable', 'string', 'max:40', Rule::unique('biens', 'reference')->ignore($this->bien?->id)],
            'slug' => [
                'required', 'string', 'max:190', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('biens', 'slug')->ignore($this->bien?->id),
            ],
            'titreFr' => ['required', 'string', 'max:190'],
            'titreEn' => ['nullable', 'string', 'max:190'],
            'sousTitreFr' => ['nullable', 'string', 'max:190'],
            'sousTitreEn' => ['nullable', 'string', 'max:190'],
            'accrocheFr' => ['nullable', 'string', 'max:160'],
            'accrocheEn' => ['nullable', 'string', 'max:160'],
            'descriptionFr' => ['nullable', 'string', 'max:8000'],
            'descriptionEn' => ['nullable', 'string', 'max:8000'],

            // Le vocabulaire vient du REFERENTIEL : un bien ne peut pas porter
            // un type qu'aucun filtre ne propose, sinon il serait en ligne et
            // introuvable.
            'type' => ['required', Rule::in($this->valeursDe('types_de_bien'))],
            'zone' => ['required', Rule::in($this->valeursDe('zones'))],
            'statutJuridique' => ['nullable', Rule::in($this->valeursDe('statuts_juridiques'))],
            'offre' => ['required', Rule::in(array_keys(Bien::offres()))],

            'numeroTitre' => ['nullable', 'string', 'max:120'],
            'quartier' => ['nullable', 'string', 'max:120'],

            'prix' => ['nullable', 'integer', 'min:0', 'max:99999999999'],
            'prixUnite' => ['required', Rule::in(array_keys(Bien::unitesDePrix()))],
            'surfaceHabitable' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'surfaceTerrain' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'nombrePieces' => ['nullable', 'integer', 'min:0', 'max:100'],
            'nombreChambres' => ['nullable', 'integer', 'min:0', 'max:100'],
            'nombreSallesEau' => ['nullable', 'integer', 'min:0', 'max:100'],

            'equipementsFr' => ['nullable', 'string', 'max:2000'],
            'equipementsEn' => ['nullable', 'string', 'max:2000'],

            'metaTitreFr' => ['nullable', 'string', 'max:70'],
            'metaTitreEn' => ['nullable', 'string', 'max:70'],
            'metaDescriptionFr' => ['nullable', 'string', 'max:160'],
            'metaDescriptionEn' => ['nullable', 'string', 'max:160'],

            'statut' => ['required', Rule::in(array_keys(Bien::statuts()))],
            'dateMiseEnLigne' => ['nullable', 'date'],

            'nouvellesPhotos.*' => ['image', 'max:2048'],
        ];
    }

    /** @return list<string> */
    protected function valeursDe(string $famille): array
    {
        return Referentiel::deLaFamille($famille)->pluck('valeur')->all();
    }

    protected function validationAttributes(): array
    {
        return [
            'titreFr' => __('le titre (français)'),
            'slug' => __("l'identifiant d'URL"),
            'type' => __('le type'),
            'zone' => __('la zone'),
            'offre' => __("l'offre"),
            'prix' => __('le prix'),
            'nouvellesPhotos.*' => __('les photos'),
        ];
    }

    /** Propose un identifiant d'URL a partir du titre, tant qu'il est vide. */
    public function updatedTitreFr(string $valeur): void
    {
        if ($this->estCreation() && trim($this->slug) === '') {
            $this->slug = Str::slug($valeur);
        }
    }

    public function retirerPhoto(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $photo = PhotoDeBien::where('bien_id', $this->bien?->id)->findOrFail($id);

        if ($chemin = $photo->cheminEffacable()) {
            Storage::disk('public')->delete($chemin);
        }

        $photo->delete();
        $this->message = __('Photo retirée.');
    }

    public function enregistrer()
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update.
        abort_unless($this->peutEcrire(), 403);

        $this->remplirParTraductionCeQuiEstVide();

        $this->validate();

        $donnees = [
            'reference' => $this->reference ?: null,
            'slug' => $this->slug,
            'titre_fr' => $this->titreFr,
            'titre_en' => $this->titreEn ?: null,
            'sous_titre_fr' => $this->sousTitreFr ?: null,
            'sous_titre_en' => $this->sousTitreEn ?: null,
            'accroche_fr' => $this->accrocheFr ?: null,
            'accroche_en' => $this->accrocheEn ?: null,
            'description_fr' => $this->descriptionFr ?: null,
            'description_en' => $this->descriptionEn ?: null,
            'type' => $this->type,
            'offre' => $this->offre,
            'zone' => $this->zone,
            'statut_juridique' => $this->statutJuridique ?: null,
            'numero_titre' => $this->numeroTitre ?: null,
            'quartier' => $this->quartier ?: null,
            'prix' => $this->prix === '' ? null : (int) $this->prix,
            'prix_unite' => $this->prixUnite,
            'surface_habitable' => $this->surfaceHabitable === '' ? null : (int) $this->surfaceHabitable,
            'surface_terrain' => $this->surfaceTerrain === '' ? null : (int) $this->surfaceTerrain,
            'nombre_pieces' => $this->nombrePieces === '' ? null : (int) $this->nombrePieces,
            'nombre_chambres' => $this->nombreChambres === '' ? null : (int) $this->nombreChambres,
            'nombre_salles_eau' => $this->nombreSallesEau === '' ? null : (int) $this->nombreSallesEau,
            'equipements' => [
                'fr' => $this->enLignes($this->equipementsFr),
                'en' => $this->enLignes($this->equipementsEn),
            ],
            'meta_titre_fr' => $this->metaTitreFr ?: null,
            'meta_titre_en' => $this->metaTitreEn ?: null,
            'meta_description_fr' => $this->metaDescriptionFr ?: null,
            'meta_description_en' => $this->metaDescriptionEn ?: null,
            'statut' => $this->statut,
            'date_mise_en_ligne' => $this->dateMiseEnLigne ?: null,
            'en_avant' => $this->enAvant,
            'urgent' => $this->urgent,
        ];

        if ($this->bien?->exists) {
            $this->bien->update($donnees);
        } else {
            // L'auteur est pose a la creation seulement : corriger une virgule
            // ne change pas la signature.
            $donnees['auteur_id'] = auth()->id();
            $donnees['ordre'] = ((int) Bien::max('ordre')) + 1;
            $this->bien = Bien::create($donnees);
        }

        $this->televerserLesPhotos();

        session()->flash('message', __('Bien enregistré.'));
        $this->dispatch('toast', message: __('Bien enregistré.'), variant: 'success');

        return $this->redirect(route('admin.biens.liste'), navigate: true);
    }

    /** Chaque ligne non vide devient un equipement. */
    protected function enLignes(string $texte): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $texte) ?: []), 'strlen'));
    }

    /**
     * Range les photos choisies, dans la limite du bien.
     *
     * La borne est verifiee ICI et pas seulement dans le gabarit : le nombre de
     * fichiers envoyes vient du navigateur.
     */
    protected function televerserLesPhotos(): void
    {
        if (! $this->nouvellesPhotos) {
            return;
        }

        $dejaLa = $this->bien->photos()->count();
        $rang = (int) $this->bien->photos()->max('ordre');

        foreach ($this->nouvellesPhotos as $fichier) {
            if ($dejaLa >= self::PHOTOS_MAX) {
                $this->message = __('Limite de :nombre photos atteinte : les suivantes ont été ignorées.', [
                    'nombre' => self::PHOTOS_MAX,
                ]);
                break;
            }

            $chemin = $fichier->store('biens', 'public');

            PhotoDeBien::create([
                'bien_id' => $this->bien->id,
                'fichier' => 'storage/'.$chemin,
                'ordre' => ++$rang,
            ]);

            $dejaLa++;
        }

        $this->nouvellesPhotos = [];
    }

    public function render(): View
    {
        return view('livewire.admin.bien-formulaire', [
            'estCreation' => $this->estCreation(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
            'types' => Referentiel::deLaFamille('types_de_bien')->ordonnees()->get(),
            'zones' => Referentiel::deLaFamille('zones')->ordonnees()->get(),
            'statutsJuridiques' => Referentiel::deLaFamille('statuts_juridiques')->ordonnees()->get(),
            'offres' => Bien::offres(),
            'statuts' => Bien::statuts(),
            'unitesDePrix' => Bien::unitesDePrix(),
            'photos' => $this->bien?->photos()->get() ?? collect(),
            'photosMax' => self::PHOTOS_MAX,
            'peutEcrire' => $this->peutEcrire(),
        ])->title($this->estCreation() ? __('Nouveau bien') : __('Modifier le bien'));
    }
}
