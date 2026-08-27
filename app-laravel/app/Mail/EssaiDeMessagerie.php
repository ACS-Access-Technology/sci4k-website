<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Message d'essai envoye depuis l'ecran de configuration.
 *
 * Une classe plutot qu'un Mail::raw() : le contenu brut ne laisse rien a
 * verifier a un test — ni destinataire, ni objet —, si bien que la seule
 * mesure possible aurait ete la phrase affichee a l'ecran. Or ce qui compte
 * ici n'est pas ce que l'ecran DIT avoir envoye, mais ou le message part
 * reellement.
 */
class EssaiDeMessagerie extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $nomDuSite) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("Essai d'envoi — :site", ['site' => $this->nomDuSite]),
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.essai-de-messagerie',
            with: ['nomDuSite' => $this->nomDuSite],
        );
    }
}
