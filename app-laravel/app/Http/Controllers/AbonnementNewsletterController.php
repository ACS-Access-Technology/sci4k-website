<?php

namespace App\Http\Controllers;

use App\Models\AbonneNewsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Inscription a la lettre d'information depuis le pied de page.
 *
 * Deuxieme point d'ecriture ouvert au public, et il porte les memes
 * protections que la reception des messages : limitation de debit, champ
 * piege, longueur bornee.
 *
 * La reponse est TOUJOURS la meme, que l'adresse soit nouvelle ou deja
 * connue. Repondre « vous etes deja inscrit » permettrait a un inconnu de
 * savoir, adresse par adresse, qui figure dans la liste de l'agence.
 */
class AbonnementNewsletterController extends Controller
{
    public function __invoke(Request $requete): JsonResponse
    {
        $valide = $requete->validate([
            'email' => ['required', 'email', 'max:160'],
            'site_web' => ['prohibited'],
        ], [
            'site_web.prohibited' => __('Envoi refusé.'),
        ]);

        AbonneNewsletter::inscrire($valide['email']);

        return response()->json(['inscrit' => true], 201);
    }
}
