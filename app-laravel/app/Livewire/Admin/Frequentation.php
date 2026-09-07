<?php

namespace App\Livewire\Admin;

use App\Models\PageQuotidienne;
use App\Models\Visite;
use App\Models\VisiteurQuotidien;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * L'ecran de frequentation, qui lit deux sources selon la periode demandee.
 *
 * Le detail des visites n'est conserve que Visite::JOURS_DE_DETAIL jours. En
 * deca, l'ecran le lit tel quel et toutes ses mesures sont exactes au visiteur
 * pres. Au-dela, il lit les agregats quotidiens pour les jours clos et le
 * detail pour la journee en cours — les deux ne se recouvrent pas, donc rien
 * n'est compte deux fois.
 *
 * UNE MESURE CHANGE DE SENS en passant aux agregats. Les pages vues
 * s'additionnent : une somme de sommes reste une somme. Les visiteurs, non —
 * quelqu'un qui revient trois jours de suite compte une fois par jour, mais
 * reste une seule personne sur la semaine. Sur la longue periode, le chiffre
 * est donc un cumul de visiteurs quotidiens et non un nombre de personnes.
 * L'ecran le signale plutot que d'afficher le nombre le plus flatteur sans
 * prevenir.
 */
#[Layout('layouts.app')]
class Frequentation extends Component
{
    public int $periode = 30;

    public function render(): View
    {
        $surAgregats = $this->periode > Visite::JOURS_DE_DETAIL;
        $depuis = now()->subDays($this->periode - 1)->startOfDay();

        $donnees = $surAgregats
            ? $this->depuisLesAgregats($depuis)
            : $this->depuisLeDetail($depuis);

        return view('livewire.admin.frequentation', [
            ...$donnees,
            'cumulDeVisiteurs' => $surAgregats,
        ])->title(__('Fréquentation'));
    }

    /**
     * La periode tient dans le detail conserve : tout se compte a la source.
     *
     * @return array<string, mixed>
     */
    protected function depuisLeDetail(CarbonInterface $depuis): array
    {
        $visites = Visite::where('visitee_le', '>=', $depuis);

        return [
            'total' => (clone $visites)->count(),
            'visiteurs' => (clone $visites)->distinct('session_hash')->count('session_hash'),
            'pages' => (clone $visites)->selectRaw('chemin, COUNT(*) AS total')->groupBy('chemin')->orderByDesc('total')->limit(10)->get(),
            'parJour' => (clone $visites)->selectRaw('DATE(visitee_le) AS jour, COUNT(*) AS total')->groupBy('jour')->orderBy('jour')->get(),
        ];
    }

    /**
     * La periode deborde le detail : les jours clos viennent des agregats.
     *
     * @return array<string, mixed>
     */
    protected function depuisLesAgregats(CarbonInterface $depuis): array
    {
        $premierJour = $depuis->format('Y-m-d');
        $aujourdhui = now()->startOfDay();

        $agregees = PageQuotidienne::where('jour', '>=', $premierJour)->get();

        // La journee en cours n'a pas encore d'agregat : son detail est la
        // seule source, et il ne recouvre aucun jour clos.
        $duJour = DB::table('visites')
            ->where('visitee_le', '>=', $aujourdhui)
            ->selectRaw('chemin, COUNT(*) AS total')
            ->groupBy('chemin')
            ->get();

        $parChemin = $agregees->groupBy('chemin')->map->sum('pages_vues');
        foreach ($duJour as $ligne) {
            $parChemin[$ligne->chemin] = ($parChemin[$ligne->chemin] ?? 0) + (int) $ligne->total;
        }

        $parJour = $agregees->groupBy('jour')->map->sum('pages_vues');
        if ($vuesDuJour = $duJour->sum('total')) {
            $parJour[$aujourdhui->format('Y-m-d')] = (int) $vuesDuJour;
        }

        return [
            'total' => (int) $parChemin->sum(),
            'visiteurs' => (int) VisiteurQuotidien::where('jour', '>=', $premierJour)->sum('visiteurs')
                + Visite::where('visitee_le', '>=', $aujourdhui)->distinct('session_hash')->count('session_hash'),
            'pages' => $this->enLignes($parChemin, 'chemin')->sortByDesc('total')->take(10)->values(),
            'parJour' => $this->enLignes($parJour, 'jour')->sortBy('jour')->values(),
        ];
    }

    /**
     * Rend a la vue la forme qu'elle attend deja : des objets `$ligne->total`.
     *
     * @param  Collection<string, int>  $comptages
     * @return Collection<int, \stdClass>
     */
    protected function enLignes(Collection $comptages, string $cle): Collection
    {
        return $comptages->map(fn (int $total, string $valeur) => (object) [$cle => $valeur, 'total' => $total])->values();
    }
}
