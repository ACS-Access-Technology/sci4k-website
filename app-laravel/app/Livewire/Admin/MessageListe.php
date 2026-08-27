<?php

namespace App\Livewire\Admin;

use App\Mail\ReponseAuMessage;
use App\Models\MessageDeContact;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Messages recus par le formulaire de contact du site.
 *
 * Une liste a gauche, le message ouvert a droite, comme sur la maquette. Les
 * demandes de visite qu'elle montre a cote appartiennent au lot 3 : elles se
 * rattachent chacune a une fiche de bien, qui n'existe pas encore.
 */
#[Layout('layouts.app')]
class MessageListe extends Component
{
    public string $filtre = '';

    public string $recherche = '';

    /**
     * Message ouvert. Verrouille : Livewire expose toute propriete publique,
     * et celle-ci designe la ligne que les actions vont ecrire.
     */
    #[Locked]
    public ?int $ouvert = null;

    public string $reponse = '';

    public ?string $message = null;

    protected function peutEcrire(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['administrateur', 'editeur']);
    }

    public function ouvrir(int $id): void
    {
        $message = MessageDeContact::findOrFail($id);

        $this->ouvert = $message->id;
        $this->reponse = '';
        $this->message = null;

        // L'ouvrir le fait passer de « nouveau » a « en cours » : le compteur
        // des non-lus doit refleter ce que personne n'a encore regarde. Reserve
        // a qui peut ecrire — un lecteur consulte sans rien changer.
        if ($message->statut === MessageDeContact::NOUVEAU && $this->peutEcrire()) {
            // Affectation directe et non update() : `statut` est
            // volontairement hors du `fillable`, parce que le point d'entree
            // PUBLIC ecrit sur ce meme modele et ne doit pas pouvoir le fixer.
            // L'ecrire ici explicitement dit qui a le droit de le faire.
            $message->statut = MessageDeContact::EN_COURS;
            $message->save();
        }
    }

    public function fermer(): void
    {
        $this->ouvert = null;
        $this->reponse = '';
    }

    public function changerLeStatut(int $id, string $statut): void
    {
        abort_unless($this->peutEcrire(), 403);
        abort_unless(MessageDeContact::statutConnu($statut), 404);

        $message = MessageDeContact::findOrFail($id);
        $message->statut = $statut;
        $message->save();

        $this->message = __('Statut mis à jour.');
    }

    public function assigner(int $id, string $utilisateur): void
    {
        abort_unless($this->peutEcrire(), 403);

        $message = MessageDeContact::findOrFail($id);

        if ($utilisateur === '') {
            $message->assigne_a = null;
            $message->save();
            $this->message = __('Message désassigné.');

            return;
        }

        // Le compte vient du navigateur : on verifie qu'il existe ET qu'il
        // travaille dans le backoffice. Assigner a un compte sans role
        // laisserait une demande a quelqu'un qui n'y a pas acces.
        $compte = User::whereKey((int) $utilisateur)
            ->whereHas('roles', fn ($r) => $r->whereIn('name', ['administrateur', 'editeur', 'redacteur']))
            ->first();

        // abort() plutot que firstOrFail() : la reponse doit etre un 404 clair
        // et non une exception de modele qui remonte jusqu'a l'ecran.
        abort_unless($compte, 404);

        $message->assigne_a = $compte->id;
        $message->save();
        $this->message = __('Message confié à :nom.', ['nom' => $compte->name]);
    }

    public function repondre(): void
    {
        abort_unless($this->peutEcrire(), 403);

        $message = MessageDeContact::findOrFail($this->ouvert);

        $this->validate([
            'reponse' => ['required', 'string', 'max:5000'],
        ], attributes: ['reponse' => __('la réponse')]);

        if (! $message->email) {
            $this->message = __("Ce message n'a pas d'adresse e-mail : répondez par téléphone.");

            return;
        }

        try {
            Mail::to($message->email)->send(new ReponseAuMessage($message, $this->reponse));
        } catch (\Throwable $e) {
            report($e);
            $this->message = __("Échec de l'envoi : :raison", ['raison' => $e->getMessage()]);

            return;
        }

        // `repondu_a` n'est pose qu'a la PREMIERE reponse : le delai moyen
        // mesure le temps d'attente du visiteur, qui s'arrete quand on lui
        // repond, pas quand on lui reecrit.
        $message->statut = MessageDeContact::TRAITE;
        $message->repondu_a = $message->repondu_a ?? now();
        $message->save();

        $this->reponse = '';
        $this->message = __('Réponse envoyée à :adresse.', ['adresse' => $message->email]);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutEcrire(), 403);

        MessageDeContact::findOrFail($id)->delete();

        if ($this->ouvert === $id) {
            $this->ouvert = null;
        }

        $this->message = __('Message supprimé.');
    }

    public function render(): View
    {
        $messages = MessageDeContact::query()
            ->with('assigne')
            ->when($this->filtre !== '', fn ($r) => $r->where('statut', $this->filtre))
            ->when($this->recherche !== '', function ($r) {
                $motif = '%'.trim($this->recherche).'%';
                $r->where(fn ($q) => $q->where('nom', 'like', $motif)
                    ->orWhere('sujet', 'like', $motif)
                    ->orWhere('message', 'like', $motif));
            })
            ->recents()
            ->limit(50)
            ->get();

        $delai = MessageDeContact::delaiMoyenDeReponse();

        return view('livewire.admin.message-liste', [
            'messages' => $messages,
            'ouvertement' => $this->ouvert ? MessageDeContact::with('assigne')->find($this->ouvert) : null,
            'statuts' => MessageDeContact::statuts(),
            'peutEcrire' => $this->peutEcrire(),
            'collaborateurs' => User::query()
                ->whereHas('roles', fn ($r) => $r->whereIn('name', ['administrateur', 'editeur', 'redacteur']))
                ->orderBy('name')
                ->get(),
            'statistiques' => [
                ['intitule' => __('Messages reçus'), 'valeur' => MessageDeContact::count()],
                [
                    'intitule' => __('Non lus'),
                    'valeur' => MessageDeContact::nonLus()->count(),
                    'ton' => MessageDeContact::nonLus()->count() > 0 ? 'alerte' : 'neutre',
                ],
                [
                    'intitule' => __('Traités ce mois'),
                    'valeur' => MessageDeContact::where('statut', MessageDeContact::TRAITE)
                        ->where('updated_at', '>=', now()->startOfMonth())->count(),
                ],
                [
                    'intitule' => __('Délai moyen de réponse'),
                    // Le tiret plutot que « 0 h » : rien n'a encore ete
                    // repondu, et « 0 h » laisserait croire a une reponse
                    // instantanee.
                    'valeur' => $delai === null ? '—' : __(':heures h', ['heures' => $delai]),
                ],
            ],
        ])->title(__('Messages de contact'));
    }
}
