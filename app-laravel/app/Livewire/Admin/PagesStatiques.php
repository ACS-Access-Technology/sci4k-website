<?php

namespace App\Livewire\Admin;

use App\Models\PageStatique;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PagesStatiques extends Component
{
    public string $page = 'contact';
    public string $titreFr = '';
    public string $titreEn = '';
    public string $contenuFr = '';
    public string $contenuEn = '';
    public bool $publie = true;

    public function mount(): void
    {
        $this->charger();
    }

    public function updatedPage(): void
    {
        $this->charger();
    }

    /**
     * Charge la page choisie, apres avoir verifie QUI demande et LAQUELLE.
     *
     * Les deux controles manquaient ici, alors qu'enregistrer() en portait un.
     * Or cette methode ecrit aussi : firstOrCreate() cree la ligne absente.
     * Un compte sans droit d'edition pouvait donc, en fixant la propriete
     * publique $page depuis le navigateur, semer autant de lignes qu'il
     * voulait sur des slugs que le site public ne sert jamais.
     */
    protected function charger(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);
        abort_unless(in_array($this->page, PageStatique::slugsEditables(), true), 404);

        $page = PageStatique::firstOrCreate(['slug' => $this->page], [
            'titre_fr' => ucfirst($this->page),
            'titre_en' => ucfirst($this->page),
            'contenu_fr' => '',
            'contenu_en' => '',
        ]);
        $this->titreFr = $page->titre_fr;
        $this->titreEn = $page->titre_en;
        $this->contenuFr = (string) $page->contenu_fr;
        $this->contenuEn = (string) $page->contenu_en;
        $this->publie = $page->publie;
    }

    public function enregistrer(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['administrateur', 'editeur']), 403);
        abort_unless(in_array($this->page, PageStatique::slugsEditables(), true), 404);
        $this->validate([
            'titreFr' => ['required', 'string', 'max:190'],
            'titreEn' => ['nullable', 'string', 'max:190'],
            'contenuFr' => ['nullable', 'string', 'max:50000'],
            'contenuEn' => ['nullable', 'string', 'max:50000'],
        ]);
        PageStatique::updateOrCreate(['slug' => $this->page], [
            'titre_fr' => $this->titreFr, 'titre_en' => $this->titreEn,
            'contenu_fr' => $this->contenuFr, 'contenu_en' => $this->contenuEn,
            'publie' => $this->publie,
        ]);
        $this->dispatch('toast', message: __('Page enregistrée.'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.pages-statiques')->title(__('Pages éditables'));
    }
}
