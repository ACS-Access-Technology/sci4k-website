<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\QuestionFaq;
use App\Models\RubriqueFaq;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Reprend les six services et les douze questions du site statique.
 *
 * Rejouable, et surtout SANS ECRASER LE TRAVAIL EDITORIAL. La version
 * precedente reecrivait `ordre`, `visible` et `image_source` a chaque passage :
 * un `db:seed` de routine remettait l'ordre du glisser-deposer a celui du site
 * d'origine, reaffichait les services masques, et remplaçait une image
 * televersee par le chemin statique en laissant le fichier orphelin. Ces trois
 * champs ne sont donc poses qu'a la CREATION ; ensuite ils appartiennent a
 * l'editeur.
 *
 * La cle d'idempotence d'une question est (rubrique, question_fr) et non plus
 * (service, ordre) : le rang change des qu'on reordonne l'ecran, si bien que
 * rejouer le seeder apres un glisser-deposer aurait cree des doublons.
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

        // Champs que l'administration pilote : jamais reecrits par le seeder.
        $editoriaux = ['ordre', 'visible', 'image_source'];

        foreach ($services as $s) {
            // Le slug du service correspond a celui de la categorie : c'est ce
            // qui avait motive la table categories au lot 1.
            $categorie = Categorie::where('slug', $s['slug'])->first();

            if (! $categorie) {
                throw new \RuntimeException("Categorie absente pour le service {$s['slug']}. Lancer CategorieSeeder d'abord.");
            }

            $service = Service::firstOrNew(['slug' => $s['slug']]);

            $service->fill(array_diff_key($s, array_flip($editoriaux)));
            $service->categorie_id = $categorie->id;

            if (! $service->exists) {
                $service->fill(array_intersect_key($s, array_flip($editoriaux)));
                $service->visible = true;
            }

            $service->save();

            // Une rubrique de FAQ par service repris, pour que la page publique
            // garde les six groupes du site d'origine. Les rubriques ajoutees
            // ensuite par l'administration ne sont pas touchees.
            $rubrique = RubriqueFaq::firstOrNew(['slug' => $s['slug']]);
            $rubrique->fill(['nom_fr' => $s['nom_fr'], 'nom_en' => $s['nom_en']]);

            if (! $rubrique->exists) {
                $rubrique->ordre = $s['ordre'] ?? RubriqueFaq::rangSuivant();
                $rubrique->visible = true;
            }

            $rubrique->save();
        }

        foreach ($questions as $q) {
            $rubrique = RubriqueFaq::where('slug', $q['rubrique_slug'])->firstOrFail();

            $question = QuestionFaq::firstOrNew([
                'rubrique_id' => $rubrique->id,
                'question_fr' => $q['question_fr'],
            ]);

            $question->fill([
                'question_en' => $q['question_en'],
                'reponse_fr' => $q['reponse_fr'],
                'reponse_en' => $q['reponse_en'],
            ]);

            if (! $question->exists) {
                $question->ordre = $q['ordre'];
                $question->visible = true;
            }

            $question->save();
        }

        $this->command?->info(sprintf(
            '%d services, %d rubriques et %d questions en base.',
            Service::count(),
            RubriqueFaq::count(),
            QuestionFaq::count()
        ));
    }
}
