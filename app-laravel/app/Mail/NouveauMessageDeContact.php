<?php

namespace App\Mail;

use App\Models\MessageDeContact;
use App\Models\Parametre;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Previent l'agence qu'un message est arrive par le formulaire du site.
 *
 * L'adresse du visiteur est mise en « repondre a » et NON en expediteur : un
 * message envoye au nom d'un inconnu depuis le domaine du site serait rejete
 * par la plupart des serveurs, et servirait au pire a se faire passer pour
 * quelqu'un d'autre.
 */
class NouveauMessageDeContact extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public MessageDeContact $message) {}

    public function envelope(): Envelope
    {
        $enveloppe = new Envelope(
            subject: __('Nouveau message de :nom', ['nom' => $this->message->nom]),
        );

        return $this->message->email
            ? $enveloppe->replyTo($this->message->email, $this->message->nom)
            : $enveloppe;
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.nouveau-message-de-contact',
            with: [
                'nom' => $this->message->nom,
                'telephone' => $this->message->telephone,
                'courriel' => $this->message->email,
                'sujet' => $this->message->sujet,
                'corps' => $this->message->message,
                'lien' => route('admin.messages'),
                'nomDuSite' => (string) Parametre::lire('nom_du_site', 'SCI4K'),
            ],
        );
    }
}
