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
 * Reponse de l'agence a un message recu par le formulaire.
 *
 * Le message d'origine est rappele en bas : le visiteur a pu ecrire il y a
 * plusieurs jours et ne plus se souvenir de ce qu'il demandait.
 */
class ReponseAuMessage extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public MessageDeContact $original,
        public string $reponse,
    ) {}

    public function envelope(): Envelope
    {
        $site = (string) Parametre::lire('nom_du_site', 'SCI4K');

        $enveloppe = new Envelope(
            subject: $this->original->sujet
                ? __('Re : :sujet', ['sujet' => $this->original->sujet])
                : __('Votre message à :site', ['site' => $site]),
        );

        // Les reponses du visiteur reviennent a l'agence, et non a l'adresse
        // technique d'envoi que personne ne releve.
        $adresse = Parametre::lire('destinataire_formulaire') ?: Parametre::lire('email_public');

        return $adresse ? $enveloppe->replyTo($adresse) : $enveloppe;
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.reponse-au-message',
            with: [
                'nom' => $this->original->nom,
                'reponse' => $this->reponse,
                'messageOriginal' => $this->original->message,
                'nomDuSite' => (string) Parametre::lire('nom_du_site', 'SCI4K'),
            ],
        );
    }
}
