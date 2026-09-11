<?php

/*
 * La raison d'un refus renseigne sans rien exposer.
 *
 * Le message d'une exception de transport porte plus que la reponse du
 * serveur : il cite l'IDENTIFIANT employe. Vu de mes propres yeux pendant la
 * configuration de Resend —
 *
 *     Failed to authenticate on SMTP server with username "resend" using the
 *     following authenticators: "LOGIN", "PLAIN". Authenticator "LOGIN"
 *     returned "Expected response code "235" but got code "535"...
 *
 * Le rendre tel quel a l'ecran le met sur une capture, et les captures
 * circulent — dans un ticket, dans une conversation. C'est ainsi qu'un secret
 * sort, et deux l'ont deja fait aujourd'hui sur ce projet.
 *
 * On garde donc CE QUI RENSEIGNE — le code et le texte renvoyes par le serveur,
 * «  550 Invalid `to` field » — et on laisse tomber le reste, qui ne dit rien a
 * l'administrateur et beaucoup a qui lirait par-dessus son epaule.
 */

use App\Livewire\Admin\UtilisateurListe;

/** La methode est protegee : on l'atteint par une classe anonyme qui l'expose. */
function raisonLisible(string $brut): string
{
    $composant = new class extends UtilisateurListe
    {
        public function exposer(string $brut): string
        {
            return $this->raisonDuRefus($brut);
        }
    };

    return $composant->exposer($brut);
}

it('retient la reponse du serveur', function () {
    expect(raisonLisible('Expected response code "250" but got code "550", with message "550 Invalid `to` field. Please use our testing email address instead.".'))
        ->toContain('550')
        ->toContain('Invalid `to` field');
});

it('ne repete pas l identifiant employe', function () {
    $brut = 'Failed to authenticate on SMTP server with username "resend" using the following '
        .'authenticators: "LOGIN", "PLAIN". Authenticator "LOGIN" returned "Expected response '
        .'code "235" but got code "535", with message "535 Authentication credentials invalid".".';

    $raison = raisonLisible($brut);

    expect($raison)->not->toContain('username')
        ->and($raison)->not->toContain('resend')
        ->and($raison)->toContain('535');
});

/** Un message sans reponse de serveur reste comprehensible, et borne. */
it('borne un message qui ne dit rien de reconnaissable', function () {
    $raison = raisonLisible(str_repeat('detail interne ', 200));

    expect(strlen($raison))->toBeLessThanOrEqual(200);
});
