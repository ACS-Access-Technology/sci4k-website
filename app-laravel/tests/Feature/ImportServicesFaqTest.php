<?php

use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'CategorieSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'ServiceFaqSeeder', '--force' => true]);
});

it('importe les six services', function () {
    expect(Service::count())->toBe(6);
});

it('importe les douze questions, deux par service', function () {
    expect(QuestionFaq::count())->toBe(12);

    Service::all()->each(function ($s) {
        expect($s->questionsFaq()->count())->toBe(2);
    });
});

it('n importe aucun texte vide', function () {
    // accroche et atout1 sont attendus sur les six services du site (voir le
    // garde-fou de tools/extraction-services-faq.py) : les couvrir ici
    // detecterait une regression meme si l'import n'echouait pas.
    foreach (Service::all() as $s) {
        foreach (['nom_fr', 'nom_en', 'accroche_fr', 'accroche_en', 'description_fr', 'description_en',
            'atout1_fr', 'atout1_en'] as $c) {
            expect($s->$c)->not->toBe('', "{$s->slug}.{$c} est vide");
        }
    }

    foreach (QuestionFaq::all() as $q) {
        foreach (['question_fr', 'question_en', 'reponse_fr', 'reponse_en'] as $c) {
            expect($q->$c)->not->toBe('', "question {$q->id}.{$c} est vide");
        }
    }
});

it('n importe aucun texte corrompu', function () {
    // Le controle se fait en PHP, jamais en SQL : la collation du projet est
    // insensible aux accents, un LIKE '%Ã%' matcherait tous les « a ».
    $suspects = [];

    foreach (Service::all() as $s) {
        foreach (['nom_fr', 'nom_en', 'description_fr', 'description_en'] as $c) {
            if (str_contains($s->$c, 'Ã') || str_contains($s->$c, 'â€')) {
                $suspects[] = $s->slug.'.'.$c;
            }
        }
    }

    // Les douze questions (48 champs de question et de reponse) sont
    // exposees au meme risque : elles passent elles aussi par denoter().
    foreach (QuestionFaq::all() as $q) {
        foreach (['question_fr', 'question_en', 'reponse_fr', 'reponse_en'] as $c) {
            if (str_contains($q->$c, 'Ã') || str_contains($q->$c, 'â€')) {
                $suspects[] = "question {$q->id}.{$c}";
            }
        }
    }

    expect($suspects)->toBe([]);
});

it('rattache chaque service a sa categorie', function () {
    expect(Service::whereNull('categorie_id')->count())->toBe(0);

    $service = Service::where('slug', 'foncier')->first();
    expect($service->categorie->slug)->toBe('foncier');
});

it('conserve l ordre d affichage du site', function () {
    expect(Service::ordonnees()->pluck('slug')->all())
        ->toBe(['foncier', 'construction', 'gestion', 'achat', 'vente', 'administration']);
});

it('est rejouable sans creer de doublon', function () {
    Artisan::call('db:seed', ['--class' => 'ServiceFaqSeeder', '--force' => true]);

    expect(Service::count())->toBe(6);
    expect(QuestionFaq::count())->toBe(12);
});
