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
