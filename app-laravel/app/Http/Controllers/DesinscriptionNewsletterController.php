<?php

namespace App\Http\Controllers;

use App\Livewire\Admin\AbonneNewsletterListe;
use App\Models\AbonneNewsletter;
use App\Models\ReglageDeSection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Le retrait de la lettre d'information, par l'interesse lui-meme.
 *
 * On pouvait s'inscrire, et l'ecran du backoffice pouvait desinscrire
 * quelqu'un — mais l'abonne n'avait aucun moyen de partir sans le demander a
 * l'agence. C'est le sens meme du droit de retrait : il ne doit dependre de
 * personne.
 *
 * EN DEUX TEMPS, et c'est la seule subtilite. Le lien ouvre une page qui
 * demande confirmation ; c'est le bouton qui retire. Un retrait declenche par
 * le simple chargement de l'adresse partirait au premier antivirus de
 * messagerie ou au premier apercu de lien : ces outils VISITENT les adresses
 * contenues dans un message pour les inspecter, et l'abonne se retrouverait
 * desinscrit sans avoir rien clique.
 *
 * Un jeton inconnu ne dit pas qu'il est inconnu : la page de confirmation
 * s'affiche pareil, et le bouton rend le meme message. Distinguer les deux cas
 * dirait a qui essaie des jetons lesquels correspondent a un abonne.
 */
class DesinscriptionNewsletterController extends Controller
{
    /** La page qui demande confirmation. */
    public function formulaire(string $jeton): View
    {
        return view('public.newsletter-desinscription', [
            'jeton' => $jeton,
            'abonne' => AbonneNewsletter::where('jeton', $jeton)->first(),
            // Les textes de la page, editables depuis l'ecran « Abonnés
            // newsletter » : la page est servie par le site, hors des sept
            // pages editables, et ses mots se changent la ou l'on gere ce dont
            // elle parle.
            'textes' => ReglageDeSection::where('slug', AbonneNewsletterListe::SECTION)->first(),
        ]);
    }

    /** Le retrait lui-meme. */
    public function retirer(string $jeton): RedirectResponse
    {
        $abonne = AbonneNewsletter::where('jeton', $jeton)->first();

        // Deja parti : on ne repousse pas la date. Le premier retrait est celui
        // qui compte, et c'est lui qu'on doit pouvoir montrer.
        if ($abonne && ! $abonne->estDesinscrit()) {
            $abonne->desinscrit_a = now();
            $abonne->save();
        }

        return redirect()
            ->route('newsletter.desinscription', $jeton)
            ->with('desinscrit', true);
    }
}
