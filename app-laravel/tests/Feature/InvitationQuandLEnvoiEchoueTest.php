<?php

/*
 * Un envoi refuse par le serveur ne casse pas l'ecran.
 *
 * Constate en configurant Resend : l'expediteur de demonstration
 * « onboarding@resend.dev » ne delivre qu'a l'adresse proprietaire du compte.
 * Toute invitation adressee a quelqu'un d'autre est refusee par un « 550 ».
 *
 * Or l'envoi n'etait protege par rien. Le refus remontait donc en page
 * d'erreur — APRES la creation du compte. L'administrateur voyait un ecran
 * plante, et son second essai butait sur « cette adresse est deja prise »,
 * puisque le compte existait bel et bien.
 *
 * Le compte cree n'est pas une erreur : c'est la remise qui a echoue. On garde
 * donc le compte, on le dit, et on repete la raison donnee par le serveur —
 * elle designe le vrai obstacle, qu'aucun message maison ne saurait deviner.
 */

use App\Livewire\Admin\UtilisateurListe;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Exception\TransportException;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    Role::findOrCreate('redacteur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    config(['mail.default' => 'smtp']);
});

/** Le serveur refuse : l'ecran tient, et le compte reste. */
it('garde le compte et explique quand la remise est refusee', function () {
    Mail::shouldReceive('to->send')
        ->andThrow(new TransportException('550 Invalid `to` field. Please use our testing email address instead.'));

    $composant = Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Awa Koné')
        ->set('emailInvite', 'awa@exemple.ci')
        ->set('roleInvite', 'redacteur')
        ->call('inviter');

    // Le compte existe : c'est la remise qui a echoue, pas la creation.
    expect(User::where('email', 'awa@exemple.ci')->exists())->toBeTrue();

    $message = $composant->get('message');

    expect($message)->toContain('Awa Koné')
        ->and($message)->toContain('550')
        ->and($message)->not->toContain('Invitation envoyée');
});

/** Le renvoi suit la meme regle : il informe au lieu de casser. */
it('explique aussi quand le renvoi est refuse', function () {
    $compte = User::factory()->create(['email' => 'awa@exemple.ci', 'statut' => User::INVITE]);
    $compte->assignRole('redacteur');

    Mail::shouldReceive('to->send')
        ->andThrow(new TransportException('550 Invalid `to` field.'));

    $composant = Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('renvoyerLInvitation', $compte->id);

    expect($composant->get('message'))->toContain('550')
        ->and($composant->get('message'))->not->toContain('Invitation renvoyée');
});
