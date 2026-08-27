<?php

namespace App\Http\Controllers;

use App\Mail\NouveauMessageDeContact;
use App\Models\MessageDeContact;
use App\Models\Parametre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Reception des messages du formulaire de contact.
 *
 * C'est le SEUL point d'ecriture ouvert au public de toute l'application. Il
 * porte donc ses propres protections, la ou les ecrans d'administration
 * s'appuient sur l'authentification :
 *
 *   - limitation de debit posee sur la route, pour qu'un envoi automatise ne
 *     remplisse pas la table ;
 *   - champ piege : un robot remplit tous les champs d'un formulaire, y compris
 *     celui qu'un humain ne voit pas. La reponse reste un succes — dire au
 *     robot qu'il a ete repere lui apprend a contourner ;
 *   - longueurs bornees, et rien de ce qui est reçu n'est renvoye dans la
 *     reponse.
 *
 * La conversation WhatsApp continue de s'ouvrir cote navigateur : le client a
 * choisi les deux canaux. Ce point d'entree ne fait qu'ajouter la trace, pour
 * qu'aucune demande ne se perde si le telephone change de main.
 */
class MessageDeContactController extends Controller
{
    public function __invoke(Request $requete): JsonResponse
    {
        $valide = $requete->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'sujet' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
            // La question de FAQ arrive par le meme point d'entree : c'est le
            // meme objet — un expediteur, une demande, une reponse attendue —
            // et le meme ecran y repond. Seule l'origine differe.
            'source' => ['nullable', Rule::in(array_keys(MessageDeContact::sources()))],
            // Le champ piege. Il doit rester vide.
            'site_web' => ['prohibited'],
        ], [
            'site_web.prohibited' => __('Envoi refusé.'),
        ]);

        $message = MessageDeContact::create($valide);

        $this->notifierLAgence($message);

        // La reponse ne renvoie ni le contenu reçu ni l'identifiant cree :
        // l'expediteur n'en a pas l'usage, et un identifiant sequentiel dirait
        // a qui le demande combien de messages le site reçoit.
        return response()->json(['enregistre' => true], 201);
    }

    /**
     * Previent l'agence qu'un message est arrive.
     *
     * Silencieux en cas d'echec : un serveur d'envoi mal configure ne doit pas
     * faire perdre le message au visiteur, qui l'a bel et bien envoye. Il reste
     * consultable dans le backoffice, qui est la source de verite.
     */
    protected function notifierLAgence(MessageDeContact $message): void
    {
        $destinataire = Parametre::lire('destinataire_formulaire');

        if (! $destinataire) {
            return;
        }

        try {
            Mail::to($destinataire)->send(new NouveauMessageDeContact($message));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
