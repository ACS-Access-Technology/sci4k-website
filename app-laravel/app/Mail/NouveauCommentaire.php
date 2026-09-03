<?php

namespace App\Mail;

use App\Models\Commentaire;
use App\Models\Parametre;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Previent l'agence qu'un commentaire vient d'etre depose.
 *
 * L'adresse du visiteur est mise en « repondre a » et NON en expediteur, pour
 * la meme raison que NouveauMessageDeContact : un message envoye au nom d'un
 * inconnu depuis le domaine du site serait rejete par la plupart des serveurs,
 * et servirait au pire a se faire passer pour quelqu'un d'autre.
 *
 * Le sujet dit d'emblee si le commentaire est EN LIGNE ou EN ATTENTE : c'est
 * la seule information qui decide s'il faut ouvrir le message tout de suite.
 */
class NouveauCommentaire extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Commentaire $commentaire) {}

    public function envelope(): Envelope
    {
        $enAttente = $this->commentaire->statut === Commentaire::EN_ATTENTE;

        return (new Envelope(
            subject: $enAttente
                ? __('Commentaire à vérifier de :nom', ['nom' => $this->commentaire->auteur])
                : __('Nouveau commentaire de :nom', ['nom' => $this->commentaire->auteur]),
        ))->replyTo($this->commentaire->email, $this->commentaire->auteur);
    }

    public function content(): Content
    {
        $this->commentaire->loadMissing('article');

        return new Content(
            text: 'mail.nouveau-commentaire',
            with: [
                'auteur' => $this->commentaire->auteur,
                'courriel' => $this->commentaire->email,
                'corps' => $this->commentaire->message,
                'article' => $this->commentaire->article?->titre(app()->getLocale()),
                'enAttente' => $this->commentaire->statut === Commentaire::EN_ATTENTE,
                'motif' => $this->commentaire->motif_de_mise_en_attente,
                'lien' => route('admin.pages.actualites'),
                'nomDuSite' => (string) Parametre::lire('nom_du_site', 'SCI4K'),
            ],
        );
    }
}
