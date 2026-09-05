<?php

namespace App\Mail;

use App\Models\DemandeDeVisite;
use App\Models\Parametre;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Previent l'agence qu'un rendez-vous est demande depuis la fiche d'un bien.
 *
 * Les messages de contact et les commentaires d'articles declenchaient chacun
 * leur alerte ; les demandes de visite, non. Elles s'empilaient dans l'ecran
 * « Demandes de visite » sans que personne ne soit prevenu — c'est pourtant le
 * seul formulaire du site ou le visiteur propose une DATE. Une demande vue
 * trois jours plus tard est une demande perdue.
 *
 * Comme pour les messages, l'adresse du visiteur est mise en « repondre a » et
 * NON en expediteur : un message envoye au nom d'un inconnu depuis le domaine
 * du site serait rejete par la plupart des serveurs.
 */
class NouvelleDemandeDeVisite extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public DemandeDeVisite $demande) {}

    public function envelope(): Envelope
    {
        $enveloppe = new Envelope(
            subject: __('Demande de visite de :nom', ['nom' => $this->demande->nom]),
        );

        return $this->demande->email
            ? $enveloppe->replyTo($this->demande->email, $this->demande->nom)
            : $enveloppe;
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.nouvelle-demande-de-visite',
            with: [
                'nom' => $this->demande->nom,
                'telephone' => $this->demande->telephone,
                'courriel' => $this->demande->email,
                // L'intitule recopie, et non le bien lie : la ligne doit rester
                // lisible quand le bien sera vendu puis retire du catalogue.
                'bien' => $this->demande->bien_intitule,
                'creneau' => $this->demande->creneau_souhaite?->format('d/m/Y'),
                'corps' => $this->demande->message,
                'lien' => route('admin.visites'),
                'nomDuSite' => (string) Parametre::lire('nom_du_site', 'SCI4K'),
            ],
        );
    }
}
