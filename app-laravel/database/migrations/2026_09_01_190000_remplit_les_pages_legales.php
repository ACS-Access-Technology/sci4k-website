<?php

use App\Models\PageStatique;
use Illuminate\Database\Migrations\Migration;

/**
 * Verse le contenu des deux pages legales dans la base.
 *
 * Elles etaient servies depuis public/ et leur texte n'existait nulle part
 * ailleurs : le backoffice affichait deux champs vides pour des pages qui,
 * elles, avaient bien un contenu. L'editeur ne pouvait donc rien corriger.
 *
 * Les deux ne sont PAS traitees de la meme facon :
 *
 * - La politique de confidentialite est PUBLIEE. Ses corrections sont
 *   factuelles et ne laissent aucun trou : la page en ligne affirmait des
 *   choses devenues fausses depuis que le site est servi par Laravel.
 *
 * - Les mentions legales restent NON PUBLIEES. Elles reclament des donnees que
 *   seul le client detient — RCCM, capital, hebergeur, directeur de
 *   publication — et publier une page criblee de « [a fournir] » serait pire
 *   que l'etat actuel. Tant qu'elles ne sont pas publiees, la route retombe
 *   sur la page d'origine : le visiteur voit ce qu'il voit aujourd'hui, et
 *   l'editeur travaille son brouillon dans le backoffice.
 *
 * Ni l'une ni l'autre n'ecrase un texte deja saisi : la migration ne remplit
 * que ce qui est vide.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->poser('politique-confidentialite', true, $this->politiqueFr(), $this->politiqueEn());
        $this->poser('mentions-legales', false, $this->mentionsFr(), $this->mentionsEn());
    }

    public function down(): void
    {
        PageStatique::whereIn('slug', ['politique-confidentialite', 'mentions-legales'])
            ->update(['contenu_fr' => '', 'contenu_en' => '', 'publie' => true]);
    }

    /**
     * Ecrit le contenu, sauf si quelqu'un en a deja saisi un.
     *
     * La ligne peut exister depuis la migration qui a cree la table, avec un
     * contenu vide ; elle peut aussi porter un texte ecrit depuis le
     * backoffice. Dans ce second cas on ne touche a rien.
     */
    private function poser(string $slug, bool $publie, string $fr, string $en): void
    {
        $page = PageStatique::where('slug', $slug)->first();

        if ($page && trim((string) $page->contenu_fr) !== '') {
            return;
        }

        PageStatique::updateOrCreate(
            ['slug' => $slug],
            [
                'titre_fr' => $slug === 'mentions-legales' ? 'Mentions légales' : 'Politique de confidentialité',
                'titre_en' => $slug === 'mentions-legales' ? 'Legal notices' : 'Privacy policy',
                'contenu_fr' => $fr,
                'contenu_en' => $en,
                'publie' => $publie,
            ],
        );
    }

    private function politiqueFr(): string
    {
        return <<<'HTML'
<div class="legal-block">
  <h2>1. Qui sommes-nous</h2>
  <p>SCI4K, Société Civile Immobilière basée à Cocody, Cité des Arts, Abidjan, Côte d'Ivoire, est responsable du traitement des données personnelles collectées via ce site.</p>
</div>

<div class="legal-block">
  <h2>2. Données que nous collectons</h2>
  <p>Lorsque vous utilisez nos formulaires (contact, demande de visite, question de la FAQ, lettre d'information), nous collectons les informations que vous nous transmettez volontairement : nom complet, adresse e-mail, numéro de téléphone et le contenu de votre message. Elles servent uniquement à identifier votre demande et à y répondre.</p>
  <p>Nous mesurons également la fréquentation du site. Cette mesure enregistre la page consultée, la date et l'heure, le type de navigateur, et un identifiant de session transformé en empreinte non réversible. <strong>Aucune adresse IP n'est conservée</strong>, et cette mesure ne permet pas de vous identifier.</p>
  <p>Vos préférences d'affichage — thème clair ou sombre, langue française ou anglaise — sont mémorisées dans le stockage local de votre navigateur. Elles restent sur votre appareil et ne nous sont jamais transmises.</p>
</div>

<div class="legal-block">
  <h2>3. Cookies et stockage local</h2>
  <p>Ce site dépose un <strong>cookie de session</strong>, nécessaire à son fonctionnement : il maintient votre choix de langue d'une page à l'autre et permet la mesure de fréquentation décrite ci-dessus. Il expire à la fermeture de votre navigateur et ne sert à aucun profilage.</p>
  <p>Le site utilise par ailleurs le <strong>stockage local</strong> de votre navigateur pour mémoriser votre thème et votre langue d'une visite à l'autre. Ces informations ne quittent pas votre appareil.</p>
  <p>Nous n'utilisons aucun cookie publicitaire et ne revendons aucune donnée. Si des services tiers de mesure d'audience ou de messagerie instantanée venaient à être activés sur ce site, ils déposeraient leurs propres cookies : la présente politique serait alors mise à jour pour les décrire, et votre consentement recueilli au préalable.</p>
</div>

<div class="legal-block">
  <h2>4. Durée de conservation</h2>
  <p>Les données transmises via nos formulaires sont conservées le temps nécessaire au traitement de votre demande, puis archivées ou supprimées conformément à nos obligations légales et commerciales.</p>
  <p>Les données de fréquentation, qui ne permettent pas de vous identifier, sont conservées à des fins statistiques.</p>
</div>

<div class="legal-block">
  <h2>5. Partage des données</h2>
  <p>SCI4K ne vend ni ne loue vos données personnelles à des tiers. Vos informations peuvent être partagées avec nos partenaires notariés ou institutionnels uniquement dans le cadre strict de la réalisation d'une transaction que vous avez initiée avec nous.</p>
  <p>Lorsque vous choisissez d'envoyer votre demande via WhatsApp depuis notre formulaire de contact, le contenu de votre message transite par ce service, soumis à ses propres conditions d'utilisation.</p>
</div>

<div class="legal-block">
  <h2>6. Vos droits</h2>
  <p>Conformément à la loi n° 2013-450 du 19 juin 2013 relative à la protection des données à caractère personnel, vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles. Pour exercer ces droits, contactez-nous à l'adresse ci-dessous.</p>
</div>

<div class="legal-block">
  <h2>7. Contact</h2>
  <p>Pour toute question relative à cette politique de confidentialité ou à vos données personnelles, contactez-nous à <a href="mailto:contact@sci4k.com">contact@sci4k.com</a> ou au +225 07 06 16 50 29.</p>
</div>
HTML;
    }

    private function politiqueEn(): string
    {
        return <<<'HTML'
<div class="legal-block">
  <h2>1. Who we are</h2>
  <p>SCI4K, a Société Civile Immobilière based in Cocody, Cité des Arts, Abidjan, Côte d'Ivoire, is the controller of the personal data collected through this site.</p>
</div>

<div class="legal-block">
  <h2>2. Data we collect</h2>
  <p>When you use our forms (contact, viewing request, FAQ question, newsletter), we collect the information you send us willingly: full name, email address, phone number and the content of your message. It is used only to identify your request and answer it.</p>
  <p>We also measure site traffic. This records the page viewed, the date and time, the browser type, and a session identifier turned into a non-reversible fingerprint. <strong>No IP address is kept</strong>, and this measurement cannot identify you.</p>
  <p>Your display preferences — light or dark theme, French or English — are stored in your browser's local storage. They stay on your device and are never sent to us.</p>
</div>

<div class="legal-block">
  <h2>3. Cookies and local storage</h2>
  <p>This site sets a <strong>session cookie</strong>, required for it to work: it keeps your language choice from one page to the next and enables the traffic measurement described above. It expires when you close your browser and is never used for profiling.</p>
  <p>The site also uses your browser's <strong>local storage</strong> to remember your theme and language between visits. That information never leaves your device.</p>
  <p>We use no advertising cookies and sell no data. Should third-party analytics or live-chat services be enabled on this site, they would set their own cookies: this policy would then be updated to describe them, and your consent obtained beforehand.</p>
</div>

<div class="legal-block">
  <h2>4. Retention</h2>
  <p>Data sent through our forms is kept for as long as needed to handle your request, then archived or deleted in line with our legal and commercial obligations.</p>
  <p>Traffic data, which cannot identify you, is kept for statistical purposes.</p>
</div>

<div class="legal-block">
  <h2>5. Sharing</h2>
  <p>SCI4K neither sells nor rents your personal data. Your information may be shared with our notarial or institutional partners strictly within a transaction you have initiated with us.</p>
  <p>When you choose to send your request via WhatsApp from our contact form, the content of your message passes through that service, subject to its own terms of use.</p>
</div>

<div class="legal-block">
  <h2>6. Your rights</h2>
  <p>Under Ivorian law No. 2013-450 of 19 June 2013 on the protection of personal data, you have a right of access, correction and deletion of your personal data. To exercise these rights, contact us at the address below.</p>
</div>

<div class="legal-block">
  <h2>7. Contact</h2>
  <p>For any question about this privacy policy or your personal data, contact us at <a href="mailto:contact@sci4k.com">contact@sci4k.com</a> or on +225 07 06 16 50 29.</p>
</div>
HTML;
    }

    private function mentionsFr(): string
    {
        return <<<'HTML'
<div class="legal-block">
  <h2>1. Éditeur du site</h2>
  <p>Le présent site, accessible à l'adresse www.sci4k.com, est édité par <strong>SCI4K</strong>, Société Civile Immobilière de droit ivoirien.<br>
  Siège social : Cocody, Cité des Arts, Résidence Paon, 3ème étage — Abidjan, Côte d'Ivoire.<br>
  Téléphone : +225 07 06 16 50 29 — Email : contact@sci4k.com</p>
</div>

<div class="legal-block">
  <h2>2. Forme juridique et capital social</h2>
  <p>Forme juridique : Société Civile Immobilière (SCI).<br>
  Capital social : <span class="legal-placeholder">[à fournir — montant en FCFA, tel qu'il figure aux statuts]</span></p>
</div>

<div class="legal-block">
  <h2>3. Immatriculation et identification fiscale</h2>
  <p>Numéro RCCM : <span class="legal-placeholder">[à fournir]</span><br>
  Numéro de Compte Contribuable : <span class="legal-placeholder">[à fournir]</span><br>
  Agrément d'exercice de l'activité immobilière : <span class="legal-placeholder">[à fournir, ou supprimer cette ligne si aucun agrément n'est requis]</span></p>
</div>

<div class="legal-block">
  <h2>4. Directeur de la publication</h2>
  <p>Directeur de la publication : <span class="legal-placeholder">[à fournir — civilité, nom et prénoms, qualité exacte]</span><br>
  Pour toute question relative au contenu éditorial du site : <a href="mailto:contact@sci4k.com">contact@sci4k.com</a></p>
</div>

<div class="legal-block">
  <h2>5. Hébergement</h2>
  <p>Le site est hébergé par : <span class="legal-placeholder">[à fournir — raison sociale, adresse et téléphone de l'hébergeur]</span><br>
  Pays d'hébergement des données : <span class="legal-placeholder">[à fournir]</span></p>
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
  Phone: +225 07 06 16 50 29 — Email: contact@sci4k.com</p>
</div>

<div class="legal-block">
  <h2>2. Legal form and share capital</h2>
  <p>Legal form: Société Civile Immobilière (SCI), a form of Ivorian civil property company.<br>
  Share capital: <span class="legal-placeholder">[to be provided — amount in FCFA, as stated in the articles]</span></p>
</div>

<div class="legal-block">
  <h2>3. Registration and tax identification</h2>
  <p>RCCM number (Registre du Commerce et du Crédit Mobilier): <span class="legal-placeholder">[to be provided]</span><br>
  Compte Contribuable (taxpayer account) number: <span class="legal-placeholder">[to be provided]</span><br>
  Real-estate licence: <span class="legal-placeholder">[to be provided, or remove this line if no licence is required]</span></p>
</div>

<div class="legal-block">
  <h2>4. Publication director</h2>
  <p>Publication director: <span class="legal-placeholder">[to be provided — title, full name, exact role]</span><br>
  For any question about the site's editorial content: <a href="mailto:contact@sci4k.com">contact@sci4k.com</a></p>
</div>

<div class="legal-block">
  <h2>5. Hosting</h2>
  <p>The site is hosted by: <span class="legal-placeholder">[to be provided — host's company name, address and phone]</span><br>
  Country where the data is hosted: <span class="legal-placeholder">[to be provided]</span></p>
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
};
