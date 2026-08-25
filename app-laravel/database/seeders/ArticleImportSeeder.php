<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Categorie;
use Illuminate\Database\Seeder;
use RuntimeException;

class ArticleImportSeeder extends Seeder
{
    /** Correspondance entre le libelle affiche sur le site et le slug de categorie. */
    private const CATEGORIES = [
        'Foncier' => 'foncier',
        'Construction' => 'construction',
        'Gestion / Location' => 'gestion',
        'Achat' => 'achat',
        'Vente' => 'vente',
        'Administration de biens' => 'administration',
        'Marché' => 'marche',
    ];

    public function run(): void
    {
        $chemin = database_path('data/articles.json');

        if (! file_exists($chemin)) {
            throw new RuntimeException("Fichier d'import introuvable : {$chemin}");
        }

        $articles = json_decode(file_get_contents($chemin), true, 512, JSON_THROW_ON_ERROR);

        foreach ($articles as $a) {
            foreach (['slug', 'titre_fr', 'titre_en', 'resume_fr', 'resume_en', 'contenu_fr', 'contenu_en'] as $champ) {
                if (empty($a[$champ])) {
                    throw new RuntimeException("Champ « {$champ} » vide pour l'article « {$a['slug']} »");
                }
            }

            $slugCategorie = self::CATEGORIES[$a['categorie']] ?? null;
            if (! $slugCategorie) {
                throw new RuntimeException("Categorie inconnue : « {$a['categorie']} »");
            }

            $categorie = Categorie::where('slug', $slugCategorie)->firstOrFail();

            Article::updateOrCreate(
                ['slug' => $a['slug']],
                [
                    'categorie_id' => $categorie->id,
                    'date_publication' => $a['date'],
                    'statut' => 'publie',
                    'titre_fr' => $a['titre_fr'],
                    'titre_en' => $a['titre_en'],
                    'resume_fr' => $a['resume_fr'],
                    'resume_en' => $a['resume_en'],
                    'contenu_fr' => $a['contenu_fr'],
                    'contenu_en' => $a['contenu_en'],
                    'image_source' => $a['image'] ?? null,
                ]
            );
        }

        $total = Article::count();
        if ($total !== 12) {
            throw new RuntimeException("{$total} articles en base, 12 attendus");
        }
    }
}
