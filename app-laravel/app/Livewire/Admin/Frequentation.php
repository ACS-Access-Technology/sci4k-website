<?php

namespace App\Livewire\Admin;

use App\Models\Visite;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Frequentation extends Component
{
    public int $periode = 30;

    public function render(): View
    {
        $depuis = now()->subDays($this->periode - 1)->startOfDay();
        $visites = Visite::where('visitee_le', '>=', $depuis);

        return view('livewire.admin.frequentation', [
            'total' => (clone $visites)->count(),
            'visiteurs' => (clone $visites)->distinct('session_hash')->count('session_hash'),
            'pages' => (clone $visites)->selectRaw('chemin, COUNT(*) AS total')->groupBy('chemin')->orderByDesc('total')->limit(10)->get(),
            'parJour' => (clone $visites)->selectRaw('DATE(visitee_le) AS jour, COUNT(*) AS total')->groupBy('jour')->orderBy('jour')->get(),
        ])->title(__('Fréquentation'));
    }
}
