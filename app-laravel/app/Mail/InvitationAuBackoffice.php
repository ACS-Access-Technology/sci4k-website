<?php

namespace App\Mail;

use App\Models\Parametre;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invitation a rejoindre le backoffice.
 *
 * Le message porte un lien de definition de mot de passe, pas un mot de passe.
 * C'est la seule facon honnete d'ouvrir un compte : personne d'autre que son
 * titulaire ne doit connaitre son mot de passe — ni l'administrateur qui
 * invite, ni le courriel qui traverse le reseau, ni la personne qui lira sa
 * boite par-dessus son epaule.
 */
class InvitationAuBackoffice extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $invite,
        public string $lien,
        public string $invitePar,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Votre accès à l’administration de :site', [
                'site' => Parametre::lire('nom_du_site', 'SCI4K'),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.invitation-au-backoffice',
            with: [
                'nom' => $this->invite->name,
                'lien' => $this->lien,
                'invitePar' => $this->invitePar,
                'nomDuSite' => (string) Parametre::lire('nom_du_site', 'SCI4K'),
            ],
        );
    }
}
