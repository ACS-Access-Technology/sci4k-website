<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RemplitParTraduction;
use App\Models\ReglageDeSection;
use App\Services\Traduction\Traducteur;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Ecran ou tous les elements d'un bloc s'editent ensemble.
 *
 * Valeurs, chiffres cles, etapes du processus : tous cote a cote, un seul
 * bouton d'enregistrement. Le cadrage en donne le motif — un tableau pagine
 * avec recherche et filtres pour quatre lignes couterait trois clics pour
 * changer un mot.
 *
 * S'appelait EnsembleFige tant que ces blocs n'acceptaient ni ajout ni
 * suppression. Les maquettes du backoffice montrent les deux : le nom mentait,
 * il a change avec le comportement.
 *
 * Chaque ensemble declare ses champs et, s'il en a, la section dont il porte
 * l'en-tete et les options. Le bilingue, la validation, le controle de role et
 * le remplissage par traduction sont ici.
 */
#[Layout('layouts.app')]
abstract class EditionGroupee extends Component
{
    use RemplitParTraduction;

    /**
     * L'ecran est-il rendu A L'INTERIEUR d'un autre ?
     *
     * Vrai quand un ecran de page — « Pages du site → Accueil » — embarque
     * cet editeur dans l'un de ses modules. Le corps reste identique ; seul
     * l'en-tete de page disparait, un titre et un fil d'Ariane n'ayant pas de
     * sens au milieu d'une autre page. Voir ListeOrdonnable::$embarque.
     */
    public bool $embarque = false;

    /**
     * Les lignes en cours d'edition, indexees par identifiant.
     *
     * Une ligne ajoutee mais pas encore enregistree porte une cle « neuf-N » :
     * elle n'a pas encore d'identifiant, et lui en inventer un risquerait de
     * heurter celui d'une ligne reelle.
     *
     * @var array<int|string, array<string, string>>
     */
    public array $lignes = [];

    /** Reglages du bloc : titre de section, chapo, options. */
    public array $reglages = [];

    /** Identifiants des lignes retirees, effacees a l'enregistrement. */
    public array $aSupprimer = [];

    /** Compteur des lignes ajoutees, pour leur donner une cle distincte. */
    public int $compteurNeuf = 0;

    /** Langue du contenu saisi — sans rapport avec celle de l'interface. */
    public string $langueActive = 'fr';

    /** Classe du modele porte par cet ecran. */
    abstract protected function modele(): string;

    /**
     * Champs bilingues, par prefixe : ['titre', 'texte'] designe les colonnes
     * titre_fr, titre_en, texte_fr et texte_en.
     *
     * @return list<string>
     */
    abstract protected function champsBilingues(): array;

    /**
     * Champs qui n'ont pas de version par langue, avec leurs regles.
     *
     * @return array<string, list<string>>
     */
    protected function champsSimples(): array
    {
        return [];
    }

    /** Vue Blade de l'ecran. */
    abstract protected function vue(): string;

    /** Titre affiche dans l'en-tete et l'onglet. */
    abstract protected function titre(): string;

    /**
     * Section du site dont cet ecran regle l'en-tete, ou null.
     *
     * Le processus et les valeurs affichent un panneau « Reglages du bloc » :
     * il edite l'en-tete de la section correspondante, deja portee par
     * ReglageDeSection, plutot qu'une seconde table qui dirait la meme chose.
     */
    protected function sectionReglee(): ?string
    {
        return null;
    }

    /**
     * Options propres au bloc, avec leur valeur par defaut.
     *
     * @return array<string, mixed>
     */
    protected function optionsDuBloc(): array
    {
        return [];
    }

    /** Cet ecran accepte-t-il l'ajout et le retrait d'elements ? */
    protected function ajoutPermis(): bool
    {
        return true;
    }

    /** Regles appliquees a chaque champ bilingue. */
    protected function reglesDuChamp(string $champ): array
    {
        return ['required', 'string'];
    }

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function mount(): void
    {
        $this->langueActive = app()->getLocale();

        foreach ($this->elements() as $element) {
            $this->lignes[$element->id] = $this->ligneDepuis($element);
        }

        if ($slug = $this->sectionReglee()) {
            $section = ReglageDeSection::pour($slug);

            $this->reglages = [
                'titre_fr' => (string) $section->titre_fr,
                'titre_en' => (string) $section->titre_en,
                'chapo_fr' => (string) $section->chapo_fr,
                'chapo_en' => (string) $section->chapo_en,
            ];

            foreach ($this->optionsDuBloc() as $nom => $defaut) {
                $this->reglages[$nom] = $section->option($nom, $defaut);
            }
        }
    }

    /** Les valeurs d'une ligne, telles que le formulaire les tient. */
    protected function ligneDepuis($element): array
    {
        $ligne = [];

        foreach ($this->champsBilingues() as $champ) {
            $ligne[$champ.'_fr'] = (string) $element->{$champ.'_fr'};
            $ligne[$champ.'_en'] = (string) $element->{$champ.'_en'};
        }

        foreach (array_keys($this->champsSimples()) as $champ) {
            $ligne[$champ] = (string) $element->$champ;
        }

        $ligne['visible'] = $element->visible ? '1' : '';

        return $ligne;
    }

    /** Les elements de l'ensemble, dans leur ordre d'affichage. */
    protected function elements()
    {
        return ($this->modele())::query()->orderBy('ordre')->orderBy('id')->get();
    }

    public function ajouter(): void
    {
        abort_unless($this->ajoutPermis(), 403);
        abort_unless($this->peutEcrire(), 403);

        $ligne = [];

        foreach ($this->champsBilingues() as $champ) {
            $ligne[$champ.'_fr'] = '';
            $ligne[$champ.'_en'] = '';
        }

        foreach (array_keys($this->champsSimples()) as $champ) {
            $ligne[$champ] = '';
        }

        $ligne['visible'] = '1';

        $this->lignes['neuf-'.(++$this->compteurNeuf)] = $ligne;
    }

    public function retirer(int|string $cle): void
    {
        abort_unless($this->ajoutPermis(), 403);
        abort_unless($this->peutEcrire(), 403);

        unset($this->lignes[$cle]);

        // Une ligne jamais enregistree n'a rien a effacer en base.
        if (is_int($cle) || ctype_digit((string) $cle)) {
            $this->aSupprimer[] = (int) $cle;
        }
    }

    protected function rules(): array
    {
        $regles = [];

        foreach (array_keys($this->lignes) as $cle) {
            foreach ($this->champsBilingues() as $champ) {
                $regles["lignes.$cle.{$champ}_fr"] = $this->reglesDuChamp($champ);
                $regles["lignes.$cle.{$champ}_en"] = $this->reglesDuChamp($champ);
            }

            foreach ($this->champsSimples() as $champ => $regle) {
                $regles["lignes.$cle.$champ"] = $regle;
            }

            $regles["lignes.$cle.visible"] = ['nullable'];
        }

        if ($this->sectionReglee()) {
            foreach (['titre_fr', 'titre_en', 'chapo_fr', 'chapo_en'] as $champ) {
                $regles["reglages.$champ"] = ['nullable', 'string', 'max:2000'];
            }
        }

        return $regles;
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « lignes.7.titre_fr ».
     */
    protected function validationAttributes(): array
    {
        $intitules = [];

        foreach (array_keys($this->lignes) as $rang => $cle) {
            foreach ($this->champsBilingues() as $champ) {
                $intitules["lignes.$cle.{$champ}_fr"] = __(':champ (français) — élément :rang', ['champ' => $champ, 'rang' => $rang + 1]);
                $intitules["lignes.$cle.{$champ}_en"] = __(':champ (anglais) — élément :rang', ['champ' => $champ, 'rang' => $rang + 1]);
            }
        }

        return $intitules;
    }

    /**
     * Champs traduisibles au sens du trait : aucun.
     *
     * Les textes vivent dans un tableau, pas dans des proprietes {prefixe}Fr.
     * Le remplissage passe par completerCouple(), que le trait expose.
     *
     * @return list<string>
     */
    protected function champsTraduisibles(): array
    {
        return [];
    }

    public function enregistrer(): void
    {
        // La route protege l'ecran, pas l'action : Livewire ne rejoue pas le
        // middleware de role sur /livewire/update.
        abort_unless($this->peutEcrire(), 403);

        foreach ($this->lignes as $cle => $ligne) {
            foreach ($this->champsBilingues() as $champ) {
                [$fr, $en] = $this->completerCouple($ligne[$champ.'_fr'] ?? '', $ligne[$champ.'_en'] ?? '');

                $this->lignes[$cle][$champ.'_fr'] = $fr;
                $this->lignes[$cle][$champ.'_en'] = $en;
            }
        }

        // Rien a valider quand l'ecran ne regle pas de section et n'a plus
        // aucune ligne — apres le retrait de la derniere categorie, par
        // exemple. Livewire refuse un jeu de regles vide, et l'editeur aurait
        // vu une erreur technique la ou il venait simplement de tout retirer.
        // Meme garde que Referentiels::enregistrer().
        if ($this->rules() !== []) {
            $this->validate();
        }

        // Les elements sont relus depuis la base : les identifiants viennent du
        // navigateur, et l'un d'eux pourrait designer une ligne d'une autre
        // table ou une ligne disparue depuis l'ouverture de l'ecran.
        $connus = ($this->modele())::query()
            ->whereIn('id', array_filter(array_keys($this->lignes), 'is_numeric'))
            ->get()
            ->keyBy('id');

        $rang = 0;

        // Seules les colonnes que l'ecran declare sont ecrites. `$this->lignes`
        // est une propriete publique : le navigateur en fixe le contenu, cles
        // comprises, et la validation ne retire pas les cles qu'elle ignore.
        // Sans ce filtre, toute colonne `fillable` absente de l'ecran serait
        // ecrivable sans passer par aucune regle. Les trois ecrans actuels
        // declarent chacun tout leur `fillable`, si bien que rien n'est
        // exploitable aujourd'hui — c'est une coincidence, pas une garantie,
        // et elle tomberait au premier ecran qui n'expose qu'une partie de son
        // modele.
        $declarees = [];

        foreach ($this->champsBilingues() as $champ) {
            $declarees[] = $champ.'_fr';
            $declarees[] = $champ.'_en';
        }

        $declarees = array_merge($declarees, array_keys($this->champsSimples()));

        foreach ($this->lignes as $cle => $ligne) {
            $donnees = array_intersect_key($ligne, array_flip($declarees));
            $donnees['visible'] = (bool) ($ligne['visible'] ?? false);
            // Le rang suit l'ordre d'affichage a l'ecran : l'editeur voit ce
            // qu'il obtiendra, sans avoir a saisir un numero.
            $donnees['ordre'] = ++$rang;

            if (isset($connus[$cle])) {
                $connus[$cle]->update($donnees);

                continue;
            }

            if ($this->ajoutPermis() && ! is_numeric($cle)) {
                $this->lignes[$cle] = $ligne;
                ($this->modele())::create($donnees);
            }
        }

        if ($this->aSupprimer) {
            ($this->modele())::query()->whereIn('id', $this->aSupprimer)->delete();
            $this->aSupprimer = [];
        }

        $this->enregistrerLesReglages();

        // Les cles « neuf-N » sont remplacees par les identifiants reels, sans
        // quoi un second enregistrement creerait les memes lignes une fois de
        // plus.
        $this->lignes = [];
        $this->compteurNeuf = 0;

        foreach ($this->elements() as $element) {
            $this->lignes[$element->id] = $this->ligneDepuis($element);
        }

        $this->dispatch('toast', message: __('Modifications enregistrées.'), variant: 'success');
    }

    protected function enregistrerLesReglages(): void
    {
        if (! $slug = $this->sectionReglee()) {
            return;
        }

        $section = ReglageDeSection::pour($slug);

        [$titreFr, $titreEn] = $this->completerCouple($this->reglages['titre_fr'] ?? '', $this->reglages['titre_en'] ?? '');
        [$chapoFr, $chapoEn] = $this->completerCouple($this->reglages['chapo_fr'] ?? '', $this->reglages['chapo_en'] ?? '');

        $section->fill([
            'titre_fr' => $titreFr, 'titre_en' => $titreEn,
            'chapo_fr' => $chapoFr, 'chapo_en' => $chapoEn,
        ]);

        $options = [];

        foreach ($this->optionsDuBloc() as $nom => $defaut) {
            $valeur = $this->reglages[$nom] ?? $defaut;

            // Une case a cocher renvoie '1' ou '', jamais un booleen : le type
            // de la valeur par defaut dit comment lire ce qui revient.
            $options[$nom] = is_bool($defaut) ? (bool) $valeur : $valeur;
        }

        $section->poserOptions($options);
        $section->save();
    }

    public function render(): View
    {
        return view($this->vue(), [
            'langue' => app()->getLocale(),
            'peutEcrire' => $this->peutEcrire(),
            'ajoutPermis' => $this->ajoutPermis(),
            'sectionReglee' => $this->sectionReglee(),
            'optionsDuBloc' => $this->optionsDuBloc(),
            'traductionActive' => app(Traducteur::class)->disponible(),
        ])->title($this->titre());
    }
}
