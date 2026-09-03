<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\MembreEquipe;
use App\Models\Partenaire;
use App\Models\QuestionFaq;
use App\Models\Service;
use App\Models\Temoignage;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/*
 * Recherche transverse de la barre d'administration.
 *
 * La maquette la place en haut de chaque ecran : « Rechercher un bien, un
 * article… ». Chaque liste a deja sa propre recherche, mais elle suppose de
 * savoir OU se trouve ce qu'on cherche — or on se souvient d'un titre, pas de
 * la famille a laquelle il appartient.
 *
 * Elle cherche dans les deux langues : un editeur anglophone tape le titre
 * qu'il a saisi, pas sa traduction francaise.
 */
class RechercheGlobale extends Component
{
    public string $terme = '';

    /** Nombre de resultats par famille, pour que la liste reste lisible. */
    protected const PAR_FAMILLE = 3;

    /**
     * Les familles fouillees : modele, colonnes, intitule, route, icone.
     *
     * La route mene a l'ECRAN DE PAGE qui porte le contenu, et non plus a une
     * fiche d'edition : les ecrans par type de contenu ont ete retires, et
     * chaque collection s'edite depuis la page qui l'affiche. Le resultat
     * ouvre donc la page, a charge d'y retrouver la ligne.
     *
     * @return list<array<int, mixed>>
     */
    protected function famillesCherchees(): array
    {
        return [
            [Article::class, ['titre_fr', 'titre_en'], 'titre_fr', __('Article'), 'admin.pages.actualites', 'document'],
            [Service::class, ['nom_fr', 'nom_en'], 'nom_fr', __('Service'), 'admin.pages.services', 'grille'],
            [QuestionFaq::class, ['question_fr', 'question_en'], 'question_fr', __('Question'), 'admin.pages.faq', 'question'],
            [Temoignage::class, ['auteur', 'citation_fr', 'citation_en'], 'auteur', __('Témoignage'), 'admin.pages.accueil', 'guillemets'],
            [MembreEquipe::class, ['nom', 'fonction_fr', 'fonction_en'], 'nom', __('Membre'), 'admin.pages.presentation', 'personne'],
            [Partenaire::class, ['nom'], 'nom', __('Partenaire'), 'admin.pages.accueil', 'grille'],
        ];
    }

    /**
     * Les resultats, groupes par famille.
     *
     * @return list<array<string, mixed>>
     */
    protected function resultats(): array
    {
        // Deux caracteres suffisent a chercher un sigle — « TF » pour titre
        // foncier —, mais un seul ramenerait la moitie de la base.
        if (mb_strlen(trim($this->terme)) < 2) {
            return [];
        }

        $motif = '%'.trim($this->terme).'%';
        $trouves = [];

        foreach ($this->famillesCherchees() as [$modele, $colonnes, $affiche, $famille, $route, $icone]) {
            $elements = $modele::query()
                ->where(function ($requete) use ($colonnes, $motif) {
                    foreach ($colonnes as $colonne) {
                        $requete->orWhere($colonne, 'like', $motif);
                    }
                })
                ->limit(self::PAR_FAMILLE)
                ->get();

            foreach ($elements as $element) {
                $trouves[] = [
                    'intitule' => (string) $element->$affiche,
                    'famille' => $famille,
                    'icone' => $icone,
                    'route' => $route,
                    'element' => $element,
                ];
            }
        }

        return $trouves;
    }

    /** Vide le champ, par exemple apres avoir suivi un resultat. */
    public function vider(): void
    {
        $this->terme = '';
    }

    public function render(): View
    {
        return view('livewire.admin.recherche-globale', [
            'resultats' => $this->resultats(),
            'langue' => app()->getLocale(),
        ]);
    }
}
