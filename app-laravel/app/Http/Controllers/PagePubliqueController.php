<?php

namespace App\Http\Controllers;

use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Contracts\View\View;

class PagePubliqueController extends Controller
{
    public function services(): View
    {
        $langue = app()->getLocale();

        return view('public.services', [
            'services' => Service::visibles()->ordonnees()->get(),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'CollectionPage',
                '@id' => route('services.index').'#page',
                'url' => route('services.index'),
                'name' => __('Nos services').' — SCI4K',
                'inLanguage' => $langue,
                'isPartOf' => ['@id' => rtrim(url('/'), '/').'/#site'],
            ],
        ]);
    }

    public function faq(): View
    {
        $langue = app()->getLocale();

        // Groupees par rubrique, dans l'ordre des rubriques puis des questions :
        // sur le site, le titre de chaque groupe EST le nom de la rubrique.
        //
        // La visibilite de la rubrique compte autant que celle de la question :
        // masquer une rubrique sans ce filtre laisserait sur la page un groupe
        // entier, titre compris, que l'editeur croyait avoir retire.
        $questions = QuestionFaq::visibles()
            ->whereHas('rubrique', fn ($r) => $r->where('visible', true))
            ->with('rubrique')
            ->get()
            ->sortBy(fn ($q) => [$q->rubrique->ordre, $q->ordre])
            ->values();

        return view('public.faq', [
            'groupes' => $questions->groupBy(fn ($q) => $q->rubrique->id),
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'FAQPage',
                '@id' => route('faq.index').'#page',
                'url' => route('faq.index'),
                'inLanguage' => $langue,
                // La meme collection que la page, et non une seconde requete :
                // les donnees structurees annonçaient sinon un ordre et un
                // contenu qui pouvaient differer de ce que le visiteur lit.
                'mainEntity' => $questions->map(fn ($q) => [
                    '@type' => 'Question',
                    'name' => $q->question($langue),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q->reponse($langue)],
                ])->all(),
            ],
        ]);
    }
}
