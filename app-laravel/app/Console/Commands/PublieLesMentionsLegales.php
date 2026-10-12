<?php

namespace App\Console\Commands;

use App\Models\PageStatique;
use Illuminate\Console\Command;

/**
 * Publie les mentions legales, completees avec les informations fournies par
 * la direction le 6 octobre 2026.
 *
 * POURQUOI. Tant qu'elles sont en brouillon, la route retombe sur la page
 * d'attente : un fichier statique servi avec « noindex », sans adresse
 * canonique, sans lien entre ses deux langues, au titre francais meme a
 * l'adresse anglaise, et absent du plan du site. Publiee, la page passe par le
 * gabarit des autres pages : tout cela est regle d'un coup.
 *
 * CE QUI Y EST : capital social, RCCM, compte contribuable, directeur de la
 * publication, hebergeur (Railway Corporation, adresse relevee dans ses
 * conditions generales) et l'adresse de contact. Rien d'invente.
 *
 * CE QUI N'Y EST PAS, volontairement : la ligne « Agrement d'exercice de
 * l'activite immobiliere » du brouillon. Personne n'a dit qu'un agrement existe
 * ou qu'il n'est pas requis ; l'ecrire ou la retirer est une decision juridique
 * de la direction. Si un agrement doit figurer, l'ajouter dans le backoffice
 * (Pages editables).
 *
 * UNE COMMANDE, ET NON UNE MIGRATION : une migration publierait aussi les
 * mentions sur toute base neuve, dont celle des tests, qui attendent le
 * brouillon. Ici, l'operateur la lance une fois, apres sauvegarde et accord :
 *
 *   php artisan mentions-legales:publier
 *
 * GARDE : elle ne touche la page que si elle est encore le brouillon d'origine
 * — non publiee et toujours pleine de « a fournir ». Un texte deja retravaille
 * par l'editeur n'est jamais ecrase. Pour revenir en arriere : repasser la page
 * en brouillon dans le backoffice (Pages editables).
 */
class PublieLesMentionsLegales extends Command
{
    protected $signature = 'mentions-legales:publier';

    protected $description = 'Publie les mentions legales completees (capital, RCCM, NCC, directeur, hebergeur), si elles sont encore le brouillon initial';

    public function handle(): int
    {
        $page = PageStatique::where('slug', 'mentions-legales')->first();

        if (! $page) {
            $this->error('La page mentions-legales est introuvable.');

            return self::FAILURE;
        }

        if ($page->publie || ! str_contains($page->contenu_fr.$page->contenu_en, 'legal-placeholder')) {
            $this->warn('Rien a faire : la page est deja publiee, ou son texte a ete retravaille. Aucune modification.');

            return self::SUCCESS;
        }

        $page->update([
            'contenu_fr' => $this->mentionsFr(),
            'contenu_en' => $this->mentionsEn(),
            'publie' => true,
        ]);

        $this->info('Mentions legales completees et publiees.');

        return self::SUCCESS;
    }

    private function mentionsFr(): string
    {
        return <<<'HTML'
<div class="legal-block">
  <h2>1. Éditeur du site</h2>
  <p>Le présent site, accessible à l'adresse www.sci4k.com, est édité par <strong>SCI4K</strong>, Société Civile Immobilière de droit ivoirien.<br>
  Siège social : Cocody, Cité des Arts, Résidence Paon, 3ème étage — Abidjan, Côte d'Ivoire.<br>
  Téléphone : +225 07 06 16 50 29 — Email : info@acsgroupe.ci</p>
</div>

<div class="legal-block">
  <h2>2. Forme juridique et capital social</h2>
  <p>Forme juridique : Société Civile Immobilière (SCI).<br>
  Capital social : 10 000 000 FCFA</p>
</div>

<div class="legal-block">
  <h2>3. Immatriculation et identification fiscale</h2>
  <p>Numéro RCCM : CI-ABJ-03-2024-B40-00161<br>
  Numéro de Compte Contribuable (NCC) : 2606288Y</p>
</div>

<div class="legal-block">
  <h2>4. Directeur de la publication</h2>
  <p>Directeur de la publication : Monsieur Tiemoko Max Regis, Directeur Général de SCI4K<br>
  Pour toute question relative au contenu éditorial du site : <a href="mailto:info@acsgroupe.ci">info@acsgroupe.ci</a></p>
</div>

<div class="legal-block">
  <h2>5. Hébergement</h2>
  <p>Le site est hébergé par : Railway Corporation, 548 Market St PMB 68956, San Francisco, California 94104, États-Unis.<br>
  Pays d'hébergement des données : États-Unis.</p>
</div>

<div class="legal-block">
  <h2>6. Propriété intellectuelle</h2>
  <p>L'ensemble des éléments du site — textes, photographies, visuels, plans, logos, marques, chartes graphiques, structure et code — est protégé par la législation en vigueur en Côte d'Ivoire et par les conventions internationales applicables. Ces éléments sont la propriété exclusive de SCI4K ou font l'objet d'une autorisation d'exploitation à son bénéfice.</p>
  <p>Toute reproduction, représentation, adaptation ou diffusion, totale ou partielle, sans autorisation écrite préalable, est interdite et constitue une contrefaçon.</p>
  <p>Les photographies de biens immobiliers sont fournies à titre d'illustration et n'ont aucune valeur contractuelle.</p>
</div>

<div class="legal-block">
  <h2>7. Liens hypertextes</h2>
  <p>Le site peut renvoyer vers des sites tiers — partenaires, réseaux sociaux, services de messagerie. SCI4K n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu et à leurs pratiques en matière de données.</p>
</div>

<div class="legal-block">
  <h2>8. Données personnelles et cookies</h2>
  <p>SCI4K traite les données personnelles collectées via ce site dans le respect de la loi n° 2013-450 du 19 juin 2013. Les finalités, les durées de conservation, l'usage des cookies et les modalités d'exercice de vos droits sont détaillés dans notre <a href="/politique-confidentialite">politique de confidentialité</a>.</p>
</div>

<div class="legal-block">
  <h2>9. Limitation de responsabilité</h2>
  <p>Les informations publiées sur le site, notamment les caractéristiques et prix des biens présentés, sont fournies à titre indicatif et sont susceptibles d'évoluer. Elles ne constituent ni une offre contractuelle ni un engagement de SCI4K : seuls les actes signés entre les parties font foi.</p>
</div>

<div class="legal-block">
  <h2>10. Droit applicable</h2>
  <p>Le présent site et les présentes mentions légales sont régis par le droit ivoirien. En cas de litige, et à défaut de résolution amiable, compétence est attribuée aux tribunaux d'Abidjan.</p>
</div>
HTML;
    }

    private function mentionsEn(): string
    {
        return <<<'HTML'
<div class="legal-block">
  <h2>1. Site publisher</h2>
  <p>This site, available at www.sci4k.com, is published by <strong>SCI4K</strong>, a Société Civile Immobilière under Ivorian law.<br>
  Registered office: Cocody, Cité des Arts, Résidence Paon, 3rd floor — Abidjan, Côte d'Ivoire.<br>
  Phone: +225 07 06 16 50 29 — Email: info@acsgroupe.ci</p>
</div>

<div class="legal-block">
  <h2>2. Legal form and share capital</h2>
  <p>Legal form: Société Civile Immobilière (SCI), a form of Ivorian civil property company.<br>
  Share capital: 10 000 000 FCFA</p>
</div>

<div class="legal-block">
  <h2>3. Registration and tax identification</h2>
  <p>RCCM number (Registre du Commerce et du Crédit Mobilier): CI-ABJ-03-2024-B40-00161<br>
  Taxpayer account number (NCC): 2606288Y</p>
</div>

<div class="legal-block">
  <h2>4. Publication director</h2>
  <p>Publication director: Monsieur Tiemoko Max Regis, Managing Director of SCI4K<br>
  For any question about the site's editorial content: <a href="mailto:info@acsgroupe.ci">info@acsgroupe.ci</a></p>
</div>

<div class="legal-block">
  <h2>5. Hosting</h2>
  <p>The site is hosted by: Railway Corporation, 548 Market St PMB 68956, San Francisco, California 94104, United States.<br>
  Country where the data is hosted: United States.</p>
</div>

<div class="legal-block">
  <h2>6. Intellectual property</h2>
  <p>All elements of the site — texts, photographs, visuals, plans, logos, trademarks, graphic identity, structure and code — are protected by the legislation in force in Côte d'Ivoire and by the applicable international conventions. These elements are the exclusive property of SCI4K or are licensed to it.</p>
  <p>Any reproduction, representation, adaptation or distribution, in whole or in part, without prior written permission, is prohibited and constitutes infringement.</p>
  <p>Photographs of properties are provided for illustration only and have no contractual value.</p>
</div>

<div class="legal-block">
  <h2>7. Hyperlinks</h2>
  <p>The site may link to third-party sites — partners, social networks, messaging services. SCI4K exercises no control over these sites and accepts no responsibility for their content or their data practices.</p>
</div>

<div class="legal-block">
  <h2>8. Personal data and cookies</h2>
  <p>SCI4K processes the personal data collected through this site in compliance with Ivorian law No. 2013-450 of 19 June 2013. Purposes, retention periods, cookie usage and how to exercise your rights are detailed in our <a href="/politique-confidentialite">privacy policy</a>.</p>
</div>

<div class="legal-block">
  <h2>9. Limitation of liability</h2>
  <p>The information published on the site, in particular the features and prices of the properties shown, is indicative and may change. It constitutes neither a contractual offer nor a commitment by SCI4K: only the deeds signed between the parties are binding.</p>
</div>

<div class="legal-block">
  <h2>10. Governing law</h2>
  <p>This site and these legal notices are governed by Ivorian law. In the event of a dispute, and failing an amicable settlement, the courts of Abidjan shall have jurisdiction.</p>
</div>
HTML;
    }
}
