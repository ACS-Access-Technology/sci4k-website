<?php

namespace App\Livewire\Concerns;

/**
 * Ce que les sept ecrans d'administration de page avaient en commun.
 *
 * Un ecran par page publique : accueil, presentation, biens, services,
 * actualites, faq, contact. Chacun presente des MODULES — les blocs de sa page
 * — et en edite un a la fois. Cette mecanique-la ne varie pas d'un ecran a
 * l'autre ; seuls varient les modules eux-memes, ce qu'on charge et ce qu'on
 * enregistre.
 *
 * Elle etait pourtant recopiee sept fois. mount(), ouvrir() et peutEcrire()
 * etaient identiques caractere pour caractere dans les sept fichiers, ce qui
 * faisait de chaque correction sept corrections — ou six oublis.
 *
 * moduleCourant() SEMBLAIT varier : chaque ecran nommait son module de repli,
 * « hero » pour l'accueil, « banniere » pour les six autres. Verification faite,
 * ce repli etait partout le premier module declare. Prendre le premier plutot
 * qu'un nom ecrit en dur donne donc exactement le meme comportement, et supprime
 * la derniere difference apparente.
 *
 * Ce qui reste a chaque ecran : modules(), charger() et enregistrer(), c'est-a
 * -dire ce qu'il montre et ce qu'il en fait. Le trait declare les deux premieres
 * abstraites pour que l'oubli se voie a la compilation.
 */
trait PorteUnEcranDePage
{
    public string $langueActive = 'fr';

    public ?string $message = null;

    /** Les blocs de la page, indexes par identifiant. Le premier fait le repli. */
    abstract public function modules(): array;

    /** Relit depuis la base ce que le module courant edite. */
    abstract protected function charger(): void;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur', 'redacteur', 'lecteur']), 403);

        $this->langueActive = app()->getLocale();
        $this->charger();
    }

    /**
     * Bascule vers un autre module de la page.
     *
     * abort_unless plutot qu'un repli silencieux : un identifiant inconnu vient
     * d'une adresse fabriquee a la main, et lui repondre le premier module
     * laisserait croire qu'il existe.
     */
    public function ouvrir(string $module): void
    {
        abort_unless(array_key_exists($module, $this->modules()), 404);

        $this->module = $module;
        $this->message = null;
        $this->resetValidation();
        $this->charger();
    }

    public function moduleCourant(): array
    {
        $modules = $this->modules();

        return $modules[$this->module] ?? reset($modules);
    }

    /**
     * Les intitules des champs, pour que les messages de validation nomment ce
     * que l'editeur voit et non la cle technique.
     *
     * Deux ecrans y ajoutent leurs champs propres et redefinissent donc cette
     * methode.
     */
    protected function validationAttributes(): array
    {
        return $this->intitulesDesTextes();
    }

    /**
     * Lecteurs et redacteurs entrent dans l'ecran, mais n'y ecrivent pas.
     *
     * La garde est reverifiee a l'enregistrement : Livewire ne rejoue pas le
     * middleware de role sur /livewire/update.
     */
    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }
}
