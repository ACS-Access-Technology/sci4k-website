<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formulaire commun aux blocs de contenu.
 *
 * Six entites partagent la meme structure : quelques champs simples, quelques
 * champs bilingues, parfois un fichier. Les ecrire une par une aurait repete
 * six fois le bilingue, la validation, le controle de role, le remplissage par
 * traduction et le menage des fichiers — donc six endroits a corriger au
 * premier defaut. C'est le raisonnement qui avait deja produit ListeOrdonnable
 * pour les listes, et TraduitParColonnes au lot 1.
 *
 * Chaque formulaire ne declare que ses champs, sous forme d'un tableau lu par
 * la vue autant que par la validation : une description unique, plutot qu'une
 * dans le composant et une autre dans le gabarit qui pourraient diverger.
 */
#[Layout('layouts.app')]
abstract class FormulaireDeBloc extends Component
{
    use RemplitParTraduction;
    use WithFileUploads;

    /**
     * Verrouille : Livewire expose au navigateur toute propriete publique, et
     * celle-ci designe la ligne que l'enregistrement va ecrire.
     */
    #[Locked]
    public ?Model $element = null;

    /**
     * Valeurs saisies, par nom de colonne.
     *
     * @var array<string, string>
     */
    public array $valeurs = [];

    /** Fichier choisi dans le navigateur, pas encore enregistre. */
    public $fichier = null;

    /**
     * Chemin du fichier actuel. Verrouille pour la meme raison que
     * ServiceFormulaire::$imageActuelle : propriete d'etat, jamais saisie,
     * mais utilisee comme chemin d'effacement.
     */
    #[Locked]
    public ?string $fichierActuel = null;

    /** L'editeur a demande le retrait du fichier existant. */
    public bool $fichierARetirer = false;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Classe du modele porte par ce formulaire. */
    abstract protected function modele(): string;

    /**
     * Description des champs, lue par la validation ET par la vue.
     *
     * Chaque entree : nom de colonne => [
     *   'intitule' => texte affiche,
     *   'type'     => 'texte' | 'zone' | 'nombre' | 'url' | 'email' | 'fige',
     *   'bilingue' => true pour une paire {nom}_fr / {nom}_en,
     *   'regles'   => regles de validation,
     *   'aide'     => precision affichee sous le champ, facultative,
     * ]
     *
     * @return array<string, array<string, mixed>>
     */
    abstract protected function champs(): array;

    /** Vue Blade de l'ecran. */
    abstract protected function vue(): string;

    /** Route de la liste, ou l'on revient apres enregistrement. */
    abstract protected function routeListe(): string;

    /** Intitule au singulier, pour les titres et le fil d'Ariane. */
    abstract protected function intitule(): string;

    /**
     * Colonne du fichier televerse, et son dossier de stockage.
     *
     * @return array{0: string, 1: string}|null
     */
    protected function fichierGere(): ?array
    {
        return null;
    }

    /**
     * Comment nommer le fichier a l'ecran.
     *
     * « Fichier » est exact et sans utilite : sur une fiche d'employe, le
     * client a cherche un televersement de photo sans le voir, l'intitule ne
     * disant pas ce qu'on attend. Chaque formulaire nomme donc le sien.
     *
     * @return array{intitule: string, aide: string, forme: string}
     */
    protected function descriptionDuFichier(): array
    {
        return [
            'intitule' => __('Fichier'),
            'aide' => '',
            'forme' => 'rectangle',
        ];
    }

    /**
     * Ce bloc peut-il etre masque du site ?
     *
     * Faux pour les en-tetes de section, qui n'ont pas de colonne `visible` :
     * masquer un en-tete laisserait la section sans titre, ce qui n'a pas de
     * sens — c'est la section entiere qu'on masquerait alors.
     */
    protected function gereLaVisibilite(): bool
    {
        return true;
    }

    /** Cet ecran accepte-t-il la creation ? */
    protected function creationPermise(): bool
    {
        return true;
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    /**
     * Prepare l'ecran. Ce n'est pas mount() : PHP interdit a une classe fille
     * de restreindre le type d'un parametre, et chaque formulaire doit
     * declarer son modele concret pour que le liage de route fonctionne. Ils
     * definissent donc leur propre mount() et delegent ici.
     */
    protected function preparer(?Model $element = null): void
    {
        $this->langueActive = app()->getLocale();

        foreach ($this->champs() as $nom => $description) {
            if ($description['bilingue'] ?? false) {
                $this->valeurs[$nom.'_fr'] = '';
                $this->valeurs[$nom.'_en'] = '';
            } else {
                $this->valeurs[$nom] = '';
            }
        }

        if ($this->gereLaVisibilite()) {
            $this->valeurs['visible'] = '1';
        }

        if (! $element?->exists) {
            abort_unless($this->creationPermise(), 404);

            return;
        }

        $this->element = $element;

        foreach (array_keys($this->valeurs) as $colonne) {
            $this->valeurs[$colonne] = (string) $element->$colonne;
        }

        if ($gere = $this->fichierGere()) {
            $this->fichierActuel = $element->{$gere[0]};
        }
    }

    /** Le bloc est-il en cours de creation ? */
    public function estCreation(): bool
    {
        return ! $this->element?->exists;
    }

    protected function rules(): array
    {
        $regles = [];

        foreach ($this->champs() as $nom => $description) {
            // Un champ fige n'est ni saisi ni valide : il n'est qu'affiche.
            if (($description['type'] ?? '') === 'fige' && ! $this->estCreation()) {
                continue;
            }

            $propres = $description['regles'] ?? ['nullable', 'string', 'max:255'];

            if ($description['bilingue'] ?? false) {
                $regles['valeurs.'.$nom.'_fr'] = $propres;
                $regles['valeurs.'.$nom.'_en'] = $propres;
            } else {
                $regles['valeurs.'.$nom] = $propres;
            }
        }

        if ($this->gereLaVisibilite()) {
            $regles['valeurs.visible'] = ['nullable'];
        }

        if ($this->fichierGere()) {
            $regles['fichier'] = ['nullable', 'image', 'max:4096'];
        }

        return $regles;
    }

    protected function validationAttributes(): array
    {
        $intitules = [];

        foreach ($this->champs() as $nom => $description) {
            $intitule = $description['intitule'] ?? $nom;

            if ($description['bilingue'] ?? false) {
                $intitules['valeurs.'.$nom.'_fr'] = $intitule.' ('.__('français').')';
                $intitules['valeurs.'.$nom.'_en'] = $intitule.' ('.__('anglais').')';
            } else {
                $intitules['valeurs.'.$nom] = $intitule;
            }
        }

        return $intitules;
    }

    /**
     * Champs traduisibles au sens du trait : aucun.
     *
     * Les textes vivent dans un tableau, pas dans des proprietes {prefixe}Fr.
     * Le remplissage passe par completerCouple(), que le trait expose pour ce
     * cas.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return [];
    }

    public function updatedFichier(): void
    {
        $this->validateOnly('fichier');
    }

    /**
     * Retire le fichier. Il n'est efface qu'a l'enregistrement : tant que
     * l'editeur n'a pas valide, il peut encore changer d'avis.
     */
    public function retirerFichier(): void
    {
        $this->fichier = null;
        $this->fichierARetirer = true;
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update.
        abort_unless($this->peutEcrire(), 403);

        // Avant la validation : un champ rempli par traduction doit satisfaire
        // la regle « required » comme s'il avait ete saisi.
        foreach ($this->champs() as $nom => $description) {
            if (! ($description['bilingue'] ?? false)) {
                continue;
            }

            [$fr, $en] = $this->completerCouple($this->valeurs[$nom.'_fr'] ?? '', $this->valeurs[$nom.'_en'] ?? '');

            $this->valeurs[$nom.'_fr'] = $fr;
            $this->valeurs[$nom.'_en'] = $en;
        }

        $this->validate();

        $donnees = $this->valeurs;

        if ($this->gereLaVisibilite()) {
            // La case a cocher renvoie '1' ou '', jamais un booleen.
            $donnees['visible'] = (bool) ($this->valeurs['visible'] ?? false);
        }

        // Un champ fige garde sa valeur d'origine : il n'est saisi qu'a la
        // creation, et l'ecran d'edition ne fait que l'afficher.
        foreach ($this->champs() as $nom => $description) {
            if (($description['type'] ?? '') === 'fige' && ! $this->estCreation()) {
                unset($donnees[$nom]);
            }
        }

        if ($gere = $this->fichierGere()) {
            $donnees[$gere[0]] = $this->resoudreFichier($gere[1]);
        }

        if ($this->estCreation()) {
            $donnees['ordre'] = ($this->modele())::rangSuivant();
            $this->element = ($this->modele())::create($donnees);
        } else {
            $this->element->update($donnees);
        }

        session()->flash('message', __(':intitule enregistré.', ['intitule' => $this->intitule()]));
        $this->redirectRoute($this->routeListe());
    }

    /**
     * Determine le chemin de fichier a enregistrer, et fait le menage.
     *
     * L'ancien fichier n'est efface que s'il vient de l'administration, et
     * seulement si son chemin ne remonte nulle part : un chemin forge du genre
     * « storage/x/../couvertures/y.jpg » commence bien par le prefixe attendu
     * et se resout pourtant hors du dossier — defaut trouve au lot 2a, ou il
     * permettait de detruire la couverture d'un article.
     */
    protected function resoudreFichier(string $dossier): ?string
    {
        $ancien = $this->fichierActuel;

        if ($this->fichier) {
            $chemin = $this->fichier->store($dossier, 'public');
            $this->effacerSiTeleverse($ancien, $dossier);
            $this->fichierActuel = 'storage/'.$chemin;

            return $this->fichierActuel;
        }

        if ($this->fichierARetirer) {
            $this->effacerSiTeleverse($ancien, $dossier);
            $this->fichierActuel = null;
            $this->fichierARetirer = false;

            return null;
        }

        return $ancien;
    }

    protected function effacerSiTeleverse(?string $chemin, string $dossier): void
    {
        $prefixe = 'storage/'.$dossier.'/';

        if (! $chemin || ! str_starts_with($chemin, $prefixe)) {
            return;
        }

        $relatif = substr($chemin, strlen('storage/'));

        if (in_array('..', explode('/', $relatif), true)) {
            return;
        }

        Storage::disk('public')->delete($relatif);
    }

    public function render(): View
    {
        return view($this->vue(), [
            'champs' => $this->champs(),
            'estCreation' => $this->estCreation(),
            'langue' => app()->getLocale(),
            'traductionActive' => app(Traducteur::class)->disponible(),
            'fichierGere' => $this->fichierGere() !== null,
            'descriptionFichier' => $this->descriptionDuFichier(),
            'gereLaVisibilite' => $this->gereLaVisibilite(),
        ])->title($this->estCreation()
            ? __('Nouveau : :intitule', ['intitule' => $this->intitule()])
            : __('Modifier : :intitule', ['intitule' => $this->intitule()]));
    }
}
