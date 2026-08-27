<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'statut'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_a' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ------------------------------------------------- etat du compte */

    /** Compte utilisable. */
    public const ACTIF = 'actif';

    /** Compte suspendu : il existe, il ne peut plus se connecter. */
    public const INACTIF = 'inactif';

    /**
     * Compte cree par invitation, dont la personne n'a pas encore choisi son
     * mot de passe. Ce n'est PAS un compte desactive : les confondre aurait
     * empeche de distinguer une invitation en attente d'un depart d'employe.
     */
    public const INVITE = 'invite';

    /** @return list<string> */
    public static function statuts(): array
    {
        return [self::ACTIF, self::INACTIF, self::INVITE];
    }

    /**
     * Ce compte peut-il se connecter ?
     *
     * Un compte invite le peut : c'est en suivant le lien d'invitation qu'il
     * choisit son mot de passe. Un compte inactif ne le peut plus.
     */
    public function peutSeConnecter(): bool
    {
        return $this->statut !== self::INACTIF;
    }

    /* ------------------------------------------------- roles et droits */

    /**
     * Les quatre roles du backoffice, et ce que chacun autorise REELLEMENT.
     *
     * Le texte affiche par l'ecran des utilisateurs vient d'ici, et pas d'une
     * liste ecrite dans le gabarit : une description qui vit loin de la regle
     * qu'elle decrit finit par la contredire.
     *
     * PAS `roles()` : ce nom est deja celui de la relation que le trait HasRoles
     * pose sur ce modele. La lui prendre cassait tout chargement anticipe des
     * roles, et donc tout controle de droits — releve par la suite de tests, en
     * six echecs d'un coup.
     *
     * @return array<string, string>
     */
    public static function descriptionsDesRoles(): array
    {
        return [
            'administrateur' => __('Accès complet, y compris la configuration et les utilisateurs.'),
            'editeur' => __('Crée et publie tous les contenus, sans accès aux réglages.'),
            'redacteur' => __('Crée et modifie ses propres articles. Il ne peut pas les publier : un éditeur s’en charge.'),
            'lecteur' => __('Consultation seule du tableau de bord et des listes.'),
        ];
    }

    /**
     * Ce compte peut-il PUBLIER un contenu ?
     *
     * Faux pour un redacteur, et c'est tout l'objet de ce role. Sans cette
     * regle, la description « publication soumise a validation » n'aurait ete
     * qu'une phrase dans un panneau.
     */
    public function peutPublier(): bool
    {
        return $this->hasAnyRole(['administrateur', 'editeur']);
    }

    /**
     * Ce compte ne voit-il que ses propres articles ?
     *
     * Vrai pour un redacteur seul. Un administrateur qui porterait aussi ce
     * role garde ses pleins droits — le role le plus large l'emporte.
     */
    public function limiteASesArticles(): bool
    {
        return $this->hasRole('redacteur') && ! $this->peutPublier();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
