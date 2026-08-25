<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActualiteController extends Controller
{
    /** Liste publique des articles publies, du plus recent au plus ancien. */
    public function index(): View
    {
        $langue = app()->getLocale();

        return view('public.actualites.index', [
            'articles' => Article::publies()->with('categorie')->get(),
            'categories' => Categorie::orderBy('ordre')->get(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'CollectionPage',
                '@id' => route('actualites.index').'#page',
                'url' => route('actualites.index'),
                'name' => __('Actualités').' — SCI4K',
                'description' => __("Conseils et actualités immobilières à Abidjan : foncier, marché, gestion locative. Les actualités de SCI4K."),
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
                'about' => ['@id' => rtrim(url('/'), '/').'/#organisation'],
            ],
        ]);
    }

    /** Un article, a son adresse propre. */
    public function detail(Article $article): View
    {
        // Un brouillon n'existe pas pour le public : 404, et non 403, pour ne
        // pas reveler qu'un article de ce nom est en preparation.
        abort_if($article->statut !== 'publie', 404);

        $langue = app()->getLocale();
        $article->load('categorie');

        $noeud = [
            '@type' => 'NewsArticle',
            '@id' => route('actualites.detail', $article).'#article',
            'url' => route('actualites.detail', $article),
            'headline' => $article->titre($langue),
            'description' => $article->metaDescription($langue),
            'datePublished' => $article->date_publication->toDateString(),
            'inLanguage' => $langue,
            'articleSection' => $article->categorie->nom($langue),
            'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            'publisher' => ['@id' => rtrim(url('/'), '/').'/#organisation'],
            'author' => ['@id' => rtrim(url('/'), '/').'/#organisation'],
        ];

        if ($url = $article->urlCouverture()) {
            $noeud['image'] = $url;
        }

        return view('public.actualites.detail', [
            'article' => $article,
            'langue' => $langue,
            'noeudPage' => $noeud,
        ]);
    }

    /**
     * Ancienne adresse des articles : /actualite-detail.html?id=slug
     *
     * Les douze articles partageaient cette unique adresse, un parametre les
     * distinguant cote navigateur. Les liens deja partages doivent continuer de
     * fonctionner : la redirection est permanente, pour que les moteurs
     * transferent l'anciennete de l'adresse a la nouvelle.
     */
    public function ancienneAdresse(Request $requete): RedirectResponse
    {
        $slug = $requete->query('id');

        if (! is_string($slug) || $slug === '') {
            return redirect()->route('actualites.index', [], 301);
        }

        return redirect()->route('actualites.detail', $slug, 301);
    }
}
