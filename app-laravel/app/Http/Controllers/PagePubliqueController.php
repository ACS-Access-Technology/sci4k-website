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

        // Groupees par service, dans l'ordre des services puis des questions :
        // sur le site, le titre de chaque groupe EST le nom du service.
        $groupes = QuestionFaq::visibles()
            ->with('service')
            ->get()
            ->sortBy(fn ($q) => [$q->service->ordre, $q->ordre])
            ->groupBy(fn ($q) => $q->service->id);

        return view('public.faq', [
            'groupes' => $groupes,
            'langue' => $langue,
            'noeudPage' => [
                '@type' => 'FAQPage',
                '@id' => route('faq.index').'#page',
                'url' => route('faq.index'),
                'inLanguage' => $langue,
                'mainEntity' => QuestionFaq::visibles()->get()->map(fn ($q) => [
                    '@type' => 'Question',
                    'name' => $q->question($langue),
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $q->reponse($langue)],
                ])->all(),
            ],
        ]);
    }
}
