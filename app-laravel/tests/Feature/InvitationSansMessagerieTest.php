<?php

/*
 * L'application ne dit pas avoir envoye ce qu'elle n'a pas envoye.
 *
 * Signale deux fois par le client : « lorsque je cree un nouvel utilisateur et
 * que je l'invite, il ne recoit jamais l'invitation sur son mail a lui ».
 *
 * La cause n'est pas un defaut d'envoi : tant que « Serveur SMTP » n'est pas
 * renseigne dans Configuration → Messagerie, Laravel ecrit les courriels dans
 * le journal au lieu de les remettre. C'est le comportement voulu pour un
 * environnement d'essai.
 *
 * CE QUI EST FAUTIF, C'EST CE QUE L'ECRAN ANNONCE. Il affichait « Invitation
 * envoyee a untel@exemple.fr », sans reserve. L'administrateur attendait donc
 * une reponse qui ne pouvait pas venir, et cherchait la panne du mauvais cote —
 * exactement ce qui s'est produit.
 */

use App\Livewire\Admin\UtilisateurListe;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();

    Role::findOrCreate('administrateur');
    Role::findOrCreate('redacteur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/** Invitation envoyée en présence d'un vrai serveur SMTP : le message l'affirme. */
it('annonce l envoi quand la messagerie est configuree', function () {
    config(['mail.default' => 'smtp']);

    $composant = Livewire\Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Awa Koné')
        ->set('emailInvite', 'awa@exemple.ci')
        ->set('roleInvite', 'redacteur')
        ->call('inviter');

    expect($composant->get('message'))->toContain('Invitation envoyée');
});

/*
 * Sans serveur, le compte est bien cree — ce n'est pas une panne — mais le
 * message doit dire que rien n'est parti, et ou aller regler cela.
 */
it('avertit que rien ne part quand la messagerie n est pas configuree', function () {
    config(['mail.default' => 'log']);

    $composant = Livewire\Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Awa Koné')
        ->set('emailInvite', 'awa@exemple.ci')
        ->set('roleInvite', 'redacteur')
        ->call('inviter');

    expect(User::where('email', 'awa@exemple.ci')->exists())->toBeTrue();

    // On lit la propriete plutot que le rendu : le message peut etre affiche
    // par un composant d'alerte qui le decoupe ou l'echappe.
    $message = $composant->get('message');

    expect($message)->not->toContain('Invitation envoyée')
        ->and($message)->toContain('Messagerie');
});
