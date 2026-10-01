<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Le contenu de la maquette, pour developper et juger du rendu.
 *
 * JAMAIS LANCE PAR `db:seed`. Il se demande expressement :
 *
 *     php artisan db:seed --class=DemonstrationSeeder
 *
 * Tout ce qui suit vient de la maquette et affirme quelque chose que l'agence
 * n'a pas valide : des temoignages signes, une equipe nommee, des logos de
 * partenaires sans accord ecrit, des chiffres (« 120 biens commercialises »),
 * douze articles dates, six biens sans prix ni reference, et des realisations
 * INVENTEES prefixees DEMO-. Sur le vrai domaine, c'est de la publicite
 * mensongere ; en developpement, c'est ce qui permet de voir une page pleine.
 *
 * EN PRODUCTION, IL DEMANDE CONFIRMATION, et la reponse par defaut est non.
 * Sans terminal pour repondre — un script, un --no-interaction — il ne seme
 * rien : du contenu fictif ne doit jamais arriver en ligne par inadvertance.
 * L'environnement d'essai sur Railway tourne en « production » ; y semer la
 * demonstration reste possible, mais seulement en le confirmant a la main.
 */
class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        // Le refus sans terminal est ecrit ici, et non laisse a la reponse par
        // defaut de la question : on ne depend pas de la facon dont la console
        // traite une question posee a personne.
        $confirme = fn (): bool => $this->command !== null
            && ! $this->command->option('no-interaction')
            && $this->command->confirm(
                'Environnement de PRODUCTION. Semer du contenu fictif (témoignages, équipe, biens, articles de la maquette) ?',
                false,
            );

        if (app()->isProduction() && ! $confirme()) {
            $this->command?->warn('Contenu de démonstration non semé.');

            return;
        }

        // L'ossature d'abord : les biens exigent les referentiels, les
        // articles les categories, les realisations les services. Sans effet
        // sur une base deja remplie.
        $this->call(StructureSeeder::class);

        foreach (['temoignages', 'equipe', 'partenaires', 'chiffres-cles'] as $famille) {
            BlocsDeContenuSeeder::semerLaFamille($famille);
        }

        $this->call([
            ArticleImportSeeder::class,
            BiensSeeder::class,
            RealisationsDeDemonstrationSeeder::class,
        ]);
    }
}
