<?php

namespace App\Livewire\Concerns;

use App\Models\ReglageDeSection;

/**
 * Les textes d'un bloc qui ne sont ni un titre ni une accroche.
 *
 * Les libelles d'un formulaire, ses exemples de saisie, le mot du bouton :
 * autant de textes que la page publique affiche, qu'aucun ecran n'exposait, et
 * qui ne valent pas une paire de colonnes chacun. Ils vivent dans le sac
 * d'options de la section, sous des cles suffixees `_fr` / `_en`, et se lisent
 * par ReglageDeSection::texteBilingue().
 *
 * Le module qui s'en sert declare, sous la cle `textes` de sa description :
 *
 *   'libelle_bouton' => ['intitule' => 'Libellé du bouton',
 *                        'defaut'   => 'Envoyer',
 *                        'long'     => true],   // zone de saisie, facultatif
 *
 * Ecran partage par « Pages du site → FAQ » et « → Contact » : le filtre sur
 * les cles declarees est le meme, et une seule copie evite qu'il derive.
 */
trait PorteDesTextesDeBloc
{
    /**
     * Le titre et la description que les moteurs reprennent, pour une page.
     *
     * Ils etaient ecrits en dur en tete de CHAQUE vue publique — deux lignes
     * de Blade par page — et l'ecran « Configuration » ne proposait que des
     * valeurs par DEFAUT, employees quand la page n'annonce rien. Aucune page
     * n'etant dans ce cas, ces defauts ne servaient jamais et les vrais textes
     * n'etaient modifiables nulle part.
     *
     * Ils vivent sur la banniere de chaque page : c'est le module que
     * l'editeur ouvre pour changer ce que la page annonce d'elle-meme.
     *
     * Les longueurs sont celles de l'ecran Configuration, et pour la meme
     * raison : au-dela, Google tronque.
     */
    protected static function referencement(string $titre, string $description): array
    {
        return [
            'meta_titre' => [
                'intitule' => 'Titre dans les résultats de recherche',
                'defaut' => $titre,
                'aide' => 'Au-delà de 70 caractères, Google tronque le titre.',
            ],
            'meta_description' => [
                'intitule' => 'Description dans les résultats de recherche',
                'defaut' => $description,
                'long' => true,
                'aide' => '160 caractères au plus, pour la même raison.',
            ],
        ];
    }

    /** Textes du bloc ouvert, par cle suffixee de langue. */
    public array $textes = [];

    /**
     * Les textes que DECLARE le module ouvert.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function textesDeclares(): array
    {
        return $this->moduleCourant()['textes'] ?? [];
    }

    protected function chargerLesTextes(?ReglageDeSection $section): void
    {
        $this->textes = [];

        foreach (array_keys($this->textesDeclares()) as $nom) {
            $this->textes[$nom.'_fr'] = (string) ($section?->option($nom.'_fr', '') ?? '');
            $this->textes[$nom.'_en'] = (string) ($section?->option($nom.'_en', '') ?? '');
        }
    }

    /** @return array<string, list<string>> */
    protected function reglesDesTextes(int $longueurMaximale = 500): array
    {
        $regles = [];

        foreach ($this->textesDeclares() as $nom => $decrit) {
            $maxi = ($decrit['long'] ?? false) ? max($longueurMaximale, 2000) : $longueurMaximale;

            $regles['textes.'.$nom.'_fr'] = ['nullable', 'string', 'max:'.$maxi];
            $regles['textes.'.$nom.'_en'] = ['nullable', 'string', 'max:'.$maxi];
        }

        return $regles;
    }

    /**
     * Intitules lisibles, pour que le message de validation ne cite pas
     * « textes.libelle_bouton_fr ».
     *
     * @return array<string, string>
     */
    protected function intitulesDesTextes(): array
    {
        $intitules = [];

        foreach ($this->textesDeclares() as $nom => $decrit) {
            $intitules['textes.'.$nom.'_fr'] = __($decrit['intitule']).' ('.__('français').')';
            $intitules['textes.'.$nom.'_en'] = __($decrit['intitule']).' ('.__('anglais').')';
        }

        return $intitules;
    }

    /**
     * Pose les textes declares sur la section, SANS enregistrer.
     *
     * Seules les cles que le module declare sont retenues : `$textes` est une
     * propriete publique, dont le navigateur fixe le contenu, CLES COMPRISES.
     * Sans ce filtre, n'importe quelle option de la section — la mise en page
     * du processus, par exemple — serait ecrivable sans passer par aucune
     * regle.
     *
     * poserOptions() pose sans ecrire : l'appelant enregistre ensuite.
     */
    protected function poserLesTextes(ReglageDeSection $section): void
    {
        $declarees = [];

        foreach (array_keys($this->textesDeclares()) as $nom) {
            foreach (['_fr', '_en'] as $suffixe) {
                $declarees[$nom.$suffixe] = trim((string) ($this->textes[$nom.$suffixe] ?? ''));
            }
        }

        if ($declarees !== []) {
            $section->poserOptions($declarees);
        }
    }
}
