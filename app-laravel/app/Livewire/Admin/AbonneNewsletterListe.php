<?php

namespace App\Livewire\Admin;

use App\Models\AbonneNewsletter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Response;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Abonnes a la lettre d'information.
 *
 * L'ecran ne sert qu'a deux choses : voir combien d'adresses ont ete recueillies,
 * et les exporter pour les verser dans l'outil d'envoi. Il n'y a rien a
 * « modifier » chez un abonne — il n'a saisi qu'une adresse.
 *
 * On peut en revanche DESINSCRIRE quelqu'un qui le demande, sans effacer la
 * ligne : garder la trace du retrait est ce qui empeche de le reinscrire par
 * erreur, et c'est aussi ce qu'on doit pouvoir montrer si l'interesse le
 * demande.
 */
#[Layout('layouts.app')]
class AbonneNewsletterListe extends Component
{
    public string $recherche = '';

    /** Inclure les adresses desinscrites. */
    public bool $avecDesinscrits = false;

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function basculerLAbonnement(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        $abonne = AbonneNewsletter::findOrFail($id);

        $abonne->desinscrit_a = $abonne->estDesinscrit() ? null : now();
        $abonne->save();

        $this->message = $abonne->estDesinscrit()
            ? __('Adresse désinscrite.')
            : __('Adresse réinscrite.');
    }

    /**
     * Exporte les adresses actives au format CSV.
     *
     * Seules les ACTIVES : exporter une adresse desinscrite reviendrait a lui
     * reecrire, ce que la desinscription interdit precisement.
     *
     * Deux colonnes, et la seconde est aussi importante que la premiere. Les
     * lettres partent d'un outil externe, alimente par cet export : c'est donc
     * le SEUL endroit par ou le lien de desinscription peut atteindre le pied
     * des messages. Un export qui ne porte que des adresses produit des envois
     * dont on ne peut pas sortir.
     */
    public function exporter(): StreamedResponse
    {
        abort_unless($this->peutEcrire(), 403);

        $abonnes = AbonneNewsletter::actifs()->orderBy('email')->get();

        return Response::streamDownload(function () use ($abonnes) {
            $sortie = fopen('php://output', 'w');
            fputcsv($sortie, ['email', 'lien_desinscription']);

            foreach ($abonnes as $abonne) {
                fputcsv($sortie, [$abonne->email, $abonne->lienDeDesinscription()]);
            }

            fclose($sortie);
        }, 'abonnes-newsletter-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function render(): View
    {
        $abonnes = AbonneNewsletter::query()
            ->when(! $this->avecDesinscrits, fn ($r) => $r->actifs())
            ->when($this->recherche !== '', fn ($r) => $r->where('email', 'like', '%'.trim($this->recherche).'%'))
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('livewire.admin.abonne-newsletter-liste', [
            'abonnes' => $abonnes,
            'peutEcrire' => $this->peutEcrire(),
            'statistiques' => [
                ['intitule' => __('Abonnés actifs'), 'valeur' => AbonneNewsletter::actifs()->count()],
                [
                    'intitule' => __('Désinscrits'),
                    'valeur' => AbonneNewsletter::whereNotNull('desinscrit_a')->count(),
                ],
                [
                    'intitule' => __('Inscrits ce mois'),
                    'valeur' => AbonneNewsletter::where('created_at', '>=', now()->startOfMonth())->count(),
                ],
            ],
        ])->title(__('Abonnés newsletter'));
    }
}
