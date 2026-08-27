<?php

namespace App\Livewire\Public;

use App\Models\Bien;
use App\Models\Referentiel;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Le catalogue des biens, cote visiteur.
 *
 * PREMIERE PAGE PUBLIQUE EN LIVEWIRE, et c'est le filtrage qui l'impose.
 * Jusqu'ici le site rendait ses six biens d'un bloc puis les masquait en
 * JavaScript : tout le catalogue traversait le reseau a chaque visite, et
 * l'ordinateur du visiteur faisait le tri. La maquette du backoffice annonce
 * 245 biens ; a ce compte-la, la page devient lourde avant meme d'etre lue, et
 * un telephone sur reseau lent en paie le prix.
 *
 * Les filtres vivent dans l'ADRESSE. Une recherche devient donc un lien qu'on
 * envoie a quelqu'un, qu'on met en favori, et qu'un moteur peut indexer — trois
 * choses qu'un filtre en memoire ne sait pas faire.
 */
#[Layout('public.layout-livewire')]
class CatalogueDesBiens extends Component
{
    use WithPagination;

    #[Url(as: 'offre', except: '')]
    public string $offre = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'zone', except: '')]
    public string $zone = '';

    #[Url(as: 'pieces', except: '')]
    public string $pieces = '';

    #[Url(as: 'surface', except: '')]
    public string $surface = '';

    public function updating($nom): void
    {
        if (in_array($nom, ['offre', 'type', 'zone', 'pieces', 'surface'], true)) {
            $this->resetPage();
        }
    }

    public function reinitialiser(): void
    {
        $this->reset(['offre', 'type', 'zone', 'pieces', 'surface']);
        $this->resetPage();
    }

    public function render(): View
    {
        $langue = app()->getLocale();

        // Les valeurs viennent du navigateur : celles qui ne figurent pas au
        // referentiel sont ignorees plutot que passees a la requete. Une
        // adresse forgee ne doit pas vider la page sans explication.
        $connues = fn (string $famille) => Referentiel::deLaFamille($famille)->pluck('valeur')->all();

        $type = in_array($this->type, $connues('types_de_bien'), true) ? $this->type : '';
        $zone = in_array($this->zone, $connues('zones'), true) ? $this->zone : '';
        $pieces = in_array($this->pieces, $connues('tranches_pieces'), true) ? $this->pieces : '';
        $surface = in_array($this->surface, $connues('tranches_surface'), true) ? $this->surface : '';
        $offre = in_array($this->offre, array_keys(Bien::offres()), true) ? $this->offre : '';

        $biens = Bien::query()
            ->publies()
            ->with('photos')
            ->when($type !== '', fn ($r) => $r->where('type', $type))
            ->when($zone !== '', fn ($r) => $r->where('zone', $zone))
            ->when($offre !== '', fn ($r) => $r->where('offre', $offre))
            ->when($pieces !== '', fn ($r) => $r->deLaTrancheDePieces($pieces))
            ->when($surface !== '', fn ($r) => $r->deLaTrancheDeSurface($surface))
            ->ordonnes()
            ->paginate(12);

        return view('public.biens', [
            'biens' => $biens,
            'langue' => $langue,
            'types' => Referentiel::deLaFamille('types_de_bien')->visibles()->ordonnees()->get(),
            'zones' => Referentiel::deLaFamille('zones')->visibles()->ordonnees()->get(),
            'tranchesPieces' => Referentiel::deLaFamille('tranches_pieces')->visibles()->ordonnees()->get(),
            'tranchesSurface' => Referentiel::deLaFamille('tranches_surface')->visibles()->ordonnees()->get(),
            'offres' => Bien::offres(),
        ]);
    }
}
