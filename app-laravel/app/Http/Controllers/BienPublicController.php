<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Referentiel;
use Illuminate\Contracts\View\View;

/**
 * La fiche d'un bien, cote visiteur.
 *
 * Le site ouvrait une fenetre par-dessus le catalogue : la fiche n'avait donc
 * pas d'adresse. On ne pouvait ni l'envoyer a quelqu'un, ni la mettre en
 * favori, ni la voir apparaitre dans un moteur de recherche — pour le coeur de
 * metier d'une agence immobiliere, c'est cher paye. La maquette du backoffice
 * annonce d'ailleurs un « identifiant d'URL /biens/… », qui n'existait nulle
 * part.
 */
class BienPublicController extends Controller
{
    public function __invoke(string $slug): View
    {
        $bien = Bien::publies()->with('photos')->where('slug', $slug)->firstOrFail();

        // Compteur de consultations. Ecriture directe, sans passer par le
        // modele : elle ne doit ni toucher `updated_at` ni inscrire une ligne
        // au journal des activites — une visite n'est pas une modification
        // editoriale, et le journal se remplirait de bruit.
        Bien::whereKey($bien->id)->update(['vues' => $bien->vues + 1]);

        $langue = app()->getLocale();

        $etiquette = fn (string $famille, ?string $valeur) => $valeur
            ? Referentiel::deLaFamille($famille)->where('valeur', $valeur)->first()?->libelle($langue)
            : null;

        return view('public.bien-detail', [
            'bien' => $bien,
            'langue' => $langue,
            'typeLisible' => $etiquette('types_de_bien', $bien->type),
            'zoneLisible' => $etiquette('zones', $bien->zone),
            'statutJuridiqueLisible' => $etiquette('statuts_juridiques', $bien->statut_juridique),
            // Trois biens de la meme zone, le bien courant exclu.
            'similaires' => Bien::publies()
                ->where('zone', $bien->zone)
                ->whereKeyNot($bien->id)
                ->with('photos')
                ->ordonnes()
                ->limit(3)
                ->get(),
            'noeudPage' => [
                '@type' => 'RealEstateListing',
                '@id' => url()->current().'#page',
                'url' => url()->current(),
                'name' => $bien->titre($langue),
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }
}
