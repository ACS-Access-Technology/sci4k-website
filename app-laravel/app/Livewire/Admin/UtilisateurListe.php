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

        // UN REFUS DU SERVEUR NE DOIT PAS CASSER L'ECRAN.
        //
        // L'envoi n'etait protege par rien : un « 550 » remontait en page
        // d'erreur, APRES la creation du compte. L'administrateur voyait un
        // ecran plante, et son second essai butait sur « adresse deja prise »
        // puisque le compte existait bel et bien.
        //
        // Constate en configurant Resend : l'expediteur de demonstration ne
        // delivre qu'a l'adresse proprietaire du compte, et refuse tout autre
        // destinataire.
        $echec = $this->remettre($compte, $jeton);

        $this->panneauInvitation = false;

        // ON NE DIT PAS AVOIR ENVOYE CE QU'ON N'A PAS ENVOYE.
        //
        // Tant que « Serveur SMTP » n'est pas renseigne, Laravel ecrit les
        // courriels dans le journal au lieu de les remettre — comportement
        // voulu pour un environnement d'essai. L'ecran affichait pourtant
        // « Invitation envoyee », sans reserve : l'administrateur attendait une
        // reponse qui ne pouvait pas venir, et cherchait la panne du mauvais
        // cote. C'est arrive.
        //
        // Le compte, lui, est bel et bien cree : ce n'est pas un echec, c'est
        // une remise qui n'a pas eu lieu.
        if ($echec !== null) {
            // Le compte reste : c'est la remise qui a echoue, pas la creation.
            // Et la raison vient du serveur — elle designe le vrai obstacle,
            // qu'aucun message maison ne saurait deviner.
            $this->message = __('Compte créé pour :nom, mais l’invitation n’a pas pu être remise : :raison', [
                'nom' => $compte->name,
                'raison' => $echec,
            ]);

            return;
        }

        $this->message = $this->messagerieRemetVraiment()
            ? __('Invitation envoyée à :adresse.', ['adresse' => $compte->email])
            : __('Compte créé pour :adresse, mais AUCUN courriel n’est parti : la messagerie n’est pas configurée. Renseignez « Serveur SMTP » dans Configuration → Messagerie, puis renvoyez l’invitation.', ['adresse' => $compte->email]);
    }

    /**
     * Remet l'invitation, et rend la raison du refus s'il y en a une.
     *
     * @return string|null La raison donnee par le serveur, ou null si la
     *                     remise a ete acceptee.
     */
    protected function remettre(User $compte, string $jeton): ?string
    {
        try {
            Mail::to($compte->email)->send(new InvitationAuBackoffice(
                $compte,
                route('password.reset', ['token' => $jeton, 'email' => $compte->email]),
                (string) auth()->user()?->name,
            ));

            return null;
        } catch (\Throwable $e) {
            // Journalise pour qu'une remise refusee laisse une trace ailleurs
            // que sur un ecran qu'on va quitter.
            report($e);

            return $this->raisonDuRefus($e->getMessage());
        }
    }

    /**
     * Ce qu'on montre d'un refus : la reponse du serveur, et rien d'autre.
     *
     * Le message d'une exception de transport porte plus que cette reponse :
     * il cite l'IDENTIFIANT employe. Vu pendant la configuration de Resend —
     * « Failed to authenticate on SMTP server with username "resend"… ».
     *
     * Le rendre tel quel a l'ecran le met sur une capture, et les captures
     * circulent : dans un ticket, dans une conversation. C'est ainsi qu'un
     * secret sort, et deux l'ont deja fait sur ce projet.
     *
     * On garde donc ce qui renseigne — le code et le texte du serveur, « 550
     * Invalid `to` field » — et on laisse le reste, qui ne dit rien a
     * l'administrateur et beaucoup a qui lit par-dessus son epaule.
     */
    protected function raisonDuRefus(string $brut): string
    {
        // La reponse SMTP : trois chiffres suivis de son texte. On prend la
        // DERNIERE, les transports imbriquant la reponse reelle dans leur
        // propre message.
        if (preg_match_all('/\b([45]\d{2})\s+([^"\n]{3,140})/', $brut, $trouves, PREG_SET_ORDER) > 0) {
            $dernier = end($trouves);

            return trim($dernier[1].' '.rtrim(trim($dernier[2]), '".'));
        }

        // Rien de reconnaissable : on borne, plutot que de deverser un message
        // interne dont la longueur n'a pas de limite connue.
        return Str::limit($brut, 160);
    }

    /**
     * La messagerie remet-elle vraiment, ou se contente-t-elle d'ecrire ?
     *
     * « log » ecrit dans le journal, « array » garde en memoire : aucun des
     * deux ne fait sortir un courriel du serveur.
     */
    protected function messagerieRemetVraiment(): bool
    {
        return ! in_array(config('mail.default'), ['log', 'array'], true);
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

        $echec = $this->remettre($compte, Password::broker()->createToken($compte));

        if ($echec !== null) {
            $this->message = __('Le renvoi a été refusé : :raison', ['raison' => $echec]);

            return;
        }

        // Meme reserve qu'a la premiere invitation : un renvoi qui ne part pas
        // doit le dire, sans quoi l'administrateur reessaie indefiniment.
        $this->message = $this->messagerieRemetVraiment()
            ? __('Invitation renvoyée à :adresse.', ['adresse' => $compte->email])
            : __('Aucun courriel n’est parti : la messagerie n’est pas configurée. Renseignez « Serveur SMTP » dans Configuration → Messagerie.');
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
