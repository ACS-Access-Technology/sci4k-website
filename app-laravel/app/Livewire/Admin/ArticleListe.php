<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/*
 * Tableau d'administration des articles.
 *
 * Contrairement a la liste publique, il montre les brouillons : c'est l'ecran
 * de travail des editeurs.
 */
#[Layout('layouts.app')]
class ArticleListe extends Component
{
    use WithPagination;

    public string $recherche = '';

    public string $categorieId = '';

    public string $statut = '';

    /** Revenir a la premiere page des qu'un filtre change. */
    public function updating($nom): void
    {
        if (in_array($nom, ['recherche', 'categorieId', 'statut'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $langue = app()->getLocale();

        $articles = Article::query()
            ->with('categorie')
            // La recherche porte sur les deux langues : un editeur anglophone
            // doit retrouver un article dont seul le titre anglais lui parle.
            ->when($this->recherche !== '', fn ($r) => $r->where(function ($q) {
                $q->where('titre_fr', 'like', '%'.$this->recherche.'%')
                    ->orWhere('titre_en', 'like', '%'.$this->recherche.'%');
            }))
            ->when($this->categorieId !== '', fn ($r) => $r->where('categorie_id', $this->categorieId))
            ->when($this->statut !== '', fn ($r) => $r->where('statut', $this->statut))
            ->orderByDesc('date_publication')
            ->paginate(20);

        return view('livewire.admin.article-liste', [
            'articles' => $articles,
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => $langue,
        ])->title(__('Articles'));
    }
}
