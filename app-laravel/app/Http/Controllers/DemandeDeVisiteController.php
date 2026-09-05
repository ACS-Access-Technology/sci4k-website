<?php

namespace App\Http\Controllers;

use App\Mail\NouvelleDemandeDeVisite;
use App\Models\Bien;
use App\Models\DemandeDeVisite;
use App\Models\Parametre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Reception des demandes de visite depuis la fiche d'un bien.
 *
 * Troisieme point d'ecriture ouvert au public, et il porte les memes
 * protections que les deux autres : limitation de debit sur la route, champ
 * piege, longueurs bornees, et une reponse qui ne renvoie rien de ce qu'elle a
 * recu.
 *
 * Sans cette entree, l'ecran des demandes de visite aurait ete vide par
 * construction — exactement le defaut releve par le client sur les messages.
 */
class DemandeDeVisiteController extends Controller
{
    public function __invoke(Request $requete): JsonResponse
    {
        $valide = $requete->validate([
            'nom' => ['required', 'string', 'max:120'],
            'telephone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:160'],
            'message' => ['nullable', 'string', 'max:2000'],
            'bien' => ['nullable', 'string', 'max:190'],
            'creneau_souhaite' => ['nullable', 'date', 'after_or_equal:today'],
            'site_web' => ['prohibited'],
        ], [
            'site_web.prohibited' => __('Envoi refusé.'),
            'creneau_souhaite.after_or_equal' => __('Le créneau demandé est déjà passé.'),
        ]);

        // Le bien est designe par son identifiant d'URL, jamais par son
        // numero : celui-ci viendrait du navigateur et pourrait pointer
        // n'importe quelle ligne. Introuvable, la demande est conservee tout
        // de meme — mieux vaut un rendez-vous sans bien qu'un prospect perdu.
        $bien = $requete->filled('bien')
            ? Bien::publies()->where('slug', $requete->string('bien'))->first()
            : null;

        $demande = DemandeDeVisite::create([
            'nom' => $valide['nom'],
            'telephone' => $valide['telephone'],
            'email' => $valide['email'] ?? null,
            'message' => $valide['message'] ?? null,
            'bien_id' => $bien?->id,
            // Recopie : la ligne doit rester lisible quand le bien sera vendu
            // puis retire du catalogue.
            'bien_intitule' => $bien?->titre_fr ?? ($valide['bien'] ?? null),
            'creneau_souhaite' => $valide['creneau_souhaite'] ?? null,
        ]);

        $this->notifierLAgence($demande);

        return response()->json(['enregistre' => true], 201);
    }

    /**
     * Previent l'agence qu'un rendez-vous est demande.
     *
     * Les messages de contact et les commentaires declenchaient chacun leur
     * alerte ; les demandes de visite, non. Elles s'empilaient dans leur ecran
     * sans que personne ne soit prevenu — alors que c'est le seul formulaire du
     * site ou le visiteur propose une DATE. Une demande vue trois jours plus
     * tard est une demande perdue.
     *
     * Silencieux en cas d'echec, meme raison qu'ailleurs : un serveur d'envoi
     * mal configure ne doit pas faire perdre la demande au visiteur, qui l'a
     * bel et bien envoyee. Elle reste dans le backoffice, qui est la source de
     * verite.
     */
    protected function notifierLAgence(DemandeDeVisite $demande): void
    {
        $destinataire = Parametre::lire('destinataire_formulaire');

        if (! $destinataire) {
            return;
        }

        try {
            Mail::to($destinataire)->send(new NouvelleDemandeDeVisite($demande));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
