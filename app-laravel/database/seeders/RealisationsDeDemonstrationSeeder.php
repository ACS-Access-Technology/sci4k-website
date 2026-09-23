<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Des realisations INVENTEES, pour juger du rendu avant d'avoir les vraies.
 *
 * A LIRE AVANT DE DEPLOYER AILLEURS QUE SUR L'ENVIRONNEMENT DE TEST.
 * Rien ici ne correspond a un immeuble existant. Ces fiches n'ont ete ecrites
 * que pour repondre a une question de mise en page : « Construction » et
 * « Administration de biens » n'avaient aucun bien a montrer, et une rubrique
 * vide ne dit pas si la rubrique pleine sera lisible.
 *
 * POURQUOI DES REALISATIONS ET NON DES BIENS ORDINAIRES : ces deux activites
 * ne vendent ni ne louent. Un immeuble bati ou administre par l'agence est une
 * reference, pas une offre — d'ou `est_une_realisation`, qui le garde hors du
 * catalogue, sans prix et sans bouton de visite.
 *
 * COMMENT LES RETIRER, le jour ou l'agence fournit ses vraies references :
 *
 *     php artisan tinker --execute="App\Models\Bien::where('reference','like','DEMO-%')->delete();"
 *
 * La reference prefixee est la POUR CELA. Elle n'a aucun role d'affichage :
 * c'est une etiquette de contenu jetable, et le seul moyen sur de distinguer
 * plus tard l'invente du reel une fois les deux melanges dans la meme table.
 */
class RealisationsDeDemonstrationSeeder extends Seeder
{
    /**
     * @var list<array<string, string>>
     */
    private const REALISATIONS = [
        [
            'service' => 'construction',
            'reference' => 'DEMO-C01',
            'slug' => 'demo-residence-les-roniers',
            'titre_fr' => 'Résidence Les Rôniers',
            'titre_en' => 'Les Rôniers Residence',
            'sous_titre_fr' => 'Immeuble R+4 · 16 logements',
            'sous_titre_en' => 'Four-storey building · 16 units',
            'zone' => 'cocody',
            'quartier' => 'Cocody Angré 7ème Tranche',
            'type' => 'immeuble',
            'description_fr' => "Conception et gros œuvre menés par SCI4K, de l'étude de sol à la livraison. Seize logements répartis sur quatre niveaux, avec parking en sous-sol et groupe électrogène de secours.",
            'description_en' => 'Design and structural work carried out by SCI4K, from the soil survey to handover. Sixteen units across four floors, with underground parking and a backup generator.',
        ],
        [
            'service' => 'construction',
            'reference' => 'DEMO-C02',
            'slug' => 'demo-villa-duplex-bingerville',
            'titre_fr' => 'Villa duplex de Bingerville',
            'titre_en' => 'Bingerville Duplex Villa',
            'sous_titre_fr' => 'Maison individuelle · F6',
            'sous_titre_en' => 'Detached house · 6 rooms',
            'zone' => 'bingerville',
            'quartier' => 'Bingerville, cité BACID',
            'type' => 'villa',
            'description_fr' => 'Construction clé en main sur un terrain acquis par le client. Dalle, charpente, second œuvre et finitions assurés en dix mois, avec suivi hebdomadaire du chantier.',
            'description_en' => 'Turnkey construction on land acquired by the client. Slab, frame, fittings and finishes delivered in ten months, with weekly site supervision.',
        ],
        [
            'service' => 'construction',
            'reference' => 'DEMO-C03',
            'slug' => 'demo-immeuble-de-bureaux-plateau',
            'titre_fr' => 'Immeuble de bureaux du Plateau',
            'titre_en' => 'Plateau Office Building',
            'sous_titre_fr' => 'Tertiaire · 6 plateaux',
            'sous_titre_en' => 'Office use · 6 floors',
            'zone' => 'plateau',
            'quartier' => 'Le Plateau',
            'type' => 'immeuble',
            'description_fr' => "Réhabilitation lourde d'un bâtiment des années 1980 : reprise de structure, façade ventilée, mise aux normes électriques et ascenseur neuf.",
            'description_en' => 'Major refurbishment of a 1980s building: structural repairs, ventilated façade, electrical upgrade and a new lift.',
        ],
        [
            'service' => 'administration',
            'reference' => 'DEMO-A01',
            'slug' => 'demo-residence-akwaba',
            'titre_fr' => 'Résidence Akwaba',
            'titre_en' => 'Akwaba Residence',
            'sous_titre_fr' => 'Copropriété · 24 lots gérés',
            'sous_titre_en' => 'Condominium · 24 managed units',
            'zone' => 'marcory',
            'quartier' => 'Marcory Zone 4',
            'type' => 'immeuble',
            'description_fr' => 'Administration complète pour le compte du syndic : appels de charges, entretien des parties communes, gardiennage et relation avec les copropriétaires.',
            'description_en' => 'Full administration on behalf of the management board: service charges, upkeep of shared areas, security staffing and owner relations.',
        ],
        [
            'service' => 'administration',
            'reference' => 'DEMO-A02',
            'slug' => 'demo-immeuble-de-la-lagune',
            'titre_fr' => 'Immeuble de la Lagune',
            'titre_en' => 'Lagoon Building',
            'sous_titre_fr' => 'Immeuble de rapport · 9 baux',
            'sous_titre_en' => 'Rental building · 9 leases',
            'zone' => 'abatta',
            'quartier' => 'Abatta Bord de Lagune',
            'type' => 'immeuble',
            'description_fr' => 'Gestion locative déléguée depuis 2022 : encaissement des loyers, reversement mensuel au propriétaire, états des lieux et suivi des impayés.',
            'description_en' => 'Delegated rental management since 2022: rent collection, monthly transfer to the owner, condition reports and arrears follow-up.',
        ],
        [
            'service' => 'administration',
            'reference' => 'DEMO-A03',
            'slug' => 'demo-galerie-commerciale-cocody',
            'titre_fr' => 'Galerie commerciale de Cocody',
            'titre_en' => 'Cocody Shopping Arcade',
            'sous_titre_fr' => 'Commerces · 11 cellules',
            'sous_titre_en' => 'Retail · 11 units',
            'zone' => 'cocody',
            'quartier' => 'Cocody, Cité des Arts',
            'type' => 'immeuble',
            'description_fr' => "Administration d'un ensemble commercial : baux professionnels, charges refacturées, maintenance technique et sécurité des accès.",
            'description_en' => 'Administration of a retail complex: commercial leases, recharged expenses, technical maintenance and access security.',
        ],
    ];

    public function run(): void
    {
        foreach (self::REALISATIONS as $entree) {
            $service = Service::where('slug', $entree['service'])->first();

            // Le slug du service depend des donnees : une base ou ils auraient
            // ete renommes ne doit pas faire echouer le peuplement.
            if (! $service) {
                continue;
            }

            $bien = Bien::updateOrCreate(
                ['slug' => $entree['slug']],
                [
                    'reference' => $entree['reference'],
                    'titre_fr' => $entree['titre_fr'],
                    'titre_en' => $entree['titre_en'],
                    'sous_titre_fr' => $entree['sous_titre_fr'],
                    'sous_titre_en' => $entree['sous_titre_en'],
                    'description_fr' => $entree['description_fr'],
                    'description_en' => $entree['description_en'],
                    'type' => $entree['type'],
                    // Obligatoire en base, sans aucun sens ici : une realisation
                    // n'est ni a vendre ni a louer. La valeur n'est jamais
                    // affichee — la pastille d'offre est masquee sur une
                    // realisation, et sa fiche ne propose pas de visite.
                    'offre' => Bien::VENTE,
                    'zone' => $entree['zone'],
                    'quartier' => $entree['quartier'],
                    'statut' => Bien::PUBLIE,
                    'est_une_realisation' => true,
                ]
            );

            $bien->services()->syncWithoutDetaching([$service->id]);
        }
    }
}
