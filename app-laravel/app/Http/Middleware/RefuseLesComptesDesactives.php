<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte desactive perd sa session en cours.
 *
 * Le refus pose sur l'authentification ne regarde que le MOMENT de la
 * connexion. Un editeur deja connecte quand l'administrateur le desactive
 * continuait de naviguer, d'enregistrer et de supprimer jusqu'a l'expiration
 * de sa session — deux heures d'inactivite, remises a zero a chaque requete,
 * donc potentiellement toute la journee de travail.
 *
 * C'est le cas qui compte le plus : on desactive un compte parce qu'il se
 * passe quelque chose maintenant, pas pour empecher une connexion demain.
 */
class RefuseLesComptesDesactives
{
    public function handle(Request $requete, Closure $suivant): Response
    {
        $utilisateur = Auth::user();

        if ($utilisateur && ! $utilisateur->peutSeConnecter()) {
            Auth::logout();

            $requete->session()->invalidate();
            $requete->session()->regenerateToken();

            // EXACTEMENT le message d'un echec de connexion, mot pour mot :
            // distinguer « desactive » de « identifiants incorrects » dirait a
            // un inconnu quelles adresses existent dans la maison.
            //
            // La cle est le texte francais lui-meme, et non `auth.failed` :
            // c'est la convention du projet, et un garde-fou la verifie.
            return redirect()->route('login')
                ->withErrors(['email' => __('Ces identifiants ne correspondent à aucun compte.')]);
        }

        return $suivant($requete);
    }
}
