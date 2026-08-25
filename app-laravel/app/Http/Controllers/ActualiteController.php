<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Contracts\View\View;

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
}
