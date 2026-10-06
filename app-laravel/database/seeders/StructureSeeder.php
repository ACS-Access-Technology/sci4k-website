<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\CommuneDuBandeau;
use App\Models\EntreeDeMenu;
use App\Models\EtapeProcessus;
use App\Models\Referentiel;
use App\Models\ReglageDeSection;
use App\Models\Service;
use App\Models\Valeur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Ce sans quoi le site ne tient pas debout : l'ossature, et non le contenu.
 *
 * Une base qui n'a recu que les migrations sert des pages VIDES — ni menu, ni
 * filtre, ni service, ni question, aucun titre de section. Ce seeder pose ce qui
 * les remplit : categories, referentiels des filtres, menus, services et FAQ,
 * textes et visuels des sections, valeurs, etapes du processus, communes du
 * bandeau. Tout vient des fichiers de database/data/, eux-memes releves sur le
 * site d'origine : rien n'y est invente.
 *
 * N'EN FONT PAS PARTIE les contenus qui affirment quelque chose sur des
 * personnes, des chiffres ou des offres — temoignages, equipe, partenaires,
 * chiffres cles, articles, biens. Ils viennent de la maquette et restent a
 * valider par l'agence : voir DemonstrationSeeder.
 *
 * SUR UNE BASE DEJA REMPLIE, IL NE MODIFIE RIEN. Les seeders d'import qu'il
 * appelle realignent les textes sur leur fichier a chaque passage — c'est ce
 * qui les avait fait sortir du DatabaseSeeder : un `db:seed` de routine aurait
 * defait les corrections faites depuis l'administration. D'ou deux regles :
 *
 *   - une famille n'est semee que si sa table est VIDE. Une table qui porte
 *     deja des lignes appartient a l'administration ; la completer ferait
 *     reapparaitre ce qu'un editeur a supprime ;
 *
 *   - exception, les images de fond et les encarts : les migrations en posent
 *     deja quelques-uns, et ce sont des emplacements fixes, reperes par leur
 *     slug, que l'ecran ne supprime pas. On cree ceux qui manquent, sans
 *     toucher aux autres.
 */
class StructureSeeder extends Seeder
{
    public function run(): void
    {
        $this->siVide(Categorie::class, CategorieSeeder::class);
        $this->siVide(Referentiel::class, ReferentielsSeeder::class);
        $this->siVide(EntreeDeMenu::class, MenusSeeder::class);
        // Services, rubriques et questions de la FAQ vont ensemble : les
        // rubriques naissent des services. Il exige les categories.
        $this->siVide(Service::class, ServiceFaqSeeder::class);

        foreach (['reglages-de-section' => ReglageDeSection::class, 'valeurs' => Valeur::class,
            'etapes-processus' => EtapeProcessus::class] as $famille => $modele) {
            if ($modele::query()->doesntExist()) {
                BlocsDeContenuSeeder::semerLaFamille($famille);
            }
        }

        BlocsDeContenuSeeder::semerLaFamille('images-de-fond', sansModifier: true);
        BlocsDeContenuSeeder::semerLaFamille('encarts', sansModifier: true);

        // Apres les reglages de section : il cree le sien s'il manque.
        $this->siVide(CommuneDuBandeau::class, CommunesDuBandeauSeeder::class);
    }

    /**
     * @param  class-string<Model>  $modele
     * @param  class-string<Seeder>  $seeder
     */
    private function siVide(string $modele, string $seeder): void
    {
        if ($modele::query()->doesntExist()) {
            $this->call($seeder);
        }
    }
}
