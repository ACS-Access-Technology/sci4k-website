<?php

namespace App\Http\Controllers;

use App\Mail\NouveauCommentaire;
use App\Models\Article;
use App\Models\Commentaire;
use App\Models\Parametre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/**
 * Depot d'un commentaire sous un article.
 *
 * Deuxieme point d'ecriture ouvert au public, apres le formulaire de contact,
 * et il porte les memes protections : limitation de debit sur la route, champ
 * piege, longueurs bornees.
 *
 * Il en ajoute une, propre aux commentaires : le message parait TOUT DE SUITE,
 * mais un filtre le met de cote quand il ressemble a du courrier indesirable.
 * Sans cela, « publication immediate » voudrait dire « publicite immediate » —
 * un site public sans garde en recoit des les premiers jours.
 */
class CommentaireController extends Controller
{
    public function __invoke(Request $requete, Article $article): RedirectResponse
    {
        // Un brouillon n'existe pas pour le public : 404, comme sa page.
        abort_if($article->statut !== 'publie', 404);

        // Le refus est pose ICI et pas seulement dans le gabarit : le
        // formulaire absent n'empeche personne de poster a la main.
        abort_unless($article->commentaires_ouverts, 403, __('Les commentaires sont fermés sur cet article.'));

        $valide = $requete->validate([
            'auteur' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'message' => ['required', 'string', 'min:2', 'max:3000'],
            // La reponse doit viser un commentaire DE CET ARTICLE : sans cette
            // condition, un identifiant forge accrocherait la reponse sous un
            // autre article.
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('commentaires', 'id')
                    ->where('article_id', $article->id)
                    ->whereNull('parent_id'),
            ],
            // Le champ piege. Il doit rester vide.
            'site_web' => ['prohibited'],
        ], [
            'site_web.prohibited' => __('Envoi refusé.'),
            'parent_id.exists' => __('Ce commentaire n’existe plus.'),
        ]);

        // AUCUN COMMENTAIRE NE PARAIT SANS RELECTURE.
        //
        // La regle d'origine publiait d'emblee ce que le filtre automatique ne
        // retenait pas. Constate sur le site en ligne : un commentaire est
        // arrive d'une adresse jetable, corps en faux latin, ni lien ni motif
        // suspect — et s'est affiche sous un article sans que personne ne l'ait
        // vu. Le filtre n'etait pas en cause : un texte nu ne lui donne aucune
        // prise, et aucun filtre n'en aura jamais sur un texte quelconque.
        //
        // Le motif reste calcule : il ne decide plus de la mise en ligne, mais
        // il TRIE la file du moderateur, qui voit d'un coup d'oeil ce qui
        // sentait deja le courrier indesirable.
        $motif = Commentaire::motifDeMiseEnAttente($valide['message'], $valide['auteur']);

        $commentaire = Commentaire::create($valide + [
            'article_id' => $article->id,
            'statut' => Commentaire::EN_ATTENTE,
            'motif_de_mise_en_attente' => $motif,
        ]);

        $this->notifierLAgence($commentaire);

        // On revient a l'article, sur l'ancre des commentaires. Le message dit
        // ce qui s'est passe : un commentaire mis de cote qui ne s'affiche pas
        // sans explication passe pour une panne.
        return redirect()
            ->to(route('actualites.detail', $article).'#commentaires')
            // Le meme message pour tous : dire « en ligne » a l'un et « sera lu »
            // a l'autre revient a annoncer au courrier indesirable qu'il a ete
            // repere, et le prochain essai contournera le filtre.
            ->with('commentaire', __('Merci. Votre commentaire sera lu par notre équipe avant d’être publié.'));
    }

    /**
     * Previent l'agence qu'un commentaire est arrive.
     *
     * Silencieux en cas d'echec, comme pour les messages de contact : un
     * serveur d'envoi mal configure ne doit pas faire perdre au visiteur un
     * commentaire qu'il a bel et bien depose. Il reste dans le backoffice, qui
     * est la source de verite.
     */
    protected function notifierLAgence(Commentaire $commentaire): void
    {
        $destinataire = Parametre::lire('destinataire_formulaire');

        if (! $destinataire) {
            return;
        }

        try {
            Mail::to($destinataire)->send(new NouveauCommentaire($commentaire));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
