<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Reprend les six services et les douze questions du site statique.
 *
 * Rejouable : le slug du service et le couple (service, ordre) de la question
 * servent de cle. Relancer corrige sans dupliquer.
 */
class ServiceFaqSeeder extends Seeder
{
    public function run(): void
    {
        $services = json_decode(file_get_contents(database_path('data/services.json')), true);
        $questions = json_decode(file_get_contents(database_path('data/questions-faq.json')), true);

        if (! $services || ! $questions) {
            throw new \RuntimeException('Donnees d import introuvables ou illisibles.');
        }

        foreach ($services as $s) {
            // Le slug du service correspond a celui de la categorie : c'est ce
            // qui avait motive la table categories au lot 1.
            $categorie = Categorie::where('slug', $s['slug'])->first();

            if (! $categorie) {
                throw new \RuntimeException("Categorie absente pour le service {$s['slug']}. Lancer CategorieSeeder d'abord.");
            }

            Service::updateOrCreate(
                ['slug' => $s['slug']],
                array_merge($s, ['categorie_id' => $categorie->id, 'visible' => true])
            );
        }

        foreach ($questions as $q) {
            $service = Service::where('slug', $q['service_slug'])->firstOrFail();

            QuestionFaq::updateOrCreate(
                ['service_id' => $service->id, 'ordre' => $q['ordre']],
                [
                    'question_fr' => $q['question_fr'], 'question_en' => $q['question_en'],
                    'reponse_fr' => $q['reponse_fr'], 'reponse_en' => $q['reponse_en'],
                    'visible' => true,
                ]
            );
        }

        $this->command?->info(sprintf(
            '%d services et %d questions en base.',
            Service::count(),
            QuestionFaq::count()
        ));
    }
}
