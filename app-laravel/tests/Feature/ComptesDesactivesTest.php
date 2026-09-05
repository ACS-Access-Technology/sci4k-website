<?php

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('editeur');
});

/*
 * Un compte desactive n'entre pas, et ne reste pas.
 *
 * Le refus pose sur l'authentification par mot de passe ne regarde que le
 * MOMENT de la connexion, et le controleur de passkey ne le traverse meme pas :
 * il appelle $guard->login() directement. Un employe parti, dont le compte
 * etait passe en inactif, entrait donc encore dans le backoffice par sa cle,
 * avec son role intact.
 *
 * Deux garde-fous ont ete poses ce matin — le refus a la connexion par passkey,
 * et un middleware qui coupe une session EN COURS. Aucun test ne les couvrait :
 * je l'avais annonce et pas fait. C'est le genre de garde dont on ne s'apercoit
 * qu'il ne sert plus que le jour ou quelqu'un en profite.
 */
it('refuse la connexion par passkey a un compte desactive', function () {
    $inactif = User::factory()->create(['statut' => User::INACTIF]);

    // On interroge le point d'accroche que le paquet consulte reellement, et
    // non notre propre methode : c'est le branchement qui est en cause, pas la
    // regle qu'il applique.
    expect(Passkeys::allowsLogin(Request::create('/'), passkeyDe($inactif)))->toBeFalse();
});

it('laisse entrer un compte actif', function () {
    $actif = User::factory()->create(['statut' => User::ACTIF]);

    expect(Passkeys::allowsLogin(Request::create('/'), passkeyDe($actif)))->toBeTrue();
});

/*
 * Le cas qui compte le plus : on desactive un compte parce qu'il se passe
 * quelque chose MAINTENANT, pas pour empecher une connexion demain. Sans le
 * middleware, l'interesse continuait de naviguer et d'enregistrer jusqu'a
 * l'expiration de sa session — deux heures d'inactivite, remises a zero a
 * chaque requete, donc potentiellement toute la journee.
 */
it('coupe la session en cours d un compte que l on vient de desactiver', function () {
    $compte = User::factory()->create(['statut' => User::ACTIF]);
    $compte->assignRole('editeur');

    $this->actingAs($compte)->get('/dashboard')->assertOk();

    $compte->update(['statut' => User::INACTIF]);

    $this->actingAs($compte)->get('/dashboard')->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

it('coupe la session partout, y compris sur le site public', function () {
    // Le middleware vit sur le groupe « web » : il agit donc AUSSI sur les
    // pages publiques. Un ancien employe encore connecte y est deconnecte et
    // renvoye a l'ecran de connexion.
    //
    // Ce n'est pas ideal — il ne demandait qu'a lire le site — mais la
    // propriete qui compte est tenue : la session est coupee partout, et non
    // seulement a la porte du backoffice qu'il aurait pu contourner. Le
    // deconnecter sans le renvoyer demanderait de distinguer les deux
    // familles de routes, ce qui n'a pas ete fait.
    $inactif = User::factory()->create(['statut' => User::INACTIF]);

    $this->actingAs($inactif)->get('/')->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

/** Une cle rattachee a ce compte, telle que le paquet la presente au refus. */
function passkeyDe(User $utilisateur): Passkey
{
    $passkey = new Passkey;
    $passkey->setRelation('user', $utilisateur);

    return $passkey;
}
