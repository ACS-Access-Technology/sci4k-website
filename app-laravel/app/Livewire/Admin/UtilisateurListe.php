<?php

namespace App\Livewire\Admin;

use App\Mail\InvitationAuBackoffice;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Comptes du backoffice.
 *
 * Le seul ecran du projet qui donne ou retire un acces. Trois garde-fous s'y
 * ajoutent a ceux des autres ecrans, et chacun repond a une facon precise de
 * se tirer une balle dans le pied :
 *
 *   1. un administrateur ne peut ni se desactiver, ni se retirer son propre
 *      role, ni se supprimer — sinon il se ferme la porte, et il faut passer
 *      par la base pour la rouvrir ;
 *   2. le DERNIER administrateur ne peut pas etre retrograde ni desactive,
 *      meme par un autre : il ne resterait plus personne pour gerer les
 *      comptes ;
 *   3. aucun mot de passe n'est saisi ici. Inviter envoie un lien de
 *      definition ; le mot de passe n'est connu que de son titulaire.
 */
#[Layout('layouts.app')]
class UtilisateurListe extends Component
{
    /** Recherche sur le nom et l'adresse. */
    public string $recherche = '';

    /** Filtre de role, ou '' pour tous. */
    public string $roleFiltre = '';

    /* --------------------------------------------------- invitation */

    public bool $panneauInvitation = false;

    public string $nomInvite = '';

    public string $emailInvite = '';

    public string $roleInvite = 'redacteur';

    public ?string $message = null;

    protected function peutGerer(): bool
    {
        return (bool) auth()->user()?->hasRole('administrateur');
    }

    public function mount(): void
    {
        abort_unless($this->peutGerer(), 403);
    }

    /** Combien d'administrateurs actifs reste-t-il ? */
    protected function administrateursRestants(): int
    {
        return User::role('administrateur')->where('statut', User::ACTIF)->count();
    }

    /**
     * Ce compte est-il le dernier administrateur en etat de se connecter ?
     *
     * Le retrograder ou le desactiver ne laisserait plus personne pour gerer
     * les comptes, ni pour toucher a la configuration.
     */
    protected function estLeDernierAdministrateur(User $compte): bool
    {
        return $compte->hasRole('administrateur')
            && $compte->statut === User::ACTIF
            && $this->administrateursRestants() <= 1;
    }

    /* --------------------------------------------------- invitation */

    public function ouvrirInvitation(): void
    {
        abort_unless($this->peutGerer(), 403);

        $this->reset(['nomInvite', 'emailInvite', 'message']);
        $this->roleInvite = 'redacteur';
        $this->resetValidation();
        $this->panneauInvitation = true;
    }

    public function inviter(): void
    {
        abort_unless($this->peutGerer(), 403);

        $this->validate([
            'nomInvite' => ['required', 'string', 'max:120'],
            'emailInvite' => ['required', 'email', 'max:160', 'unique:users,email'],
            'roleInvite' => ['required', Rule::in(array_keys(User::descriptionsDesRoles()))],
        ], attributes: [
            'nomInvite' => __('le nom'),
            'emailInvite' => __('l’adresse e-mail'),
            'roleInvite' => __('le rôle'),
        ]);

        // Le mot de passe pose ici est ALEATOIRE et jamais communique : il ne
        // sert qu'a remplir une colonne obligatoire. Le compte n'est utilisable
        // qu'apres que son titulaire a suivi le lien et choisi le sien.
        $compte = User::create([
            'name' => $this->nomInvite,
            'email' => $this->emailInvite,
            'password' => Str::password(32),
            'statut' => User::INVITE,
        ]);

        $compte->assignRole($this->roleInvite);

        $jeton = Password::broker()->createToken($compte);

        Mail::to($compte->email)->send(new InvitationAuBackoffice(
            $compte,
            route('password.reset', ['token' => $jeton, 'email' => $compte->email]),
            (string) auth()->user()?->name,
        ));

        $this->panneauInvitation = false;
        $this->message = __('Invitation envoyée à :adresse.', ['adresse' => $compte->email]);
    }

    /* --------------------------------------------------- actions */

    public function changerLeRole(int $id, string $role): void
    {
        abort_unless($this->peutGerer(), 403);
        abort_unless(array_key_exists($role, User::descriptionsDesRoles()), 404);

        $compte = User::findOrFail($id);

        if ($compte->is(auth()->user())) {
            $this->message = __('Vous ne pouvez pas changer votre propre rôle.');

            return;
        }

        if ($role !== 'administrateur' && $this->estLeDernierAdministrateur($compte)) {
            $this->message = __('Il doit rester au moins un administrateur actif.');

            return;
        }

        $compte->syncRoles([$role]);
        $this->message = __('Rôle de :nom mis à jour.', ['nom' => $compte->name]);
    }

    public function basculerLActivation(int $id): void
    {
        abort_unless($this->peutGerer(), 403);

        $compte = User::findOrFail($id);

        if ($compte->is(auth()->user())) {
            $this->message = __('Vous ne pouvez pas désactiver votre propre compte.');

            return;
        }

        if ($compte->statut !== User::INACTIF && $this->estLeDernierAdministrateur($compte)) {
            $this->message = __('Il doit rester au moins un administrateur actif.');

            return;
        }

        // Un compte invite qu'on desactive redevient invite s'il est reactive :
        // il n'a toujours pas choisi de mot de passe, et le dire « actif »
        // laisserait croire qu'il s'est connecte.
        $compte->statut = $compte->statut === User::INACTIF
            ? ($compte->derniere_connexion_a ? User::ACTIF : User::INVITE)
            : User::INACTIF;

        $compte->save();

        $this->message = $compte->statut === User::INACTIF
            ? __('Compte de :nom désactivé.', ['nom' => $compte->name])
            : __('Compte de :nom réactivé.', ['nom' => $compte->name]);
    }

    public function renvoyerLInvitation(int $id): void
    {
        abort_unless($this->peutGerer(), 403);

        $compte = User::findOrFail($id);

        if ($compte->statut !== User::INVITE) {
            $this->message = __('Ce compte s’est déjà connecté : il n’a plus besoin d’invitation.');

            return;
        }

        Mail::to($compte->email)->send(new InvitationAuBackoffice(
            $compte,
            route('password.reset', [
                'token' => Password::broker()->createToken($compte),
                'email' => $compte->email,
            ]),
            (string) auth()->user()?->name,
        ));

        $this->message = __('Invitation renvoyée à :adresse.', ['adresse' => $compte->email]);
    }

    public function supprimer(int $id): void
    {
        abort_unless($this->peutGerer(), 403);

        $compte = User::findOrFail($id);

        if ($compte->is(auth()->user())) {
            $this->message = __('Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        if ($this->estLeDernierAdministrateur($compte)) {
            $this->message = __('Il doit rester au moins un administrateur actif.');

            return;
        }

        // Ses articles ne partent PAS avec lui : la contrainte les detache. Le
        // site perdrait du contenu en ligne parce qu'un employe est parti.
        $nom = $compte->name;
        $compte->delete();

        $this->message = __('Compte de :nom supprimé. Ses articles restent en ligne.', ['nom' => $nom]);
    }

    /* --------------------------------------------------- rendu */

    public function render(): View
    {
        $comptes = User::query()
            ->with('roles')
            ->when($this->recherche !== '', function ($requete) {
                $motif = '%'.trim($this->recherche).'%';
                $requete->where(fn ($r) => $r->where('name', 'like', $motif)->orWhere('email', 'like', $motif));
            })
            ->when($this->roleFiltre !== '', fn ($requete) => $requete->role($this->roleFiltre))
            ->orderBy('name')
            ->get();

        return view('livewire.admin.utilisateur-liste', [
            'comptes' => $comptes,
            'roles' => User::descriptionsDesRoles(),
            'total' => User::count(),
            'actifs' => User::where('statut', User::ACTIF)->count(),
            'moiMeme' => auth()->id(),
        ])->title(__('Utilisateurs'));
    }
}
