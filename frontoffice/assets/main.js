/* ===== SCI4K - Script unique du site ===== */

/* ---- Mouvement reduit ----
   Le CSS ne peut rien contre un defilement anime demande explicitement en
   JavaScript : scrollTo({behavior:'smooth'}) ignore scroll-behavior. On
   interroge donc la preference systeme a chaque appel, car elle peut changer
   pendant la visite. */
function sci4kDefilement() {
  return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ? 'auto'
    : 'smooth';
}

/* ---- Dictionnaire FR/EN ---- */
/* SCI4K — FR / EN dictionary. Each key maps to {fr:'...', en:'...'} */
window.SCI4K_I18N = {
  /* ===== NAV / FOOTER (shared across every page) ===== */
  "nav.home": { fr: "Accueil", en: "Home" },
  "nav.about": { fr: "Présentation", en: "About" },
  "nav.properties": { fr: "Biens Immobiliers", en: "Properties" },
  "nav.services": { fr: "Nos Services", en: "Services" },
  "nav.faq": { fr: "FAQ", en: "FAQ" },
  "nav.contact": { fr: "Contact", en: "Contact" },
  "nav.actualites": { fr: "Actualités", en: "News" },
  "nav.cta": { fr: "Nous contacter", en: "Contact us" },

  "footer.about": { fr: "Société Civile Immobilière basée à Abidjan — Cocody, Cité des Arts. Achat, vente, location, construction et gestion de patrimoine immobilier.", en: "Real estate company based in Abidjan — Cocody, Cité des Arts. Buying, selling, renting, construction and property management." },
  "footer.newsletterPh": { fr: "Votre adresse email", en: "Your email address" },
  "footer.newsletterBtn": { fr: "S'inscrire à la newsletter", en: "Subscribe to the newsletter" },
  "footer.navTitle": { fr: "Navigation", en: "Navigation" },
  "footer.servicesTitle": { fr: "Nos Services", en: "Our Services" },
  "footer.contactTitle": { fr: "Nous contacter", en: "Contact us" },
  "footer.address": { fr: "Cocody, Cité des Arts<br>Résidence Paon, 3ème étage<br>Abidjan, Côte d'Ivoire", en: "Cocody, Cité des Arts<br>Résidence Paon, 3rd floor<br>Abidjan, Côte d'Ivoire" },
  "footer.telLabel": { fr: "Tél:", en: "Phone:" },
  "footer.emailLabel": { fr: "Email:", en: "Email:" },
  "footer.copyright": { fr: "© 2026 SCI4K — Tous droits réservés.", en: "© 2026 SCI4K — All rights reserved." },
  "footer.legal": { fr: "Mentions légales", en: "Legal notice" },
  "footer.privacy": { fr: "Politique de confidentialité", en: "Privacy policy" },
  "footer.tagline": { fr: "Société Civile Immobilière — Abidjan, Côte d'Ivoire", en: "Real Estate Company — Abidjan, Côte d'Ivoire" },

  /* ===== SERVICES (shared naming, used on Home + Services page) ===== */
  "svc.foncier.name": { fr: "Foncier", en: "Land & Title" },
  "svc.foncier.short": { fr: "Sécurisation des titres fonciers, obtention des ACD et étude juridique complète de vos parcelles avant tout projet.", en: "Securing land titles, obtaining ACD certificates and a full legal review of your plots before any project." },
  "svc.foncier.desc": { fr: "Avant tout achat ou construction, nous auditons la situation juridique de la parcelle : titre foncier, Arrêté de Concession Définitive (ACD), bornage et viabilisation. Nous sécurisons vos investissements fonciers du premier jour jusqu'à la mutation notariée.", en: "Before any purchase or construction, we audit the plot's legal status: land title, ACD certificate, boundary survey and utility access. We secure your land investments from day one through to the notarised transfer." },

  "svc.construction.name": { fr: "Construction", en: "Construction" },
  "svc.construction.short": { fr: "Choix des matériaux nobles, respect strict des normes de sécurité et suivi minutieux de vos chantiers de construction.", en: "Premium materials, strict safety standards and meticulous oversight of your construction sites." },
  "svc.construction.desc": { fr: "De l'élaboration des plans architecturaux jusqu'à la livraison finale des clés : nous coordonnons les corps de métier, veillons au respect des délais et assurons un contrôle qualité rigoureux.", en: "From architectural plans to the final handover of keys: we coordinate every trade, keep projects on schedule and enforce rigorous quality control." },

  "svc.gestion.name": { fr: "Gestion / Location", en: "Rental Management" },
  "svc.gestion.short": { fr: "Recherche de locataires solvables, rédaction des baux, états des lieux et recouvrement sécurisé des loyers sans aucun tracas.", en: "Finding reliable tenants, drafting leases, move-in inspections and secure rent collection, hassle-free." },
  "svc.gestion.desc": { fr: "Libérez-vous de la gestion quotidienne de vos locataires. SCI4K prend en charge la recherche, la sélection, la rédaction des baux, l'encaissement et le reversement ponctuel de vos loyers.", en: "Free yourself from the day-to-day management of your tenants. SCI4K handles tenant search, screening, lease drafting, rent collection and timely payouts." },

  "svc.achat.name": { fr: "Achat", en: "Buying" },
  "svc.achat.short": { fr: "Résidence principale ou investissement locatif : nous cadrons vos critères et sécurisons chaque étape de l'acquisition.", en: "Primary residence or rental investment: we refine your criteria and secure every step of the acquisition." },
  "svc.achat.desc": { fr: "Résidence principale, villa de standing ou terrain à bâtir : nous analysons vos besoins et vous proposons des opportunités vérifiées sur le plan juridique et foncier (titre foncier, ACD disponible).", en: "Primary residence, high-end villa or building plot: we analyse your needs and offer opportunities that are fully verified on the legal and land-title side (land title, ACD available)." },

  "svc.vente.name": { fr: "Vente", en: "Selling" },
  "svc.vente.short": { fr: "Estimation précise, valorisation soignée du bien, filtrage rigoureux des acquéreurs : nous concluons rapidement aux meilleures conditions.", en: "Accurate valuation, careful staging and thorough buyer screening: we close quickly on the best terms." },
  "svc.vente.desc": { fr: "Valorisez au mieux votre patrimoine. SCI4K réalise une estimation rigoureuse basée sur les prix réels du marché à Abidjan et déploie un plan marketing ciblé pour trouver des acheteurs solvables.", en: "Get the best value for your property. SCI4K carries out a rigorous valuation based on real Abidjan market data and runs a targeted marketing plan to find qualified buyers." },

  "svc.administration.name": { fr: "Administration de biens", en: "Property Administration" },
  "svc.administration.short": { fr: "Comptabilité, syndic de copropriété, maintenance technique et suivi administratif complet de votre patrimoine.", en: "Accounting, co-ownership administration, technical maintenance and full administrative follow-up of your assets." },
  "svc.administration.desc": { fr: "Une gestion transparente de vos immeubles et copropriétés : syndic, comptabilité claire, maintenance préventive, interventions techniques et suivi administratif pour préserver la valeur de votre patrimoine dans la durée.", en: "Transparent management of your buildings and co-ownerships: administration, clear accounting, preventive maintenance, technical interventions and administrative follow-up to preserve the long-term value of your assets." },

  /* ===== HOME ===== */
  "home.hero.eyebrow": { fr: "Société Civile Immobilière — Abidjan", en: "Real Estate Company — Abidjan" },
  "home.hero.title": { fr: "Votre propriété,<br><em>notre priorité.</em>", en: "Your property,<br><em>our priority.</em>" },
  "home.hero.lede": { fr: "SCI4K accompagne particuliers et investisseurs dans l'achat, la vente, la location et la construction de biens d'exception à Abidjan — avec rigueur, transparence et un vrai sens du service.", en: "SCI4K supports individuals and investors in buying, selling, renting and building exceptional properties in Abidjan — with rigour, transparency and a genuine sense of service." },
  "home.hero.btnPrimary": { fr: "Rechercher un bien", en: "Search a property" },
  "home.hero.btnSecondary": { fr: "Découvrir SCI4K", en: "Discover SCI4K" },
  "home.hero.stat1": { fr: "Biens commercialisés", en: "Properties handled" },
  "home.hero.stat2": { fr: "Années d'expérience", en: "Years of experience" },
  "home.hero.stat3": { fr: "% clients satisfaits", en: "% satisfied clients" },

  "home.services.tag": { fr: "Nos services", en: "Our services" },
  "home.services.title": { fr: "Un accompagnement sur-mesure, à chaque étape", en: "Tailored support, at every step" },
  "home.services.lede": { fr: "De la recherche de biens à la signature et la gestion locative : SCI4K structure chaque projet avec la même exigence et rigueur professionnelle.", en: "From property search to signing and rental management: SCI4K structures every project with the same professional rigour." },
  "home.services.more": { fr: "En savoir plus", en: "Learn more" },

  "home.cta.title": { fr: "Prêt à concrétiser votre projet immobilier ?", en: "Ready to bring your real estate project to life?" },
  "home.cta.text": { fr: "Découvrez notre catalogue complet d'appartements, villas et terrains disponibles à l'achat et à la location sur notre page dédiée.", en: "Discover our full catalogue of apartments, villas and plots available for sale and rent on our dedicated page." },
  "home.cta.btn": { fr: "Consulter les biens", en: "Browse properties" },

  "home.partners.tag": { fr: "Écosystème institutionnel & financier", en: "Institutional & financial ecosystem" },
  "home.partners.title": { fr: "Nos Partenaires Privilégiés", en: "Our Trusted Partners" },

  "home.articles.tag": { fr: "Actualités & conseils", en: "News & advice" },
  "home.articles.title": { fr: "Nos derniers articles", en: "Our latest articles" },
  "home.articles.lede": { fr: "Foncier, marché et gestion locative : nos conseils d'experts pour réussir vos projets immobiliers à Abidjan.", en: "Land, market and rental management: our expert advice to succeed in your real estate projects in Abidjan." },
  "home.articles.read": { fr: "Lire l'article", en: "Read article" },
  "home.articles.a1.excerpt": { fr: "Les vérifications essentielles avant d'acheter un terrain : ACD, bornage et viabilisation.", en: "The essential checks before buying land: ACD, boundary survey and utility access." },
  "home.articles.a2.excerpt": { fr: "Cocody, Riviera, Bingerville : où et comment investir cette année.", en: "Cocody, Riviera, Bingerville: where and how to invest this year." },
  "home.articles.a3.excerpt": { fr: "Sélection des locataires, bail, état des lieux : nos bonnes pratiques au quotidien.", en: "Tenant screening, leases, inspections: our day-to-day best practices." },

  "home.testimonials.tag": { fr: "Avis clients", en: "Client reviews" },
  "home.testimonials.title": { fr: "Ce que disent nos clients", en: "What our clients say" },
  "home.testimonials.lede": { fr: "Quelques retours de propriétaires, acheteurs et locataires accompagnés par SCI4K à Abidjan.", en: "A few words from owners, buyers and tenants supported by SCI4K in Abidjan." },
  "home.testimonials.t1.quote": { fr: "« SCI4K a sécurisé l'achat de mon terrain à Bingerville de bout en bout. L'équipe a vérifié l'ACD et le bornage avant même que je signe quoi que ce soit. Un vrai sérieux. »", en: "\"SCI4K secured the purchase of my land in Bingerville from start to finish. The team checked the ACD and boundary survey before I even signed anything. Truly professional.\"" },
  "home.testimonials.t1.role": { fr: "Acheteuse à Bingerville", en: "Buyer in Bingerville" },
  "home.testimonials.t2.quote": { fr: "« Je confie la gestion locative de mes trois appartements à SCI4K depuis deux ans. Les loyers arrivent toujours à date et le suivi est transparent. »", en: "\"I've entrusted the rental management of my three apartments to SCI4K for two years. Rent always arrives on time and the reporting is transparent.\"" },
  "home.testimonials.t2.role": { fr: "Propriétaire à Cocody", en: "Landlord in Cocody" },
  "home.testimonials.t3.quote": { fr: "« Vente de notre villa conclue en six semaines, à un prix conforme à l'estimation. L'équipe a su filtrer les acquéreurs sérieux et nous faire gagner du temps. »", en: "\"Our villa sale closed in six weeks, at a price matching the valuation. The team filtered out serious buyers and saved us a lot of time.\"" },
  "home.testimonials.t3.role": { fr: "Vendeuse, Riviera", en: "Seller, Riviera" },

  /* ===== SERVICES PAGE ===== */
  "services.page.tag": { fr: "Expertise Immobilière", en: "Real Estate Expertise" },
  "services.page.title": { fr: "Nos Services & Prestations", en: "Our Services" },
  "services.page.lede": { fr: "SCI4K propose une gamme complète de solutions immobilières adaptées aux exigences des particuliers, propriétaires et investisseurs à Abidjan.", en: "SCI4K offers a full range of real estate solutions tailored to individuals, owners and investors in Abidjan." },

  "svc.foncier.feat1": { fr: "Vérification foncière & ACD", en: "Land title & ACD verification" },
  "svc.foncier.feat2": { fr: "Bornage & viabilisation", en: "Boundary survey & utilities" },
  "svc.foncier.feat3": { fr: "Audit juridique complet", en: "Full legal audit" },
  "svc.foncier.cta": { fr: "Sécuriser mon terrain →", en: "Secure my land →" },

  "svc.construction.feat1": { fr: "Maîtrise d'ouvrage", en: "Project management" },
  "svc.construction.feat2": { fr: "Respect des budgets", en: "Budget control" },
  "svc.construction.feat3": { fr: "Contrôle qualité permanent", en: "Continuous quality control" },
  "svc.construction.cta": { fr: "Discuter de mon projet →", en: "Discuss my project →" },

  "svc.gestion.feat1": { fr: "Recouvrement des loyers", en: "Rent collection" },
  "svc.gestion.feat2": { fr: "Gestion des quittances", en: "Receipt management" },
  "svc.gestion.feat3": { fr: "Entretien courant", en: "Routine upkeep" },
  "svc.gestion.cta": { fr: "Confier mes biens →", en: "Entrust my rentals →" },

  "svc.achat.feat1": { fr: "Vérification foncière & ACD", en: "Land title & ACD verification" },
  "svc.achat.feat2": { fr: "Négociation notariée", en: "Notarised negotiation" },
  "svc.achat.feat3": { fr: "Recherche personnalisée", en: "Personalised search" },
  "svc.achat.cta": { fr: "Démarrer mon achat →", en: "Start my purchase →" },

  "svc.vente.feat1": { fr: "Estimation gratuite", en: "Free valuation" },
  "svc.vente.feat2": { fr: "Filtre d'acquéreurs", en: "Buyer screening" },
  "svc.vente.feat3": { fr: "Diffusion de prestige", en: "Premium listing exposure" },
  "svc.vente.cta": { fr: "Estimer mon bien →", en: "Get a valuation →" },

  "svc.administration.feat1": { fr: "Comptabilité claire", en: "Transparent accounting" },
  "svc.administration.feat2": { fr: "Maintenance technique", en: "Technical maintenance" },
  "svc.administration.feat3": { fr: "Suivi administratif complet", en: "Full administrative follow-up" },
  "svc.administration.cta": { fr: "Confier mon patrimoine →", en: "Entrust my assets →" },

  "services.process.tag": { fr: "Notre Méthode", en: "Our Method" },
  "services.process.title": { fr: "Comment nous travaillons avec vous", en: "How we work with you" },
  "services.process.lede": { fr: "Une démarche claire en 4 étapes pour la concrétisation en toute sérénité de vos projets.", en: "A clear 4-step process to bring your projects to life with peace of mind." },
  "services.process.step1.title": { fr: "Écoute & Analyse", en: "Listening & Analysis" },
  "services.process.step1.text": { fr: "Prise de contact approfondie pour comprendre vos objectifs, vos critères et votre budget.", en: "An in-depth first contact to understand your goals, criteria and budget." },
  "services.process.step2.title": { fr: "Sélection & Audit", en: "Selection & Audit" },
  "services.process.step2.text": { fr: "Propositions ciblées et vérification complète des volets juridiques, techniques et fonciers.", en: "Targeted proposals with a full check of legal, technical and land-title aspects." },
  "services.process.step3.title": { fr: "Négociation & Acte", en: "Negotiation & Deed" },
  "services.process.step3.text": { fr: "Sécurisation des transactions avec notre réseau de notaires et partenaires agréés.", en: "Secure transactions through our network of notaries and approved partners." },
  "services.process.step4.title": { fr: "Suivi Continu", en: "Ongoing Follow-up" },
  "services.process.step4.text": { fr: "Un accompagnement permanent même après la signature ou la livraison du bien.", en: "Continuous support even after signing or handover of the property." },

  /* ===== ABOUT / PRÉSENTATION PAGE ===== */
  "about.page.tag": { fr: "À propos de notre société", en: "About our company" },
  "about.page.title": { fr: "Excellence, Transparence & Vision Durable", en: "Excellence, Transparency & Sustainable Vision" },
  "about.page.lede": { fr: "Découvrez la vision et l'engagement de SCI4K pour développer le patrimoine immobilier d'Abidjan avec rigueur et intégrité.", en: "Discover SCI4K's vision and commitment to developing Abidjan's real estate assets with rigour and integrity." },

  "about.overview.tag": { fr: "Qui sommes-nous", en: "Who we are" },
  "about.overview.title": { fr: "Présentation Générale de SCI4K", en: "General Overview of SCI4K" },
  "about.overview.p1": { fr: "SCI4K est une Société Civile Immobilière de référence basée à Abidjan (Cocody, Cité des Arts). Née d'une ambition forte de moderniser et d'assainir le secteur immobilier ivoirien, notre structure s'impose comme l'interlocuteur de confiance privilégié des particuliers, entreprises et investisseurs institutionnels.", en: "SCI4K is a leading real estate company based in Abidjan (Cocody, Cité des Arts). Born from a strong ambition to modernise and clean up the Ivorian real estate sector, our company has become the trusted partner of choice for individuals, businesses and institutional investors." },
  "about.overview.p2": { fr: "Forts d'une connaissance intime du marché d'Abidjan et des enjeux fonciers locaux, nous intervenons sur l'ensemble de la chaîne de valeur : achat de terrains viabilisés avec ACD, vente de logements neufs et de prestige, promotion, construction sur-mesure, gestion locative sécurisée et administration de biens.", en: "With an intimate knowledge of the Abidjan market and local land issues, we operate across the entire value chain: buying serviced land with ACD, selling new and premium homes, development, tailored construction, secure rental management and property administration." },
  "about.overview.h1title": { fr: "Expertise Juridique", en: "Legal Expertise" },
  "about.overview.h1text": { fr: "Toutes nos transactions et acquisitions sont scrupuleusement auditées par des notaires agréés.", en: "All our transactions and acquisitions are scrupulously audited by licensed notaries." },
  "about.overview.h2title": { fr: "Ancrage Abidjanais", en: "Rooted in Abidjan" },
  "about.overview.h2text": { fr: "Une présence active dans tous les secteurs stratégiques (Cocody, Plateau, Riviera, Marcory, Bingerville).", en: "An active presence across every strategic district (Cocody, Plateau, Riviera, Marcory, Bingerville)." },

  "about.dg.floatCard": { fr: "quartiers d'Abidjan couverts par notre réseau d'agents", en: "Abidjan districts covered by our agent network" },
  "about.dg.tag": { fr: "Mot du directeur", en: "Word from the CEO" },
  "about.dg.title": { fr: "Bâtir des lieux de vie, pas seulement des bâtiments.", en: "Building places to live, not just buildings." },
  "about.dg.p1": { fr: "La Côte d'Ivoire dispose d'un potentiel foncier et immobilier exceptionnel. Chez SCI4K, nous pensons que l'immobilier va bien au-delà de la simple construction : il s'agit de concevoir et valoriser des espaces de vie où se rencontrent sécurité, confort moderne et valeur patrimoniale pérenne.", en: "Côte d'Ivoire has exceptional land and real estate potential. At SCI4K, we believe real estate goes far beyond simple construction: it's about designing and enhancing living spaces where security, modern comfort and lasting asset value come together." },
  "about.dg.p2": { fr: "Chaque projet que nous portons est structuré avec une rigueur absolue, du choix stratégique de l'emplacement à la concrétisation notariée et technique. Nous nous engageons à offrir une expérience transparente et rassurante pour tous nos clients et investisseurs.", en: "Every project we carry out is structured with absolute rigour, from the strategic choice of location through to the notarised and technical completion. We are committed to offering a transparent, reassuring experience to every client and investor." },
  "about.dg.p3": { fr: "Notre équipe d'experts à taille humaine reste guidée par une conviction simple : la réussite d'un projet immobilier se mesure à la satisfaction et à la tranquillité d'esprit de celles et ceux qui nous accordent leur confiance.", en: "Our close-knit team of experts is guided by one simple conviction: the success of a real estate project is measured by the satisfaction and peace of mind of those who place their trust in us." },
  "about.dg.sigName": { fr: "Le Directeur Général", en: "The Chief Executive Officer" },

  "about.values.tag": { fr: "Nos Piliers", en: "Our Pillars" },
  "about.values.title": { fr: "Les engagements de SCI4K", en: "SCI4K's commitments" },
  "about.values.lede": { fr: "Quatre principes fondamentaux qui guident l'ensemble de nos actions au quotidien.", en: "Four core principles that guide everything we do, every day." },
  "about.values.v1title": { fr: "Rigueur & Sécurité", en: "Rigour & Security" },
  "about.values.v1text": { fr: "Toutes nos transactions et projets de construction respectent les exigences juridiques, foncières (ACD) et réglementaires en vigueur en Côte d'Ivoire.", en: "All our transactions and construction projects comply with the legal, land-title (ACD) and regulatory requirements in force in Côte d'Ivoire." },
  "about.values.v2title": { fr: "Transparence Totale", en: "Total Transparency" },
  "about.values.v2text": { fr: "Pas de coûts cachés ni de promesses irréalistes. Nous fournissons des bilans clairs, des estimations précises et un suivi continu à nos partenaires.", en: "No hidden costs, no unrealistic promises. We provide clear statements, accurate valuations and continuous follow-up to our partners." },
  "about.values.v3title": { fr: "Ancrage Abidjanais", en: "Rooted in Abidjan" },
  "about.values.v3text": { fr: "Une connaissance fine et approfondie des quartiers d'Abidjan, des tendances du marché foncier et des opportunités d'investissement à fort potentiel.", en: "A deep, detailed knowledge of Abidjan's districts, land market trends and high-potential investment opportunities." },
  "about.values.v4title": { fr: "Service Client VIP", en: "VIP Customer Service" },
  "about.values.v4text": { fr: "Un accompagnement sur-mesure et personnalisé, du premier échange jusqu'à la remise des clés et la gestion à long terme de votre bien.", en: "Tailored, personal support from the very first contact through to key handover and the long-term management of your property." },

  "about.team.tag": { fr: "Capital Humain", en: "Human Capital" },
  "about.team.title": { fr: "Notre Équipe d'Experts", en: "Our Team of Experts" },
  "about.team.lede": { fr: "Des professionnels passionnés et chevronnés à votre service pour vous conseiller à chaque étape de vos projets immobiliers à Abidjan.", en: "Passionate, seasoned professionals ready to advise you at every step of your real estate projects in Abidjan." },
  "about.team.badge1": { fr: "Direction", en: "Management" },
  "about.team.role1": { fr: "Directeur Général & Fondateur", en: "CEO & Founder" },
  "about.team.desc1": { fr: "Spécialiste de la structuration foncière et des investissements immobiliers majeurs en Côte d'Ivoire depuis plus de 12 ans.", en: "Specialist in land structuring and major real estate investments in Côte d'Ivoire for over 12 years." },
  "about.team.badge2": { fr: "Ventes", en: "Sales" },
  "about.team.role2": { fr: "Responsable Transactions & Ventes", en: "Head of Transactions & Sales" },
  "about.team.desc2": { fr: "Experte dans la négociation notariée et l'accompagnement personnalisé pour l'acquisition de villas et appartements de standing.", en: "Expert in notarised negotiation and personalised support for acquiring high-end villas and apartments." },
  "about.team.badge3": { fr: "Foncier", en: "Land" },
  "about.team.role3": { fr: "Expert Foncier & Juridique ACD", en: "Land & ACD Legal Expert" },
  "about.team.desc3": { fr: "Spécialiste du contentieux foncier, de l'obtention des ACD et de la viabilisation des parcelles sur le grand Abidjan.", en: "Specialist in land disputes, obtaining ACD certificates and servicing plots across greater Abidjan." },
  "about.team.badge4": { fr: "Gestion", en: "Management" },
  "about.team.role4": { fr: "Responsable Gestion Locative & Administration", en: "Head of Rental & Property Administration" },
  "about.team.desc4": { fr: "Supervise la gestion locative des immeubles, les baux commerciaux et la comptabilité transparente des copropriétés.", en: "Oversees rental management of buildings, commercial leases and transparent co-ownership accounting." },

  /* ===== BIENS / PROPERTIES PAGE ===== */
  "biens.page.tag": { fr: "Catalogue de biens", en: "Property catalogue" },
  "biens.page.title": { fr: "Biens Immobiliers à Abidjan", en: "Real Estate in Abidjan" },
  "biens.page.lede": { fr: "Trouvez le bien idéal à l'achat ou à la location. Cliquez sur un bien pour consulter sa fiche descriptive intégrale.", en: "Find the ideal property to buy or rent. Click on a listing to view its full description." },
  "biens.mode.location": { fr: "Location", en: "Rent" },
  "biens.mode.vente": { fr: "Vente", en: "Sale" },
  "biens.filters.title": { fr: "Filtres multicritères", en: "Advanced filters" },
  "biens.filters.type": { fr: "Type de bien", en: "Property type" },
  "biens.filters.typeAll": { fr: "Tous les types", en: "All types" },
  "biens.filters.typeVilla": { fr: "Villa & Duplex", en: "Villa & Duplex" },
  "biens.filters.typeAppt": { fr: "Appartement & Studio", en: "Apartment & Studio" },
  "biens.filters.typeImmeuble": { fr: "Immeuble de rapport", en: "Income building" },
  "biens.filters.typeTerrain": { fr: "Terrain viabilisé", en: "Serviced land" },
  "biens.filters.loc": { fr: "Localité", en: "Location" },
  "biens.filters.locAll": { fr: "Toutes les zones", en: "All areas" },
  "biens.filters.locPlateau": { fr: "Le Plateau", en: "Le Plateau" },
  "biens.filters.rooms": { fr: "Nombre de Pièces", en: "Number of rooms" },
  "biens.filters.roomsAll": { fr: "Toutes pièces", en: "Any number" },
  "biens.filters.rooms12": { fr: "1 à 2 pièces", en: "1 to 2 rooms" },
  "biens.filters.rooms34": { fr: "3 à 4 pièces", en: "3 to 4 rooms" },
  "biens.filters.rooms5": { fr: "5+ pièces", en: "5+ rooms" },
  "biens.filters.surface": { fr: "Surface (m²)", en: "Surface area (m²)" },
  "biens.filters.surfaceAll": { fr: "Toutes surfaces", en: "Any surface" },
  "biens.filters.submit": { fr: "Rechercher le bien idéal →", en: "Search for the ideal property →" },
  "biens.pill.all": { fr: "Tous", en: "All" },
  "biens.pill.villas": { fr: "Villas", en: "Villas" },
  "biens.pill.appts": { fr: "Appartements", en: "Apartments" },
  "biens.pill.terrains": { fr: "Terrains", en: "Land" },
  "biens.viewSheet": { fr: "Voir la fiche", en: "View listing" },
  "biens.unit.rooms": { fr: "ch.", en: "bed." },
  "biens.unit.baths": { fr: "sdb", en: "bath" },
  "biens.unit.living": { fr: "salons", en: "living rms" },
  "biens.unit.lots": { fr: "lots", en: "units" },
  "biens.unit.available": { fr: "dispo.", en: "avail." },

  "biens.p0.type": { fr: "Villa moderne · F5", en: "Modern villa · F5" },
  "biens.p0.title": { fr: "Villa Les Palmiers", en: "Villa Les Palmiers" },
  "biens.p0.loc": { fr: "Riviera Golf, Cocody", en: "Riviera Golf, Cocody" },
  "biens.p1.type": { fr: "Appartement · F3", en: "Apartment · F3" },
  "biens.p1.title": { fr: "Résidence Alba", en: "Résidence Alba" },
  "biens.p1.loc": { fr: "Cocody Angré, Abidjan", en: "Cocody Angré, Abidjan" },
  "biens.p2.type": { fr: "Terrain viabilisé", en: "Serviced land" },
  "biens.p2.title": { fr: "Lot Bonoua ACD", en: "Lot Bonoua ACD" },
  "biens.p2.loc": { fr: "Bingerville, Abidjan", en: "Bingerville, Abidjan" },
  "biens.p3.type": { fr: "Maison duplex · F6", en: "Duplex house · F6" },
  "biens.p3.title": { fr: "Villa M'Batto", en: "Villa M'Batto" },
  "biens.p3.loc": { fr: "Marcory, Abidjan", en: "Marcory, Abidjan" },
  "biens.p4.type": { fr: "Immeuble de rapport", en: "Income building" },
  "biens.p4.title": { fr: "Résidence Étoile", en: "Résidence Étoile" },
  "biens.p4.loc": { fr: "Abatta, Abidjan", en: "Abatta, Abidjan" },
  "biens.p5.type": { fr: "Studio meublé", en: "Furnished studio" },
  "biens.p5.title": { fr: "Le Plateau Loft", en: "Le Plateau Loft" },
  "biens.p5.loc": { fr: "Le Plateau, Abidjan", en: "Le Plateau, Abidjan" },

  "biens.modal.type": { fr: "Type de bien", en: "Property type" },
  "biens.modal.surface": { fr: "Surface totale", en: "Total surface" },
  "biens.modal.rooms": { fr: "Pièces / Chambres", en: "Rooms / Bedrooms" },
  "biens.modal.legal": { fr: "Statut Juridique", en: "Legal status" },
  "biens.modal.descTitle": { fr: "Description Intégrale du Bien", en: "Full Property Description" },
  "biens.modal.featTitle": { fr: "Équipements & Prestations incluses :", en: "Amenities & Features included:" },
  "biens.modal.cta": { fr: "Planifier une visite →", en: "Schedule a visit →" },

  /* ===== CONTACT PAGE ===== */
  "contact.page.tag": { fr: "Échangeons sur votre projet", en: "Let's talk about your project" },
  "contact.page.title": { fr: "Contactez SCI4K", en: "Contact SCI4K" },
  "contact.page.lede": { fr: "Une question, un projet d'achat, de vente, de location ou de construction à Abidjan ? Notre équipe d'experts est à votre entière disposition.", en: "A question, or a buying, selling, renting or construction project in Abidjan? Our team of experts is entirely at your disposal." },
  "contact.form.title": { fr: "Envoyez-nous un message", en: "Send us a message" },
  "contact.form.sub": { fr: "Remplissez le formulaire ci-dessous et notre équipe vous recontactera sous 24 heures ouvrées.", en: "Fill in the form below and our team will get back to you within 24 business hours." },
  "contact.form.success": { fr: "Votre message est prêt : la conversation WhatsApp s'ouvre dans un nouvel onglet. Appuyez sur Envoyer pour le transmettre à SCI4K.", en: "Your message is ready: the WhatsApp conversation is opening in a new tab. Press Send to deliver it to SCI4K." },
  "contact.form.name": { fr: "Nom complet *", en: "Full name *" },
  "contact.form.namePh": { fr: "Ex: Jean Kouassi", en: "E.g. Jean Kouassi" },
  "contact.form.phone": { fr: "Téléphone *", en: "Phone *" },
  "contact.form.email": { fr: "Adresse Email *", en: "Email address *" },
  "contact.form.subject": { fr: "Sujet de votre demande", en: "Subject of your request" },
  "contact.form.subj.achat": { fr: "Achat de bien / terrain", en: "Buying a property / land" },
  "contact.form.subj.vente": { fr: "Vente / Estimation de bien", en: "Selling / Property valuation" },
  "contact.form.subj.location": { fr: "Location d'un bien", en: "Renting a property" },
  "contact.form.subj.gestion": { fr: "Gestion locative & Administration", en: "Rental & property administration" },
  "contact.form.subj.construction": { fr: "Projet de Construction", en: "Construction project" },
  "contact.form.subj.foncier": { fr: "Question Foncier / ACD", en: "Land / ACD question" },
  "contact.form.subj.autre": { fr: "Autre demande", en: "Other request" },
  "contact.form.message": { fr: "Votre message *", en: "Your message *" },
  "contact.form.messagePh": { fr: "Précisez les détails de votre projet, le quartier souhaité, le budget approximatif...", en: "Share the details of your project, preferred area, approximate budget..." },
  "contact.form.submit": { fr: "Envoyer mon message", en: "Send my message" },
  "contact.info.hqTitle": { fr: "Siège Social", en: "Head Office" },
  "contact.info.phoneTitle": { fr: "Téléphone & WhatsApp", en: "Phone & WhatsApp" },
  "contact.info.emailTitle": { fr: "Email", en: "Email" },
  "contact.info.hoursTitle": { fr: "Horaires d'ouverture", en: "Opening hours" },
  "contact.info.hoursText": { fr: "Lundi — Vendredi : 08h00 - 18h00<br>Samedi : 09h00 - 13h00", en: "Monday — Friday: 8:00 AM – 6:00 PM<br>Saturday: 9:00 AM – 1:00 PM" },
  "contact.map.title": { fr: "Notre localisation à Cocody", en: "Our location in Cocody" },
  "contact.map.addrSub": { fr: "Cocody, Cité des Arts — Abidjan, Côte d'Ivoire", en: "Cocody, Cité des Arts — Abidjan, Côte d'Ivoire" },
  "contact.map.openLink": { fr: "Ouvrir dans Google Maps", en: "Open in Google Maps" },

  /* ===== 404 PAGE ===== */
  "e404.title": { fr: "Cette page est introuvable", en: "This page can't be found" },
  "e404.text": { fr: "La page que vous recherchez a peut-être été déplacée, renommée ou n'existe plus. Vérifiez l'adresse ou repartez de notre page d'accueil.", en: "The page you're looking for may have been moved, renamed, or no longer exists. Check the address or head back to our homepage." },
  "e404.btnHome": { fr: "Retour à l'accueil", en: "Back to home" },
  "e404.btnContact": { fr: "Contacter SCI4K", en: "Contact SCI4K" },

  /* ===== ERROR 500 ===== */
  "e500.title": { fr: "Une erreur est survenue de notre côté", en: "Something went wrong on our end" },
  "e500.text": { fr: "Nos serveurs n'ont pas pu traiter votre demande. Le problème vient de chez nous, pas de vous. Réessayez dans quelques instants ou contactez-nous si cela persiste.", en: "Our servers could not process your request. The problem is on our side, not yours. Try again in a moment, or contact us if it persists." },
  "e500.btnHome": { fr: "Retour à l'accueil", en: "Back to home" },
  "e500.btnRetry": { fr: "Réessayer", en: "Try again" },
  "e500.btnContact": { fr: "Contacter SCI4K", en: "Contact SCI4K" },

  /* ===== ENCARTS / ANNONCES ===== */
  "ad.label": { fr: "Annonce", en: "Advertisement" },
  "ad.house.tag": { fr: "Nouveau programme", en: "New development" },
  "ad.house.title": { fr: "Terrains viabilises a Bingerville, ACD en main", en: "Serviced plots in Bingerville, ACD in hand" },
  "ad.house.text": { fr: "Parcelles de 500 a 1 000 m2 dans un lotissement securise, titres verifies avant mise en vente. Visites accompagnees sur rendez-vous.", en: "Plots from 500 to 1,000 sq m in a secured subdivision, titles verified before listing. Guided viewings by appointment." },
  "ad.house.btn": { fr: "Voir les parcelles", en: "View the plots" },

  /* ===== FAQ PAGE ===== */
  "faq.page.tag": { fr: "Questions fréquentes", en: "Frequently asked questions" },
  "faq.page.title": { fr: "Toutes les réponses à vos questions", en: "All the answers to your questions" },
  "faq.page.lede": { fr: "Foncier, construction, gestion locative, achat, vente et administration de biens : retrouvez nos réponses aux questions les plus posées par nos clients à Abidjan.", en: "Land, construction, rental management, buying, selling and property administration: find our answers to the questions our Abidjan clients ask most." },

  "faq.q1.q": { fr: "Qu'est-ce qu'un ACD et pourquoi est-il important ?", en: "What is an ACD and why does it matter?" },
  "faq.q1.a": { fr: "L'Arrêté de Concession Définitive (ACD) est le document officiel qui atteste qu'un terrain vous appartient de façon définitive et sécurisée en Côte d'Ivoire. Sans ACD, un terrain reste juridiquement vulnérable. SCI4K vérifie systématiquement ce document avant toute transaction.", en: "The Definitive Concession Order (ACD) is the official document confirming that a plot of land belongs to you definitively and securely in Côte d'Ivoire. Without an ACD, a plot remains legally vulnerable. SCI4K systematically verifies this document before any transaction." },
  "faq.q2.q": { fr: "Comment SCI4K vérifie-t-elle la situation juridique d'un terrain ?", en: "How does SCI4K verify a plot's legal status?" },
  "faq.q2.a": { fr: "Nous auditons le titre foncier, le bornage, l'historique des mutations et la conformité avec les documents d'urbanisme, en lien avec notre réseau de notaires agréés, avant de proposer un terrain à nos clients.", en: "We audit the land title, boundary survey, transfer history and compliance with zoning documents, working with our network of licensed notaries, before offering any plot to our clients." },

  "faq.q3.q": { fr: "SCI4K construit-elle elle-même les bâtiments ou coordonne-t-elle des entreprises partenaires ?", en: "Does SCI4K build directly, or coordinate partner companies?" },
  "faq.q3.a": { fr: "SCI4K assure la maîtrise d'ouvrage : nous coordonnons un réseau d'entreprises et d'artisans qualifiés, supervisons chaque étape du chantier et garantissons le respect des plans, des délais et des normes de sécurité.", en: "SCI4K acts as project owner's representative: we coordinate a network of qualified companies and craftsmen, oversee every stage of the site, and guarantee compliance with plans, schedules and safety standards." },
  "faq.q4.q": { fr: "Quels sont les délais moyens pour un projet de construction ?", en: "What is the average timeline for a construction project?" },
  "faq.q4.a": { fr: "Selon l'ampleur du projet, comptez généralement entre 6 et 14 mois entre la validation des plans et la livraison des clés. Un calendrier détaillé vous est communiqué dès la signature du contrat.", en: "Depending on the size of the project, expect generally between 6 and 14 months between plan approval and key handover. A detailed schedule is provided as soon as the contract is signed." },

  "faq.q5.q": { fr: "Comment fonctionne la gestion locative avec SCI4K ?", en: "How does rental management work with SCI4K?" },
  "faq.q5.a": { fr: "Nous recherchons et sélectionnons des locataires solvables, rédigeons le bail, réalisons l'état des lieux, encaissons les loyers et vous reversons les sommes dues chaque mois, avec un reporting transparent.", en: "We find and screen reliable tenants, draft the lease, carry out the move-in inspection, collect rent and pay out what's owed to you every month, with transparent reporting." },
  "faq.q6.q": { fr: "Que se passe-t-il en cas de loyer impayé ?", en: "What happens if a tenant doesn't pay rent?" },
  "faq.q6.a": { fr: "Nous relançons immédiatement le locataire et engageons les démarches amiables puis, si nécessaire, juridiques prévues par le bail, en vous tenant informé à chaque étape.", en: "We follow up with the tenant immediately and initiate amicable steps, then legal steps provided for in the lease if necessary, keeping you informed at every stage." },

  "faq.q7.q": { fr: "Quelles garanties ai-je lors de l'achat d'un bien via SCI4K ?", en: "What guarantees do I have when buying through SCI4K?" },
  "faq.q7.a": { fr: "Chaque bien proposé fait l'objet d'une vérification juridique et foncière complète avant commercialisation. La transaction est ensuite sécurisée par un acte notarié, en présence de nos conseillers.", en: "Every property we list undergoes a full legal and land-title check before being marketed. The transaction is then secured with a notarised deed, with our advisors present." },
  "faq.q8.q": { fr: "Puis-je visiter un bien avant de m'engager ?", en: "Can I visit a property before committing?" },
  "faq.q8.a": { fr: "Oui, toute visite se fait sur rendez-vous avec l'un de nos conseillers, qui vous accompagne également dans l'analyse technique et juridique du bien avant votre décision.", en: "Yes, every visit is arranged by appointment with one of our advisors, who also guides you through the technical and legal review of the property before you decide." },

  "faq.q9.q": { fr: "Comment SCI4K estime-t-elle la valeur de mon bien ?", en: "How does SCI4K value my property?" },
  "faq.q9.a": { fr: "Notre estimation s'appuie sur les prix réels constatés sur le marché d'Abidjan, l'état du bien, son emplacement et sa situation juridique. Cette estimation est gratuite et sans engagement.", en: "Our valuation is based on actual prices observed on the Abidjan market, the property's condition, location and legal status. This valuation is free and comes with no obligation." },
  "faq.q10.q": { fr: "Combien de temps faut-il en moyenne pour vendre un bien ?", en: "How long does it typically take to sell a property?" },
  "faq.q10.a": { fr: "Cela varie selon le type de bien et le marché, mais notre plan marketing ciblé et notre réseau d'acquéreurs qualifiés permettent généralement de conclure une vente en quelques semaines à quelques mois.", en: "It varies by property type and market conditions, but our targeted marketing plan and network of qualified buyers usually allow us to close a sale within a few weeks to a few months." },

  "faq.q11.q": { fr: "En quoi consiste l'administration de biens proposée par SCI4K ?", en: "What does SCI4K's property administration service include?" },
  "faq.q11.a": { fr: "Nous prenons en charge la comptabilité, la maintenance technique, le suivi administratif et la valorisation de vos immeubles et copropriétés, pour préserver la valeur de votre patrimoine dans la durée.", en: "We handle accounting, technical maintenance, administrative follow-up and the enhancement of your buildings and co-ownerships, to preserve the value of your assets over time." },
  "faq.q12.q": { fr: "SCI4K gère-t-elle les copropriétés (syndic) ?", en: "Does SCI4K manage co-ownerships (as a syndic)?" },
  "faq.q12.a": { fr: "Oui, l'administration de biens de SCI4K inclut la gestion des copropriétés : organisation des assemblées, comptabilité claire et maintenance des parties communes.", en: "Yes, SCI4K's property administration includes co-ownership management: organising general meetings, transparent accounting and maintenance of common areas." },

  "faq.ask.title": { fr: "Vous ne trouvez pas votre réponse ?", en: "Can't find your answer?" },
  "faq.ask.sub": { fr: "Posez-nous directement votre question, un conseiller SCI4K vous répondra sous 24 heures ouvrées.", en: "Ask us your question directly, an SCI4K advisor will get back to you within 24 business hours." },
  "faq.ask.success": { fr: "Merci ! Votre question a bien été envoyée. Un conseiller SCI4K vous répondra très rapidement.", en: "Thank you! Your question has been sent. An SCI4K advisor will reply shortly." },
  "faq.ask.name": { fr: "Nom complet *", en: "Full name *" },
  "faq.ask.email": { fr: "Adresse Email *", en: "Email address *" },
  "faq.ask.question": { fr: "Votre question *", en: "Your question *" },
  "faq.ask.questionPh": { fr: "Écrivez votre question ici...", en: "Write your question here..." },
  "faq.ask.submit": { fr: "Envoyer ma question →", en: "Send my question →" },

  /* ===== ACTUALITES ===== */
  "news.page.tag": { fr: "Actualités & conseils", en: "News & advice" },
  "news.page.title": { fr: "Actualités SCI4K", en: "SCI4K News" },
  "news.filter.all": { fr: "Toutes", en: "All" },
  "news.filter.searchLabel": { fr: "Rechercher", en: "Search" },
  "news.filter.searchPh": { fr: "Titre, mot-clé…", en: "Title, keyword…" },
  "news.filter.from": { fr: "Du", en: "From" },
  "news.filter.to": { fr: "Au", en: "To" },
  "news.filter.submit": { fr: "Rechercher", en: "Search" },
  "news.filter.empty": { fr: "Aucune actualité ne correspond à votre recherche.", en: "No news item matches your search." },
  "news.backToList": { fr: "← Retour aux actualités", en: "← Back to news" },
  "news.cta.title": { fr: "Une question sur l'un de ces sujets ?", en: "A question on any of these topics?" },
  "news.cta.text": { fr: "Nos conseillers répondent à vos questions sur le foncier, l'achat, la location et la gestion de votre patrimoine à Abidjan.", en: "Our advisers answer your questions on land, purchase, rental and property management in Abidjan." },
  "news.cta.btn": { fr: "Contacter SCI4K", en: "Contact SCI4K" },
  "home.scrollCue": { fr: "Défilez", en: "Scroll" },

  /* --- argumentaire des services --- */
  "svc.foncier.feat1Arg": { fr: "Nous contrôlons le titre auprès de l'administration et confirmons l'existence d'un ACD avant toute signature.", en: "We check the title with the authorities and confirm an ACD exists before any signature." },
  "svc.foncier.feat2Arg": { fr: "Un géomètre agréé matérialise les limites, et nous vérifions le raccordement effectif aux réseaux.", en: "A licensed surveyor marks the boundaries, and we verify that utility connections actually exist." },
  "svc.foncier.feat3Arg": { fr: "Recherche de litiges, de doubles ventes et d'hypothèques inscrites sur la parcelle.", en: "We search for disputes, double sales and any mortgage registered against the plot." },
  "svc.construction.feat1Arg": { fr: "Nous portons la coordination des corps de métier et la tenue du calendrier à votre place.", en: "We take on trade coordination and schedule-keeping on your behalf." },
  "svc.construction.feat2Arg": { fr: "Chaque poste est chiffré avant démarrage puis suivi à l'avancement, avec alerte au moindre écart.", en: "Every line item is costed before work starts, then tracked as it progresses, with an alert on any deviation." },
  "svc.construction.feat3Arg": { fr: "Des points de contrôle imposés à chaque phase, du ferraillage des fondations à la levée des réserves.", en: "Mandatory checkpoints at every phase, from foundation reinforcement to clearing the snag list." },
  "svc.gestion.feat1Arg": { fr: "Encaissement à date fixe, relance immédiate et procédure formalisée dès le premier impayé.", en: "Collection on a fixed date, immediate follow-up and a formal procedure from the first missed payment." },
  "svc.gestion.feat2Arg": { fr: "Une quittance émise à chaque règlement et archivée, avec un état locatif consultable à tout moment.", en: "A receipt issued and filed for every payment, with a rental statement available at any time." },
  "svc.gestion.feat3Arg": { fr: "Interventions techniques prises en charge par nos artisans référencés, sans que vous ayez à intervenir.", en: "Technical work handled by our vetted tradespeople, with nothing required from you." },
  "svc.achat.feat1Arg": { fr: "Aucun bien ne vous est présenté sans que sa situation juridique ait été établie au préalable.", en: "No property is shown to you before its legal standing has been established." },
  "svc.achat.feat2Arg": { fr: "Nous portons la négociation puis coordonnons le notaire jusqu'à la remise des clés.", en: "We lead the negotiation, then coordinate the notary through to handover of the keys." },
  "svc.achat.feat3Arg": { fr: "Un cahier des charges écrit avec vous, puis une sélection filtrée plutôt qu'un catalogue.", en: "A brief written with you, then a filtered shortlist rather than a catalogue." },
  "svc.vente.feat1Arg": { fr: "Une valeur établie sur les transactions comparables du quartier, pas sur une moyenne nationale.", en: "A value based on comparable local transactions, not a national average." },
  "svc.vente.feat2Arg": { fr: "Nous vérifions la capacité de financement des candidats avant d'organiser la moindre visite.", en: "We check each candidate's financing capacity before arranging a single viewing." },
  "svc.vente.feat3Arg": { fr: "Reportage photo soigné et diffusion ciblée sur nos canaux et notre réseau d'apporteurs.", en: "Careful photography and targeted distribution across our channels and referral network." },
  "svc.administration.feat1Arg": { fr: "Un état des charges et des recettes détaillé, remis chaque trimestre et justifié pièce par pièce.", en: "A detailed statement of charges and income, issued quarterly and supported document by document." },
  "svc.administration.feat2Arg": { fr: "Suivi des contrats d'entretien, des visites réglementaires et des interventions d'urgence.", en: "Management of maintenance contracts, statutory inspections and emergency call-outs." },
  "svc.administration.feat3Arg": { fr: "Convocations, procès-verbaux d'assemblée et relations avec les copropriétaires pris en charge.", en: "Meeting notices, minutes and relations with co-owners all handled for you." },

  /* --- articles 4 a 12 --- */
  "news.a4.date": { fr: "8 Juillet 2026", en: "8 July 2026" },
  "news.a4.title": { fr: "Faire construire à Abidjan : à quoi ressemble le budget réel", en: "Building in Abidjan: what the real budget looks like" },
  "news.a4.p1": { fr: "Beaucoup de projets de construction démarrent sur une estimation au mètre carré trouvée en ligne, puis dérapent dès les fondations. Le coût réel dépend surtout de trois variables que l'on sous-estime : la nature du sol, la distance aux réseaux et le niveau de finition visé.", en: "Many building projects start from a per-square-metre estimate found online, then slip as soon as the foundations are poured. The real cost depends mainly on three variables people underestimate: soil conditions, distance to utility networks, and the intended finish level." },
  "news.a4.p2": { fr: "Un terrain en zone marécageuse peut exiger des fondations spéciales qui alourdissent le gros œuvre de vingt à trente pour cent. C'est pourquoi une étude de sol, dont le coût reste modeste au regard du chantier, devrait précéder tout chiffrage sérieux.", en: "A plot in marshy ground may require special foundations that add twenty to thirty per cent to the structural work. This is why a soil survey, modest in cost relative to the build, should precede any serious estimate." },
  "news.a4.p3": { fr: "Le raccordement à l'eau et à l'électricité constitue la deuxième surprise fréquente. Sur les parcelles éloignées des réseaux existants, l'extension est à la charge du propriétaire et se compte parfois en millions de francs.", en: "Connecting to water and electricity is the second common surprise. On plots far from existing networks, the extension is the owner's responsibility and can run into millions of francs." },
  "news.a4.p4": { fr: "Notre recommandation est simple : bâtissez votre budget en trois blocs distincts, gros œuvre, second œuvre et raccordements, puis ajoutez une réserve de dix pour cent. Un chantier sans réserve est un chantier qui s'arrête.", en: "Our advice is straightforward: build your budget in three separate blocks — structure, fit-out and connections — then add a ten per cent reserve. A build with no reserve is a build that stops." },
  "news.a5.date": { fr: "24 Juin 2026", en: "24 June 2026" },
  "news.a5.title": { fr: "Premier achat immobilier : les six étapes d'un parcours serein", en: "First property purchase: six steps to a calm process" },
  "news.a5.p1": { fr: "Un premier achat se joue autant sur la méthode que sur le budget. Commencer par définir sa capacité réelle de financement, avant même de visiter, évite de s'attacher à un bien hors de portée.", en: "A first purchase depends as much on method as on budget. Establishing your real financing capacity before viewing anything prevents attachment to a property beyond reach." },
  "news.a5.p2": { fr: "Vient ensuite la sélection, où le nombre de visites compte moins que leur qualité. Trois visites bien préparées, avec une grille de critères écrite, valent mieux que quinze visites impulsives.", en: "Then comes selection, where the number of viewings matters less than their quality. Three well-prepared viewings with a written checklist beat fifteen impulsive ones." },
  "news.a5.p3": { fr: "La vérification juridique constitue la troisième étape, et la plus déterminante. Titre, bornage, absence de litige et conformité des constructions doivent être établis avant toute avance de fonds.", en: "Legal verification is the third and most decisive step. Title, boundary markers, absence of dispute and building compliance must all be established before any money changes hands." },
  "news.a5.p4": { fr: "Restent la négociation, le montage financier et la signature notariée. Comptez trois à six mois entre le coup de cœur et la remise des clés : un délai plus court doit éveiller votre vigilance plutôt que votre enthousiasme.", en: "Negotiation, financing and the notarial signature follow. Allow three to six months between falling for a property and receiving the keys: a shorter timeline should raise caution rather than enthusiasm." },
  "news.a6.date": { fr: "10 Juin 2026", en: "10 June 2026" },
  "news.a6.title": { fr: "Copropriété : ce que couvrent réellement vos charges", en: "Co-ownership: what your service charges actually cover" },
  "news.a6.p1": { fr: "Dans une copropriété, la charge mensuelle recouvre des postes très différents que peu de propriétaires distinguent. La confusion entre charges courantes et provisions pour travaux explique une bonne part des tensions en assemblée.", en: "In a co-owned building, the monthly charge covers very different items that few owners distinguish. Confusion between running costs and works provisions explains much of the tension at general meetings." },
  "news.a6.p2": { fr: "Les charges courantes financent le quotidien : gardiennage, entretien des parties communes, éclairage, ascenseur, groupe électrogène, évacuation des déchets. Elles se répartissent selon les tantièmes attribués à chaque lot.", en: "Running costs fund daily operations: security, common-area maintenance, lighting, lifts, the backup generator, waste removal. They are shared according to each unit's ownership share." },
  "news.a6.p3": { fr: "Les provisions pour travaux, elles, anticipent les gros postes : réfection d'étanchéité, ravalement, remplacement d'équipements. Une copropriété sans provision constituée devra appeler des fonds en urgence, souvent au plus mauvais moment.", en: "Works provisions, by contrast, anticipate major items: waterproofing, façade renovation, equipment replacement. A building with no accumulated provision will have to call for emergency funds, usually at the worst moment." },
  "news.a6.p4": { fr: "Avant d'acheter en copropriété, demandez systématiquement les trois derniers procès-verbaux d'assemblée et l'état des impayés. Ces deux documents en disent plus long sur l'immeuble que n'importe quelle visite.", en: "Before buying into a co-ownership, always ask for the last three meeting minutes and the arrears statement. Those two documents say more about a building than any viewing." },
  "news.a7.date": { fr: "27 Mai 2026", en: "27 May 2026" },
  "news.a7.title": { fr: "Vendre son bien : cinq erreurs qui coûtent des mois", en: "Selling your property: five mistakes that cost months" },
  "news.a7.p1": { fr: "La première erreur consiste à fixer un prix d'après ses espérances plutôt que d'après les transactions comparables du quartier. Un bien surévalué se démode sur le marché et finit par se vendre en dessous de sa valeur réelle.", en: "The first mistake is setting a price on hope rather than on comparable local transactions. An overpriced property goes stale on the market and eventually sells below its real value." },
  "news.a7.p2": { fr: "La deuxième tient au dossier. Un vendeur qui ne peut produire son titre, son bornage et ses quittances au premier rendez-vous perd la confiance de l'acquéreur, et souvent l'acquéreur lui-même.", en: "The second concerns paperwork. A seller who cannot produce title, boundary survey and receipts at the first meeting loses the buyer's trust, and often the buyer as well." },
  "news.a7.p3": { fr: "La troisième concerne la présentation. Des photographies sombres ou un bien encombré font perdre plus de valeur que n'importe quelle négociation. Quelques heures de préparation changent la perception d'un logement.", en: "The third is presentation. Dark photographs or a cluttered property destroy more value than any negotiation. A few hours of preparation change how a home is perceived." },
  "news.a7.p4": { fr: "Restent la dispersion des mandats, qui brouille le message et décrédibilise le prix, et l'absence de disponibilité pour les visites. Un bien qu'on ne peut visiter qu'un samedi sur deux met mécaniquement deux fois plus de temps à trouver preneur.", en: "Then come scattered mandates, which blur the message and undermine the asking price, and poor availability for viewings. A property that can only be seen every other Saturday takes twice as long to sell." },
  "news.a8.date": { fr: "13 Mai 2026", en: "13 May 2026" },
  "news.a8.title": { fr: "Bingerville, Songon, Abatta : la nouvelle couronne d'Abidjan", en: "Bingerville, Songon, Abatta: Abidjan's new outer ring" },
  "news.a8.p1": { fr: "La saturation de Cocody et du Plateau déplace progressivement la demande vers une deuxième couronne. Bingerville, Songon et Abatta concentrent aujourd'hui une part croissante des projets résidentiels du district.", en: "Saturation in Cocody and Plateau is gradually pushing demand towards a second ring. Bingerville, Songon and Abatta now account for a growing share of the district's residential projects." },
  "news.a8.p2": { fr: "Cette bascule tient d'abord au foncier. Les surfaces y restent disponibles à des niveaux de prix sans commune mesure avec le centre, ce qui permet des programmes avec jardin, stationnement et espaces communs.", en: "The shift is driven first by land. Plots remain available there at prices bearing no comparison with the centre, allowing developments with gardens, parking and shared spaces." },
  "news.a8.p3": { fr: "L'amélioration des axes routiers a fait le reste. Un trajet domicile-travail redevenu supportable transforme une commune périphérique en alternative crédible pour les familles.", en: "Improved road links did the rest. A commute that has become bearable again turns an outlying district into a credible option for families." },
  "news.a8.p4": { fr: "La vigilance porte sur deux points : la régularité des titres, plus inégale qu'en zone urbaine constituée, et la réalité des raccordements. Un lotissement annoncé viabilisé ne l'est pas toujours au moment de la vente.", en: "Two points warrant caution: title regularity, more uneven than in established urban areas, and the reality of utility connections. A subdivision advertised as serviced is not always so at the time of sale." },
  "news.a9.date": { fr: "29 Avril 2026", en: "29 April 2026" },
  "news.a9.title": { fr: "Le bornage : l'étape dont il ne faut jamais faire l'économie", en: "Boundary survey: the step never worth skipping" },
  "news.a9.p1": { fr: "Le bornage établit matériellement les limites d'une parcelle et les fait correspondre au plan cadastral. C'est une opération technique, réalisée par un géomètre agréé, dont dépend la sécurité de tout le reste.", en: "A boundary survey physically establishes a plot's limits and matches them to the cadastral plan. It is a technical operation, carried out by a licensed surveyor, on which everything else depends." },
  "news.a9.p2": { fr: "Son absence explique une part importante des litiges fonciers portés devant les tribunaux d'Abidjan. Deux voisins peuvent occuper de bonne foi une même bande de terrain pendant des années avant que le conflit n'éclate.", en: "Its absence explains a significant share of the land disputes brought before Abidjan's courts. Two neighbours can occupy the same strip of land in good faith for years before conflict erupts." },
  "news.a9.p3": { fr: "Un écart entre la superficie annoncée et la superficie bornée doit toujours être élucidé avant la signature. Il révèle parfois une erreur documentaire bénigne, parfois un empiètement ancien qu'il faudra régulariser.", en: "Any gap between the advertised area and the surveyed area must be resolved before signing. Sometimes it reveals a harmless documentary error, sometimes a long-standing encroachment that will need regularising." },
  "news.a9.p4": { fr: "Le coût d'un bornage reste sans commune mesure avec celui d'une procédure judiciaire. Nous conseillons systématiquement de le faire réaliser, ou de le faire vérifier, avant tout versement significatif.", en: "The cost of a survey bears no comparison with that of court proceedings. We systematically advise having one carried out, or verified, before any significant payment." },
  "news.a10.date": { fr: "15 Avril 2026", en: "15 April 2026" },
  "news.a10.title": { fr: "Bail d'habitation : les clauses qu'on néglige et qu'on regrette", en: "Residential leases: the clauses people skip and regret" },
  "news.a10.p1": { fr: "Un bail bien rédigé règle à l'avance ce qui, autrement, se discutera dans la tension. La répartition des réparations entre bailleur et locataire figure en tête des sujets à cadrer précisément.", en: "A well-drafted lease settles in advance what would otherwise be argued under pressure. How repairs are split between landlord and tenant heads the list of matters to frame precisely." },
  "news.a10.p2": { fr: "L'état des lieux d'entrée reste la pièce la plus sous-estimée du dossier. Sans description détaillée et photographies datées, aucune retenue sur la caution ne pourra être justifiée à la sortie.", en: "The inventory at move-in remains the most underrated document in the file. Without a detailed description and dated photographs, no deduction from the deposit can be justified at move-out." },
  "news.a10.p3": { fr: "Les modalités de révision du loyer et le sort des travaux d'amélioration réalisés par le locataire méritent également une clause explicite. Leur silence est une source classique de désaccord en fin de bail.", en: "Rent review terms and the treatment of tenant improvements also deserve an explicit clause. Silence on these is a classic source of disagreement at the end of a lease." },
  "news.a10.p4": { fr: "Enfin, prévoyez la procédure applicable en cas d'impayé, avec des délais clairs. Un bailleur qui laisse s'accumuler trois mois d'arriérés sans réaction formelle affaiblit sa propre position.", en: "Finally, set out the procedure for unpaid rent, with clear deadlines. A landlord who lets three months of arrears accumulate without formal action weakens their own position." },
  "news.a11.date": { fr: "25 Mars 2026", en: "25 March 2026" },
  "news.a11.title": { fr: "Suivi de chantier : les points de contrôle, phase par phase", en: "Site supervision: checkpoints, phase by phase" },
  "news.a11.p1": { fr: "Un chantier se contrôle par étapes, et chaque étape possède ses points de non-retour. Passer à la phase suivante sans validation revient à enfouir un défaut qu'il coûtera cher de corriger.", en: "A building site is controlled in stages, and each stage has its point of no return. Moving to the next phase without sign-off buries a defect that will be expensive to correct." },
  "news.a11.p2": { fr: "Aux fondations, on vérifie l'implantation, la profondeur et le ferraillage avant tout coulage. Une fois le béton pris, la reprise devient une démolition partielle.", en: "At foundation level, check setting-out, depth and reinforcement before any pour. Once concrete has set, remedial work becomes partial demolition." },
  "news.a11.p3": { fr: "À l'élévation, l'attention porte sur l'aplomb des murs, la qualité des chaînages et le respect des réservations pour les réseaux. Des gaines oubliées obligent à rouvrir des cloisons neuves.", en: "During elevation, attention goes to wall plumb, the quality of ring beams and compliance with service openings. Forgotten conduits mean reopening new partitions." },
  "news.a11.p4": { fr: "En finition, la réception se prépare avec une liste de réserves écrite, poste par poste. Le solde du marché ne devrait jamais être versé avant la levée effective de ces réserves.", en: "At finishing stage, handover is prepared with a written snag list, item by item. The final payment should never be released before those snags are actually cleared." },
  "news.a12.date": { fr: "11 Mars 2026", en: "11 March 2026" },
  "news.a12.title": { fr: "Financer son achat : ce que regardent les banques ivoiriennes", en: "Financing a purchase: what Ivorian banks look at" },
  "news.a12.p1": { fr: "Un dossier de crédit immobilier se juge sur quelques critères stables, que les établissements de la place appliquent avec des nuances mais une logique commune. La régularité des revenus prime sur leur montant.", en: "A mortgage application is judged on a few stable criteria, applied by local institutions with variations but a shared logic. Income regularity matters more than income size." },
  "news.a12.p2": { fr: "Le taux d'endettement reste le premier filtre. Au-delà d'un tiers des revenus nets consacrés au remboursement, l'accord devient difficile à obtenir, quelle que soit la qualité du bien financé.", en: "The debt-to-income ratio remains the first filter. Beyond a third of net income devoted to repayment, approval becomes hard to obtain regardless of the property's quality." },
  "news.a12.p3": { fr: "L'apport personnel pèse ensuite lourdement, tant sur la décision que sur les conditions obtenues. Un apport couvrant les frais annexes et une part du prix améliore sensiblement le dossier.", en: "The down payment then weighs heavily, on both the decision and the terms secured. A contribution covering incidental costs plus part of the price markedly improves the file." },
  "news.a12.p4": { fr: "La qualité juridique du bien compte enfin autant que le profil de l'emprunteur. Une banque prête sur une garantie : un titre incontestable et un bornage à jour accélèrent nettement l'instruction.", en: "Finally, the property's legal standing counts as much as the borrower's profile. A bank lends against security: an unchallengeable title and an up-to-date survey noticeably speed up processing." },
  "news.page.lede": { fr: "Foncier, marché, gestion locative : nos conseils d'experts pour réussir vos projets immobiliers à Abidjan.", en: "Land, market, rental management: our expert advice to succeed in your real estate projects in Abidjan." },
  "news.backHome": { fr: "Retour à l'accueil", en: "Back to home" },
  "news.category.market": { fr: "Marché", en: "Market" },

  "news.a1.date": { fr: "12 Août 2026", en: "August 12, 2026" },
  "news.a1.title": { fr: "ACD, titre foncier : comment sécuriser l'achat d'un terrain à Abidjan", en: "ACD, land title: how to secure a land purchase in Abidjan" },
  "news.a1.p1": { fr: "Acheter un terrain à Abidjan reste une étape enthousiasmante, mais aussi l'une des plus sensibles d'un projet immobilier. Le marché foncier ivoirien a connu de nombreux litiges liés à des ventes irrégulières, ce qui rend la vérification juridique absolument indispensable avant toute signature.", en: "Buying land in Abidjan is an exciting step, but also one of the most sensitive parts of a real estate project. The Ivorian land market has seen many disputes linked to irregular sales, which makes legal verification absolutely essential before signing anything." },
  "news.a1.p2": { fr: "Le premier réflexe consiste à exiger un Arrêté de Concession Définitive (ACD) ou, à défaut, un Arrêté de Concession Provisoire en cours de régularisation. Ce document, délivré par l'administration, atteste que le terrain a suivi la procédure légale d'attribution et qu'il ne fait pas l'objet d'un litige ou d'une double vente.", en: "The first thing to check is a Definitive Concession Order (ACD) or, failing that, a Provisional Concession Order being regularised. This document, issued by the administration, confirms the land followed the legal allocation process and is not subject to a dispute or double sale." },
  "news.a1.p3": { fr: "Il convient ensuite de vérifier le bornage : un terrain doit être physiquement délimité par des bornes officielles correspondant exactement au plan cadastral. Une différence entre la superficie annoncée et la superficie réelle est un signal d'alerte fréquent.", en: "Next, check the boundary survey: a plot must be physically marked with official boundary stones matching the cadastral plan exactly. A mismatch between the advertised and actual surface area is a common warning sign." },
  "news.a1.p4": { fr: "Chez SCI4K, chaque terrain proposé à nos clients fait l'objet d'un audit complet — titre foncier, bornage, viabilisation et absence de litige — avant toute mise en vente. Cette rigueur nous permet de garantir des transactions sereines, du premier contact jusqu'à la mutation notariée.", en: "At SCI4K, every plot we offer clients undergoes a full audit — land title, boundary survey, utilities and absence of disputes — before it's ever listed. This rigour lets us guarantee smooth transactions, from first contact through to the notarised transfer." },

  "news.a2.date": { fr: "3 Août 2026", en: "August 3, 2026" },
  "news.a2.title": { fr: "Marché immobilier à Abidjan : les tendances à suivre en 2026", en: "Real estate market in Abidjan: trends to watch in 2026" },
  "news.a2.p1": { fr: "Porté par une démographie dynamique et des investissements publics soutenus, le marché immobilier abidjanais poursuit sa croissance. Les quartiers de Cocody, Riviera et Bingerville continuent d'attirer particuliers et investisseurs institutionnels en quête de biens de standing.", en: "Driven by dynamic demographics and sustained public investment, Abidjan's real estate market keeps growing. Cocody, Riviera and Bingerville continue to attract individuals and institutional investors looking for upscale properties." },
  "news.a2.p2": { fr: "On observe une montée en puissance des résidences sécurisées et des copropriétés modernes, qui répondent à une demande croissante de confort et de tranquillité. Parallèlement, le segment locatif reste très actif, porté par une population active en constante augmentation dans l'agglomération.", en: "We're seeing a rise in secure residences and modern co-ownerships, meeting growing demand for comfort and peace of mind. Meanwhile, the rental segment stays very active, driven by a steadily growing working population across the metro area." },
  "news.a2.p3": { fr: "Le foncier viabilisé en périphérie — notamment vers Bingerville et Abatta — devient une alternative de plus en plus prisée pour les primo-accédants, offrant des prix plus accessibles tout en restant à proximité des grands axes routiers.", en: "Serviced land on the outskirts — particularly toward Bingerville and Abatta — is becoming an increasingly popular option for first-time buyers, offering more accessible prices while staying close to major roads." },
  "news.a2.p4": { fr: "Pour les investisseurs, la clé reste la même : privilégier les biens disposant d'un dossier juridique irréprochable et s'entourer de professionnels capables d'évaluer le potentiel réel d'un quartier, au-delà des effets de mode.", en: "For investors, the key remains the same: prioritise properties with a flawless legal file and work with professionals able to assess a neighbourhood's real potential, beyond passing trends." },

  "news.a3.date": { fr: "21 Juillet 2026", en: "July 21, 2026" },
  "news.a3.title": { fr: "5 conseils pour une gestion locative sereine à Abidjan", en: "5 tips for stress-free rental management in Abidjan" },
  "news.a3.p1": { fr: "Louer un bien immobilier peut rapidement devenir chronophage sans une organisation rigoureuse. Voici cinq principes que nous appliquons au quotidien pour nos clients propriétaires.", en: "Renting out a property can quickly become time-consuming without rigorous organisation. Here are five principles we apply every day for our landlord clients." },
  "news.a3.p2": { fr: "1. Sélectionner rigoureusement ses locataires : vérification des revenus, des garants et de l'historique locatif permet d'éviter la grande majorité des impayés. 2. Rédiger un bail complet et conforme, précisant clairement les obligations de chaque partie.", en: "1. Screen tenants rigorously: checking income, guarantors and rental history prevents the vast majority of unpaid rent issues. 2. Draft a complete, compliant lease that clearly states each party's obligations." },
  "news.a3.p3": { fr: "3. Réaliser un état des lieux détaillé, photos à l'appui, à l'entrée comme à la sortie du locataire. 4. Mettre en place un suivi comptable transparent, avec reversement ponctuel des loyers et justificatifs clairs pour le propriétaire.", en: "3. Carry out a detailed move-in and move-out inspection, backed by photos. 4. Set up transparent accounting, with timely rent payouts and clear statements for the owner." },
  "news.a3.p4": { fr: "5. Anticiper l'entretien courant du bien plutôt que de subir les urgences : un patrimoine bien entretenu conserve sa valeur et fidélise les bons locataires. C'est précisément la promesse de notre service de gestion locative chez SCI4K.", en: "5. Stay ahead of routine maintenance rather than reacting to emergencies: a well-maintained property keeps its value and retains good tenants. That's exactly the promise of SCI4K's rental management service." },

  /* ===== LEGAL NOTICE ===== */
  "legal.title": { fr: "Mentions légales", en: "Legal Notice" },
  "legal.updated": { fr: "Dernière mise à jour : Août 2026", en: "Last updated: August 2026" },
  "legal.s1.title": { fr: "1. Éditeur du site", en: "1. Site publisher" },
  "legal.s1.text": { fr: "Le présent site est édité par <strong>SCI4K</strong>, Société Civile Immobilière, dont le siège social est situé à Cocody, Cité des Arts, Résidence Paon, 3ème étage, Abidjan, Côte d'Ivoire.<br>Numéro RCCM : <span class=\"legal-placeholder\">[à compléter]</span> — Numéro de Compte Contribuable : <span class=\"legal-placeholder\">[à compléter]</span><br>Téléphone : +225 07 06 16 50 29 — Email : contact@sci4k.com (secondaire : sci4k@sci4k.com)", en: "This site is published by <strong>SCI4K</strong>, a Real Estate Company (SCI) headquartered in Cocody, Cité des Arts, Résidence Paon, 3rd floor, Abidjan, Côte d'Ivoire.<br>RCCM number: <span class=\"legal-placeholder\">[to be completed]</span> — Taxpayer account number: <span class=\"legal-placeholder\">[to be completed]</span><br>Phone: +225 07 06 16 50 29 — Email: contact@sci4k.com (secondary: sci4k@sci4k.com)" },
  "legal.s2.title": { fr: "2. Directeur de la publication", en: "2. Publication director" },
  "legal.s2.text": { fr: "Le directeur de la publication est <strong>Monsieur Tiemoko Max Regis</strong>, Directeur Général de SCI4K. Pour toute question relative au contenu du site, vous pouvez nous contacter à l'adresse <a href=\"mailto:contact@sci4k.com\">contact@sci4k.com</a>.", en: "The publication director is <strong>Mr Tiemoko Max Regis</strong>, SCI4K's Chief Executive Officer. For any question regarding the site's content, you can contact us at <a href=\"mailto:contact@sci4k.com\">contact@sci4k.com</a>." },
  "legal.s3.title": { fr: "3. Hébergement", en: "3. Hosting" },
  "legal.s3.text": { fr: "Le site est hébergé par : <span class=\"legal-placeholder\">[nom de l'hébergeur à compléter]</span>, <span class=\"legal-placeholder\">[adresse à compléter]</span>.", en: "This site is hosted by: <span class=\"legal-placeholder\">[host name to be completed]</span>, <span class=\"legal-placeholder\">[address to be completed]</span>." },
  "legal.s4.title": { fr: "4. Propriété intellectuelle", en: "4. Intellectual property" },
  "legal.s4.text": { fr: "L'ensemble des contenus présents sur ce site (textes, images, logos, graphismes) est la propriété exclusive de SCI4K, sauf mention contraire, et est protégé par les lois relatives à la propriété intellectuelle en vigueur en Côte d'Ivoire. Toute reproduction, représentation ou diffusion, totale ou partielle, sans autorisation préalable, est interdite.", en: "All content on this site (text, images, logos, graphics) is the exclusive property of SCI4K, unless otherwise stated, and is protected by intellectual property laws in force in Côte d'Ivoire. Any reproduction, representation or distribution, in whole or in part, without prior authorisation, is prohibited." },
  "legal.s5.title": { fr: "5. Liens hypertextes", en: "5. Hyperlinks" },
  "legal.s5.text": { fr: "Le site SCI4K peut contenir des liens vers d'autres sites (partenaires, réseaux sociaux). SCI4K décline toute responsabilité quant au contenu de ces sites tiers, sur lesquels elle n'exerce aucun contrôle.", en: "The SCI4K site may contain links to other sites (partners, social media). SCI4K disclaims all responsibility for the content of these third-party sites, over which it has no control." },
  "legal.s6.title": { fr: "6. Droit applicable", en: "6. Applicable law" },
  "legal.s6.text": { fr: "Le présent site et les présentes mentions légales sont soumis au droit ivoirien. En cas de litige, les tribunaux d'Abidjan seront seuls compétents.", en: "This site and this legal notice are governed by Ivorian law. In the event of a dispute, the courts of Abidjan shall have sole jurisdiction." },
  "legal.s7.title": { fr: "7. Données personnelles", en: "7. Personal data" },
  "legal.s7.text": { fr: "Les informations relatives à la collecte et au traitement de vos données personnelles sont détaillées dans notre <a href=\"politique-confidentialite.html\" data-i18n=\"legal.privacyLink\">Politique de confidentialité</a>.", en: "Information regarding the collection and processing of your personal data is detailed in our <a href=\"politique-confidentialite.html\" data-i18n=\"legal.privacyLink\">Privacy Policy</a>." },
  "legal.privacyLink": { fr: "Politique de confidentialité", en: "Privacy Policy" },

  /* ===== PRIVACY POLICY ===== */
  "privacy.title": { fr: "Politique de confidentialité", en: "Privacy Policy" },
  "privacy.s1.title": { fr: "1. Qui sommes-nous", en: "1. Who we are" },
  "privacy.s1.text": { fr: "SCI4K, Société Civile Immobilière basée à Cocody, Cité des Arts, Abidjan, Côte d'Ivoire, est responsable du traitement des données personnelles collectées via ce site.", en: "SCI4K, a Real Estate Company based in Cocody, Cité des Arts, Abidjan, Côte d'Ivoire, is responsible for processing the personal data collected via this site." },
  "privacy.s2.title": { fr: "2. Données que nous collectons", en: "2. Data we collect" },
  "privacy.s2.text": { fr: "Lorsque vous utilisez nos formulaires (contact, question FAQ, newsletter), nous collectons les informations que vous nous transmettez volontairement :", en: "When you use our forms (contact, FAQ question, newsletter), we collect the information you voluntarily provide to us:" },
  "privacy.table.data": { fr: "Donnée", en: "Data" },
  "privacy.table.purpose": { fr: "Finalité", en: "Purpose" },
  "privacy.table.d1": { fr: "Nom complet", en: "Full name" },
  "privacy.table.p1": { fr: "Identifier votre demande et vous répondre", en: "Identify your request and reply to you" },
  "privacy.table.d2": { fr: "Email / Téléphone", en: "Email / Phone" },
  "privacy.table.p2": { fr: "Vous recontacter au sujet de votre demande", en: "Get back to you about your request" },
  "privacy.table.d3": { fr: "Message / Question", en: "Message / Question" },
  "privacy.table.p3": { fr: "Traiter votre demande spécifique", en: "Handle your specific request" },
  "privacy.table.d4": { fr: "Préférences (thème, langue)", en: "Preferences (theme, language)" },
  "privacy.table.p4": { fr: "Mémorisées localement dans votre navigateur (localStorage), jamais transmises à SCI4K", en: "Stored locally in your browser (localStorage), never transmitted to SCI4K" },
  "privacy.s3.title": { fr: "3. Cookies et stockage local", en: "3. Cookies and local storage" },
  "privacy.s3.text": { fr: "Ce site n'utilise pas de cookies de suivi publicitaire. Il utilise uniquement le stockage local (localStorage) de votre navigateur pour mémoriser votre choix de thème (clair/sombre) et de langue (FR/EN) d'une visite à l'autre. Ces informations restent sur votre appareil et ne sont jamais envoyées à nos serveurs.", en: "This site does not use advertising tracking cookies. It only uses your browser's local storage (localStorage) to remember your theme (light/dark) and language (FR/EN) choice between visits. This information stays on your device and is never sent to our servers." },
  "privacy.s4.title": { fr: "4. Durée de conservation", en: "4. Retention period" },
  "privacy.s4.text": { fr: "Les données transmises via nos formulaires sont conservées le temps nécessaire au traitement de votre demande, puis archivées ou supprimées conformément à nos obligations légales et commerciales.", en: "Data submitted via our forms is kept for as long as necessary to process your request, then archived or deleted in accordance with our legal and business obligations." },
  "privacy.s5.title": { fr: "5. Partage des données", en: "5. Data sharing" },
  "privacy.s5.text": { fr: "SCI4K ne vend ni ne loue vos données personnelles à des tiers. Vos informations peuvent être partagées avec nos partenaires notariés ou institutionnels uniquement dans le cadre strict de la réalisation d'une transaction que vous avez initiée avec nous.", en: "SCI4K neither sells nor rents your personal data to third parties. Your information may be shared with our notarial or institutional partners solely within the strict context of completing a transaction you have initiated with us." },
  "privacy.s6.title": { fr: "6. Vos droits", en: "6. Your rights" },
  "privacy.s6.text": { fr: "Conformément à la réglementation applicable en Côte d'Ivoire, vous disposez d'un droit d'accès, de rectification et de suppression de vos données personnelles. Pour exercer ces droits, contactez-nous à l'adresse ci-dessous.", en: "In accordance with applicable regulations in Côte d'Ivoire, you have the right to access, rectify and delete your personal data. To exercise these rights, contact us at the address below." },
  "privacy.s7.title": { fr: "7. Contact", en: "7. Contact" },
  "privacy.s7.text": { fr: "Pour toute question relative à cette politique de confidentialité ou à vos données personnelles, contactez-nous à <a href=\"mailto:contact@sci4k.com\">contact@sci4k.com</a> ou au +225 07 06 16 50 29.", en: "For any question regarding this privacy policy or your personal data, contact us at <a href=\"mailto:contact@sci4k.com\">contact@sci4k.com</a> or on +225 07 06 16 50 29." }
};

/* ---- Catalogue de biens (source unique) ---- */
/* SCI4K — Source unique du catalogue de biens.
   Pour ajouter / retirer / modifier un bien, éditez uniquement ce fichier :
   les fiches, les filtres et la fiche détaillée (modale) sont générés automatiquement à partir de ce tableau. */
window.SCI4K_PROPERTIES = [
  {
    id: 0, mode: 'location', type: 'villa', loc: 'cocody', roomsCount: 5, surface: 's3', category: 'location villas',
    bgGrad: 'linear-gradient(150deg,#1d3557,#2c5480)',
    svg: '<path d="M20 90 60 55l40 35" stroke="#e3c087" stroke-width="2.5"/><rect x="35" y="90" width="50" height="35" stroke="#e3c087" stroke-width="2.5"/><rect x="52" y="102" width="16" height="23" stroke="#e3c087" stroke-width="2"/><rect x="100" y="70" width="70" height="55" stroke="#e3c087" stroke-width="2.5"/><line x1="100" y1="90" x2="170" y2="90" stroke="#e3c087" stroke-width="2"/>',
    meta: [{ value: 4, unitKey: 'biens.unit.rooms' }, { value: 3, unitKey: 'biens.unit.baths' }, { value: 310, unit: 'm²' }],
    contactLink: 'contact.html?bien=Villa%20Les%20Palmiers',
    fr: { title: "Villa Les Palmiers", type: "Villa moderne · F5", loc: "📍 Riviera Golf, Cocody, Abidjan", surface: "310 m²", rooms: "5 Pièces (4 Chambres + 1 Salon)", legal: "Titre Foncier ACD Disponible", desc: "Magnifique villa d'architecte neuve située au cœur du quartier résidentiel et sécurisé du Riviera Golf. Offrant un cadre de vie luxueux et lumineux, cette propriété comprend 4 grandes chambres autonomes avec dressing, un vaste séjour double hauteur donnant sur la terrasse et le jardin paysagé, une cuisine moderne équipée, ainsi qu'une dépendance pour le personnel. Sécurité 24h/24 et groupe électrogène de secours.", features: ["Piscine privative", "Climatisation centralisée", "Garage 2 véhicules", "Jardin paysagé", "Groupe Électrogène", "Sécurité 24/7", "Surveillance vidéo"] },
    en: { title: "Villa Les Palmiers", type: "Modern villa · F5", loc: "📍 Riviera Golf, Cocody, Abidjan", surface: "310 m²", rooms: "5 rooms (4 bedrooms + 1 living room)", legal: "ACD land title available", desc: "Beautiful newly-built architect villa set in the secure, upscale Riviera Golf neighbourhood. This bright, luxurious property includes 4 large en-suite bedrooms with dressing rooms, a vast double-height living room opening onto the terrace and landscaped garden, a fully-equipped modern kitchen, and staff quarters. 24/7 security and backup generator.", features: ["Private pool", "Central air conditioning", "2-car garage", "Landscaped garden", "Backup generator", "24/7 security", "Video surveillance"] }
  },
  {
    id: 1, mode: 'vente', type: 'appartement', loc: 'cocody', roomsCount: 3, surface: 's1', category: 'vente appartements',
    bgGrad: 'linear-gradient(150deg,#233d5e,#3a6a94)',
    svg: '<rect x="30" y="45" width="140" height="80" stroke="#e3c087" stroke-width="2.5"/><line x1="30" y1="70" x2="170" y2="70" stroke="#e3c087" stroke-width="2"/><line x1="30" y1="95" x2="170" y2="95" stroke="#e3c087" stroke-width="2"/><line x1="75" y1="45" x2="75" y2="125" stroke="#e3c087" stroke-width="2"/><line x1="120" y1="45" x2="120" y2="125" stroke="#e3c087" stroke-width="2"/>',
    meta: [{ value: 2, unitKey: 'biens.unit.rooms' }, { value: 2, unitKey: 'biens.unit.baths' }, { value: 95, unit: 'm²' }],
    contactLink: 'contact.html?bien=Résidence%20Alba',
    fr: { title: "Résidence Alba", type: "Appartement de standing · F3", loc: "📍 Cocody Angré 8ème Tranche, Abidjan", surface: "95 m²", rooms: "3 Pièces (2 Chambres + Salon)", legal: "ACD Notarié & Découpage individuel", desc: "Superbe appartement F3 situé au 3ème étage d'un immeuble récent avec ascenseur à Angré. Finitions haut de gamme, grand balcon avec vue dégagée, cuisine américaine aménagée et deux suites parentales climatisées. Emplacement stratégique à proximité immédiate des commerces et écoles.", features: ["Ascenseur principal", "Balcon vue dégagée", "Cuisine américaine équipée", "Parking sous-sol", "Gardiennage 24/7", "Fibre optique"] },
    en: { title: "Résidence Alba", type: "Upscale apartment · F3", loc: "📍 Cocody Angré, 8th section, Abidjan", surface: "95 m²", rooms: "3 rooms (2 bedrooms + living room)", legal: "Notarised ACD, individually titled", desc: "Superb F3 apartment on the 3rd floor of a recent building with lift in Angré. High-end finishes, large balcony with open views, fitted open kitchen and two air-conditioned master suites. Strategic location close to shops and schools.", features: ["Main lift", "Balcony with open view", "Fully-fitted open kitchen", "Basement parking", "24/7 security", "Fibre internet"] }
  },
  {
    id: 2, mode: 'vente', type: 'terrain', loc: 'bingerville', roomsCount: 1, surface: 's4', category: 'vente terrains',
    bgGrad: 'linear-gradient(150deg,#16304f,#204268)',
    svg: '<path d="M25 115 100 30l75 85" stroke="#e3c087" stroke-width="2.5" stroke-linejoin="round"/><line x1="25" y1="115" x2="175" y2="115" stroke="#e3c087" stroke-width="2.5"/>',
    meta: [{ value: 736, unit: 'm²' }, { value: 'ACD', unitKey: 'biens.unit.available' }],
    contactLink: 'contact.html?bien=Lot%20Bonoua',
    fr: { title: "Lot Bonoua ACD", type: "Terrain viabilisé résidentiel", loc: "📍 Bingerville, Abidjan", surface: "736 m²", rooms: "Terrain Nu Viabilisé", legal: "Arrêté de Concession Définitive (ACD)", desc: "Rarissime parcelle de 736 m² située dans une zone résidentielle en plein essor à Bingerville. Terrain parfaitement plat, déjà borné et viabilisé (accès eau, électricité, voirie goudronnée). Dossier juridique complet avec ACD disponible immédiatement pour mutation notariale.", features: ["Document ACD officiel", "Terrain plat prêt à bâtir", "Accès eau & électricité", "Voirie bitumée", "Zone résidentielle calme"] },
    en: { title: "Lot Bonoua ACD", type: "Serviced residential plot", loc: "📍 Bingerville, Abidjan", surface: "736 m²", rooms: "Serviced bare land", legal: "Definitive Concession Order (ACD)", desc: "Rare 736 m² plot in a fast-growing residential area of Bingerville. Perfectly flat, already surveyed and serviced (water, electricity, paved road access). Complete legal file with ACD available immediately for notarised transfer.", features: ["Official ACD document", "Flat, build-ready land", "Water & electricity access", "Paved road access", "Quiet residential area"] }
  },
  {
    id: 3, mode: 'location', type: 'villa', loc: 'marcory', roomsCount: 5, surface: 's3', category: 'location villas',
    bgGrad: 'linear-gradient(150deg,#2c5480,#4a7ba8)',
    svg: '<rect x="40" y="55" width="120" height="70" stroke="#e3c087" stroke-width="2.5"/><path d="M40 55 100 25l60 30" stroke="#e3c087" stroke-width="2.5"/><rect x="90" y="90" width="20" height="35" stroke="#e3c087" stroke-width="2"/>',
    meta: [{ value: 4, unitKey: 'biens.unit.rooms' }, { value: 2, unitKey: 'biens.unit.living' }, { value: 270, unit: 'm²' }],
    contactLink: 'contact.html?bien=Villa%20MBatto',
    fr: { title: "Villa M'Batto", type: "Maison duplex d'exception · F6", loc: "📍 Marcory Zone 4, Abidjan", surface: "270 m²", rooms: "6 Pièces (4 Chambres + 2 Salons)", legal: "ACD & Titre Foncier", desc: "Élégante maison duplex rénovée avec soin située à Marcory Zone 4. Elle propose deux vastes salons indépendants, 4 suites avec salles de bain privatives, une cuisine principale avec garde-manger, une buanderie et une cour arrière ombragée. Emplacement très recherché à 10 min de l'aéroport.", features: ["Double salon", "Cour & Terrasse couverte", "Climatisation réversible", "Garage fermé", "Cuve à eau & Surpresseur"] },
    en: { title: "Villa M'Batto", type: "Exceptional duplex house · F6", loc: "📍 Marcory Zone 4, Abidjan", surface: "270 m²", rooms: "6 rooms (4 bedrooms + 2 living rooms)", legal: "ACD & land title", desc: "Elegant, carefully renovated duplex house in Marcory Zone 4. It offers two large independent living rooms, 4 en-suite bedrooms, a main kitchen with pantry, a laundry room and a shaded backyard. Highly sought-after location 10 minutes from the airport.", features: ["Double living room", "Covered yard & terrace", "Reversible air conditioning", "Enclosed garage", "Water tank & booster pump"] }
  },
  {
    id: 4, mode: 'vente', type: 'immeuble', loc: 'abatta', roomsCount: 5, surface: 's4', category: 'vente immeuble',
    bgGrad: 'linear-gradient(150deg,#1d3557,#375f8c)',
    svg: '<rect x="35" y="40" width="130" height="85" stroke="#e3c087" stroke-width="2.5"/><rect x="55" y="60" width="30" height="25" stroke="#e3c087" stroke-width="2"/><rect x="115" y="60" width="30" height="25" stroke="#e3c087" stroke-width="2"/><rect x="85" y="95" width="30" height="30" stroke="#e3c087" stroke-width="2"/>',
    meta: [{ value: 6, unitKey: 'biens.unit.lots' }, { value: 540, unit: 'm²' }],
    contactLink: 'contact.html?bien=Résidence%20Étoile',
    fr: { title: "Résidence Étoile", type: "Immeuble de rapport commercial", loc: "📍 Abatta Bord de Lagune, Abidjan", surface: "540 m²", rooms: "6 Lots (Appartements F3 & F4)", legal: "Titre Foncier notarié complet", desc: "Immeuble R+3 neuf de 6 appartements de grand standing entièrement loués, générant un rendement locatif mensuel attractif. Construction moderne aux normes européennes, finitions durables et emplacement d'exception en bordure de lagune à Abatta.", features: ["Revenu locatif garanti", "6 Appartements occupés", "Compteurs SODECI/CIE séparés", "Caméras de surveillance", "Parking 8 places"] },
    en: { title: "Résidence Étoile", type: "Commercial income building", loc: "📍 Abatta, lagoon-front, Abidjan", surface: "540 m²", rooms: "6 units (F3 & F4 apartments)", legal: "Fully notarised land title", desc: "Brand-new 3-storey building with 6 fully-let upscale apartments, generating attractive monthly rental income. Modern construction to European standards, durable finishes and an exceptional lagoon-front location in Abatta.", features: ["Guaranteed rental income", "6 occupied apartments", "Separate SODECI/CIE meters", "CCTV surveillance", "8-space parking"] }
  },
  {
    id: 5, mode: 'location', type: 'appartement', loc: 'plateau', roomsCount: 1, surface: 's1', category: 'location appartements',
    bgGrad: 'linear-gradient(150deg,#204268,#3a6a94)',
    svg: '<rect x="55" y="35" width="90" height="95" stroke="#e3c087" stroke-width="2.5"/><line x1="55" y1="60" x2="145" y2="60" stroke="#e3c087" stroke-width="2"/><line x1="55" y1="85" x2="145" y2="85" stroke="#e3c087" stroke-width="2"/><line x1="55" y1="110" x2="145" y2="110" stroke="#e3c087" stroke-width="2"/>',
    meta: [{ value: 1, unitKey: 'biens.unit.rooms' }, { value: 1, unitKey: 'biens.unit.baths' }, { value: 45, unit: 'm²' }],
    contactLink: 'contact.html?bien=Plateau%20Loft',
    fr: { title: "Le Plateau Loft", type: "Studio meublé d'affaires", loc: "📍 Le Plateau, Abidjan", surface: "45 m²", rooms: "2 Pièces (1 Chambre + Salon)", legal: "Bail Commercial / Habitation", desc: "Loft ultra-moderne entièrement meublé et équipé situé au cœur du quartier des affaires au Plateau. Décoration épurée, espace de travail ergonomique, cuisine équipée et service de ménage inclus. Idéal pour consultants, expatriés et séjours professionnels.", features: ["Entièrement Meublé", "Service de Ménage", "Connexion Wi-Fi Haut Débit", "Climatisation", "Ascenseur & Sécurité 24/7"] },
    en: { title: "Le Plateau Loft", type: "Furnished business studio", loc: "📍 Le Plateau, Abidjan", surface: "45 m²", rooms: "2 rooms (1 bedroom + living room)", legal: "Commercial / residential lease", desc: "Ultra-modern, fully furnished and equipped loft in the heart of the Plateau business district. Minimalist decor, ergonomic workspace, equipped kitchen and cleaning service included. Ideal for consultants, expatriates and business stays.", features: ["Fully furnished", "Cleaning service", "High-speed Wi-Fi", "Air conditioning", "Lift & 24/7 security"] }
  }
];

/* ---- Moteur theme clair/sombre + langue ---- */
/* SCI4K — Dark/Light mode + FR/EN language engine (shared across all pages) */
(function () {
  var THEME_KEY = 'sci4k-theme';
  var LANG_KEY = 'sci4k-lang';

  function getTheme() { return localStorage.getItem(THEME_KEY) || 'light'; }
  function setTheme(t) {
    try { localStorage.setItem(THEME_KEY, t); } catch (e) {}
    document.documentElement.setAttribute('data-theme', t);
  }

  function getLang() { return localStorage.getItem(LANG_KEY) || 'fr'; }

  function applyLang(lang) {
    document.documentElement.setAttribute('lang', lang);
    var dict = window.SCI4K_I18N || {};

    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      var entry = dict[el.getAttribute('data-i18n')];
      if (entry && entry[lang] !== undefined) el.textContent = entry[lang];
    });
    document.querySelectorAll('[data-i18n-html]').forEach(function (el) {
      var entry = dict[el.getAttribute('data-i18n-html')];
      if (entry && entry[lang] !== undefined) el.innerHTML = entry[lang];
    });
    document.querySelectorAll('[data-i18n-ph]').forEach(function (el) {
      var entry = dict[el.getAttribute('data-i18n-ph')];
      if (entry && entry[lang] !== undefined) el.setAttribute('placeholder', entry[lang]);
    });
    document.querySelectorAll('[data-i18n-aria]').forEach(function (el) {
      var entry = dict[el.getAttribute('data-i18n-aria')];
      if (entry && entry[lang] !== undefined) el.setAttribute('aria-label', entry[lang]);
    });

    document.querySelectorAll('.lang-toggle').forEach(function (b) {
      b.textContent = lang === 'fr' ? 'EN' : 'FR';
    });

    document.dispatchEvent(new CustomEvent('sci4k:langchange', { detail: { lang: lang } }));
  }

  function setLang(lang) {
    try { localStorage.setItem(LANG_KEY, lang); } catch (e) {}
    applyLang(lang);
  }

  // Lazy-load heavy CSS background-image sections: <div data-bg-lazy="url"></div>
  function initLazyBackgrounds() {
    var targets = document.querySelectorAll('[data-bg-lazy]');
    if (!targets.length) return;
    if (!('IntersectionObserver' in window)) {
      targets.forEach(function (el) { el.style.backgroundImage = "url('" + el.getAttribute('data-bg-lazy') + "')"; });
      return;
    }
    var bgObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          var el = entry.target;
          el.style.backgroundImage = "url('" + el.getAttribute('data-bg-lazy') + "')";
          bgObserver.unobserve(el);
        }
      });
    }, { rootMargin: '200px 0px' });
    targets.forEach(function (el) { bgObserver.observe(el); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    setTheme(getTheme());
    applyLang(getLang());
    initLazyBackgrounds();

    document.querySelectorAll('.theme-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setTheme(getTheme() === 'dark' ? 'light' : 'dark');
      });
    });
    document.querySelectorAll('.lang-toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        setLang(getLang() === 'fr' ? 'en' : 'fr');
      });
    });
  });

  window.SCI4K_THEME = { get: getTheme, set: setTheme };
  window.SCI4K_LANG = { get: getLang, set: setLang, apply: applyLang };
})();

/* ---- Site chrome commun a toutes les pages (burger, header au scroll, reveal, compteurs) ---- */
document.addEventListener('DOMContentLoaded', function () {
  var burgerBtn = document.getElementById('burgerBtn');
  var mobileMenu = document.getElementById('mobileMenu');
  if (burgerBtn && mobileMenu) {
    burgerBtn.addEventListener('click', function () { mobileMenu.classList.toggle('open'); });
  }

  var header = document.getElementById('siteHeader');
  if (header) {
    window.addEventListener('scroll', function () {
      header.classList.toggle('solid', window.scrollY > 40);
    });
  }

  var revealEls = document.querySelectorAll('.reveal,.reveal-left,.reveal-right,.reveal-scale');
  if (revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.1 });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  var counters = document.querySelectorAll('.cnt');
  if (counters.length) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) return;
        var el = e.target;
        var target = parseInt(el.dataset.target, 10);
        var dur = 1600;
        var start = performance.now();
        function tick(t) {
          var p = Math.min((t - start) / dur, 1);
          var eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.round(eased * target);
          if (p < 1) requestAnimationFrame(tick);
          else el.style.animation = 'countBump .4s ease';
        }
        requestAnimationFrame(tick);
        cio.unobserve(el);
      });
    }, { threshold: 0.4 });
    counters.forEach(function (el) { cio.observe(el); });
  }
});

/* ---- Page: Accueil (index.html) ---- */
(function () {
  if (!document.body.classList.contains('page-home')) return;

  window.scrollPartners = function (amount) {
    var v = document.getElementById('partnersViewport');
    if (v) v.scrollBy({ left: amount, behavior: sci4kDefilement() });
  };
})();

/* ---- Page: Biens (biens.html) ---- */
(function () {
  if (!document.body.classList.contains('page-biens')) return;

  function currentLang() {
    return (window.SCI4K_LANG && window.SCI4K_LANG.get()) || 'fr';
  }

  function i18nText(key, lang) {
    var entry = window.SCI4K_I18N && window.SCI4K_I18N[key];
    return entry ? entry[lang] : key;
  }

  function renderMeta(prop, lang) {
    return prop.meta.map(function (m) {
      var unit = m.unit ? m.unit : i18nText(m.unitKey, lang);
      return '<span><b>' + m.value + '</b> ' + unit + '</span>';
    }).join('');
  }

  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); revealObserver.unobserve(e.target); } });
  }, { threshold: 0.12 });

  function renderPropertyCards() {
    var lang = currentLang();
    var grid = document.getElementById('propertyGrid');
    grid.innerHTML = window.SCI4K_PROPERTIES.map(function (prop) {
      var t = prop[lang];
      var badgeLabel = i18nText(prop.mode === 'vente' ? 'biens.mode.vente' : 'biens.mode.location', lang);
      var badgeClass = prop.mode === 'vente' ? ' vente' : '';
      return '\n      <div class="prop-card reveal" onclick="openPropertyModal(' + prop.id + ')" data-category="' + prop.category + '" data-mode="' + prop.mode + '" data-type="' + prop.type + '" data-loc="' + prop.loc + '" data-rooms="' + prop.roomsCount + '" data-surface="' + prop.surface + '" style="--i:' + prop.id + '">\n        <div class="prop-visual" style="background:' + prop.bgGrad + ';">\n          <span class="prop-badge' + badgeClass + '">' + badgeLabel + '</span>\n          <svg viewBox="0 0 200 140" fill="none">' + prop.svg + '</svg>\n        </div>\n        <div class="prop-body">\n          <div class="prop-type">' + t.type + '</div>\n          <h4>' + t.title + '</h4>\n          <div class="prop-loc">' + t.loc + '</div>\n          <div class="prop-meta">' + renderMeta(prop, lang) + '</div>\n          <div class="prop-footer-line">\n            <button class="prop-btn">' + i18nText('biens.viewSheet', lang) + '</button>\n          </div>\n        </div>\n      </div>';
    }).join('');
    grid.querySelectorAll('.prop-card.reveal').forEach(function (el) { revealObserver.observe(el); });
  }

  var activePropertyIndex = null;

  window.openPropertyModal = function (id) {
    activePropertyIndex = id;
    var lang = currentLang();
    var prop = window.SCI4K_PROPERTIES.find(function (p) { return p.id === id; });
    if (!prop) return;
    var t = prop[lang];

    document.getElementById('mTitle').textContent = t.title;
    document.getElementById('mBadge').textContent = t.type;
    document.getElementById('mLoc').textContent = t.loc;
    document.getElementById('mType').textContent = t.type;
    document.getElementById('mSurface').textContent = t.surface;
    document.getElementById('mRooms').textContent = t.rooms;
    document.getElementById('mLegal').textContent = t.legal;
    document.getElementById('mDesc').textContent = t.desc;
    document.getElementById('mCtaBtn').href = prop.contactLink;

    var visualEl = document.getElementById('mVisual');
    visualEl.style.background = prop.bgGrad;
    visualEl.innerHTML = '<svg viewBox="0 0 200 140" fill="none"><path d="M20 90 60 55l40 35" stroke="#e3c087" stroke-width="2.5"/><rect x="35" y="90" width="50" height="35" stroke="#e3c087" stroke-width="2.5"/><rect x="100" y="70" width="70" height="55" stroke="#e3c087" stroke-width="2.5"/></svg>';

    var featsEl = document.getElementById('mFeatures');
    featsEl.innerHTML = '';
    t.features.forEach(function (f) {
      var span = document.createElement('span');
      span.className = 'feat-tag';
      span.textContent = '✓ ' + f;
      featsEl.appendChild(span);
    });

    document.getElementById('propertyModalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
  };

  window.closePropertyModal = function (e) {
    if (e.target.id === 'propertyModalOverlay') {
      window.closePropertyModalDirect();
    }
  };

  window.closePropertyModalDirect = function () {
    document.getElementById('propertyModalOverlay').classList.remove('active');
    document.body.style.overflow = 'auto';
  };

  var activeSearchMode = 'all';
  var activePillFilter = 'all';

  window.setSearchMode = function (mode) {
    activeSearchMode = mode;
    document.getElementById('btnModeLocation').classList.toggle('active', mode === 'location');
    document.getElementById('btnModeVente').classList.toggle('active', mode === 'vente');
    window.filterProperties();
  };

  window.setPillFilter = function (filter, element) {
    activePillFilter = filter;
    document.querySelectorAll('#pillFilters .pill').forEach(function (p) { p.classList.remove('active'); });
    element.classList.add('active');
    window.filterProperties();
  };

  window.filterProperties = function () {
    var selectedType = document.getElementById('selectType').value;
    var selectedLoc = document.getElementById('selectLoc').value;
    var selectedRooms = document.getElementById('selectRooms').value;
    var selectedSurface = document.getElementById('selectSurface').value;

    var cards = document.querySelectorAll('#propertyGrid .prop-card');
    var visibleCount = 0;

    cards.forEach(function (card) {
      var mode = card.dataset.mode;
      var type = card.dataset.type;
      var loc = card.dataset.loc;
      var rooms = parseInt(card.dataset.rooms, 10);
      var surface = card.dataset.surface;
      var category = card.dataset.category;

      var modeMatch = (activeSearchMode === 'all' || mode === activeSearchMode);
      var pillMatch = (activePillFilter === 'all' || category.includes(activePillFilter) || mode === activePillFilter);
      var typeMatch = (selectedType === 'all' || type === selectedType);
      var locMatch = (selectedLoc === 'all' || loc === selectedLoc);
      var surfaceMatch = (selectedSurface === 'all' || surface === selectedSurface);
      var roomsMatch = true;

      if (selectedRooms === '1') roomsMatch = rooms <= 2;
      else if (selectedRooms === '3') roomsMatch = rooms >= 3 && rooms <= 4;
      else if (selectedRooms === '5') roomsMatch = rooms >= 5;

      if (modeMatch && pillMatch && typeMatch && locMatch && surfaceMatch && roomsMatch) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    updateResultsCount(visibleCount);
  };

  function updateResultsCount(count) {
    var lang = currentLang();
    var suffix = lang === 'fr'
      ? (count > 1 ? ' biens disponibles' : ' bien disponible')
      : (count > 1 ? ' properties available' : ' property available');
    document.getElementById('resultsCount').textContent = count + suffix;
  }

  document.addEventListener('sci4k:langchange', function () {
    renderPropertyCards();
    window.filterProperties();
    if (activePropertyIndex !== null && document.getElementById('propertyModalOverlay').classList.contains('active')) {
      window.openPropertyModal(activePropertyIndex);
    }
  });

  renderPropertyCards();
  window.filterProperties();
})();

/* ---- Page: Presentation (presentation.html) ---- */
(function () {
  if (!document.body.classList.contains('page-presentation')) return;
  // Compteurs déjà couverts par le bloc commun (.cnt observé plus haut).
})();

/* ---- Page: Services (services.html) ---- */
(function () {
  if (!document.body.classList.contains('page-services')) return;

  var serviceCards = document.querySelectorAll('.service-item-block');
  serviceCards.forEach(function (card) {
    card.addEventListener('pointerenter', function () {
      serviceCards.forEach(function (item) { item.classList.remove('active'); });
      card.classList.add('active');
    });
    card.addEventListener('pointerleave', function () {
      card.classList.remove('active');
    });
    card.addEventListener('click', function (event) {
      if (!event.target.closest('a')) {
        event.preventDefault();
        var alreadyActive = card.classList.contains('active');
        serviceCards.forEach(function (item) { item.classList.remove('active'); });
        if (!alreadyActive) card.classList.add('active');
      }
    });
  });
})();

/* ---- Page: Contact (contact.html) ---- */
(function () {
  if (!document.body.classList.contains('page-contact')) return;

  var urlParams = new URLSearchParams(window.location.search);
  var bienParam = urlParams.get('bien');
  var serviceParam = urlParams.get('service');

  if (bienParam) {
    document.getElementById('formTitle').textContent = 'Demande de visite : ' + bienParam;
    document.getElementById('messageTextarea').value = 'Bonjour,\n\nJe suis très intéressé(e) par le bien "' + bienParam + '" et je souhaite planifier une visite ou recevoir des informations complémentaires.\n\nCordialement,';
  } else if (serviceParam) {
    var map = { Foncier: 'Foncier', Construction: 'Construction', GestionLocative: 'Gestion', Achat: 'Achat', Vente: 'Vente', Administration: 'Gestion' };
    if (map[serviceParam]) document.getElementById('contactSubject').value = map[serviceParam];
  }

  /* Numero WhatsApp de SCI4K, identique a celui du bouton flottant. */
  var WHATSAPP_NUMERO = '2250706165029';

  function valeur(id) {
    var el = document.getElementById(id);
    return el ? el.value.trim() : '';
  }

  window.handleContactSubmit = function (event) {
    event.preventDefault();

    /* La demande part sur WhatsApp : c'est le canal reellement releve par
       l'agence. Le formulaire ne fait que composer le message, l'envoi final
       reste un geste explicite du visiteur dans WhatsApp. */
    var lignes = [
      'Bonjour SCI4K,',
      '',
      'Nom : ' + valeur('contactName'),
      'Telephone : ' + valeur('contactPhone'),
      'Email : ' + valeur('contactEmail'),
      'Sujet : ' + valeur('contactSubject'),
      '',
      valeur('messageTextarea')
    ];

    /* Une URL trop longue est rejetee silencieusement par le navigateur ou par
       WhatsApp. La borne maxlength du formulaire ne suffit pas a l'empecher :
       un caractere accentue occupe six caracteres une fois encode, si bien
       qu'un message francais peut tripler de taille. On mesure donc l'URL
       reelle et on abrege le corps du message plutot que de le perdre. */
    var LIMITE_URL = 1900;
    var entete = lignes.slice(0, 7).join('\n');
    var corps = lignes[7];

    function composer(texte) {
      return 'https://wa.me/' + WHATSAPP_NUMERO + '?text=' + encodeURIComponent(entete + '\n' + texte);
    }

    var url = composer(corps);
    if (url.length > LIMITE_URL) {
      var suffixe = '\n\n[...] Message abrege, je le complete ici.';
      var coupe = corps.length;
      while (coupe > 0 && composer(corps.slice(0, coupe) + suffixe).length > LIMITE_URL) {
        coupe -= 20;
      }
      url = composer(corps.slice(0, Math.max(coupe, 0)) + suffixe);
    }

    /* Ouvert avant tout reset : sur mobile, un window.open differe est bloque
       par le navigateur car il n'est plus rattache au clic de l'utilisateur. */
    var onglet = window.open(url, '_blank', 'noopener');

    /* Ouverture bloquee : on quitte la page vers WhatsApp. Le formulaire n'est
       alors ni vide ni confirme, pour que la saisie soit encore la si le
       visiteur revient en arriere. */
    if (!onglet) {
      window.location.href = url;
      return;
    }

    document.getElementById('successAlert').style.display = 'block';
    document.getElementById('contactForm').reset();
    window.scrollTo({ top: document.querySelector('.contact-card').offsetTop - 120, behavior: sci4kDefilement() });
  };
})();

/* ---- Page: FAQ (faq.html) ---- */
(function () {
  if (!document.body.classList.contains('page-faq')) return;

  document.querySelectorAll('.faq-list').forEach(function (list) {
    var items = list.querySelectorAll('.faq-item');
    items.forEach(function (item) {
      item.addEventListener('toggle', function () {
        if (item.open) items.forEach(function (other) { if (other !== item) other.open = false; });
      });
    });
  });

  window.handleAskSubmit = function (event) {
    event.preventDefault();
    document.getElementById('askSuccessAlert').style.display = 'block';
    document.getElementById('askForm').reset();
    window.scrollTo({ top: document.querySelector('.ask-card').offsetTop - 120, behavior: sci4kDefilement() });
  };
})();

/* ---- Page: Actualités (actualites.html) ---- */
(function () {
  if (!document.body.classList.contains('page-actualites')) return;

  if (window.location.hash) {
    var target = document.querySelector(window.location.hash);
    if (target) setTimeout(function () { target.scrollIntoView({ behavior: sci4kDefilement(), block: 'start' }); }, 100);
  }
})();


/* ---- Page: Actualités — filtres de la liste ---- */
(function () {
  var grid = document.getElementById('newsGrid');
  if (!grid) return;
  var cards = [].slice.call(grid.querySelectorAll('.news-card'));
  var chips = [].slice.call(document.querySelectorAll('#newsChips .news-chip'));
  var q = document.getElementById('newsQ');
  var from = document.getElementById('newsFrom');
  var to = document.getElementById('newsTo');
  var empty = document.getElementById('newsEmpty');
  var cat = 'all';

  function apply() {
    var texte = (q && q.value || '').trim().toLowerCase();
    var d1 = (from && from.value) ? new Date(from.value) : null;
    var d2 = (to && to.value) ? new Date(to.value) : null;
    var visibles = 0;
    cards.forEach(function (c) {
      var okCat = (cat === 'all') || (c.getAttribute('data-cat') === cat);
      var okTxt = !texte || c.textContent.toLowerCase().indexOf(texte) !== -1;
      var d = new Date(c.getAttribute('data-date'));
      var okDate = (!d1 || d >= d1) && (!d2 || d <= d2);
      var ok = okCat && okTxt && okDate;
      c.style.display = ok ? '' : 'none';
      if (ok) visibles++;
    });
    if (empty) empty.hidden = (visibles !== 0);
  }

  chips.forEach(function (b) {
    b.addEventListener('click', function () {
      chips.forEach(function (x) { x.classList.remove('active'); });
      b.classList.add('active');
      cat = b.getAttribute('data-cat');
      apply();
    });
  });
  [q, from, to].forEach(function (el) { if (el) el.addEventListener('input', apply); });
  var btn = document.getElementById('newsSearch');
  if (btn) btn.addEventListener('click', apply);
})();

/* ---- Page: Actualités — affichage de l'article demande ---- */
(function () {
  var zone = document.querySelector('.news-detail');
  if (!zone) return;
  var arts = [].slice.call(zone.querySelectorAll('.article'));
  if (!arts.length) return;
  var id = null;
  try { id = new URLSearchParams(window.location.search).get('id'); } catch (e) {}
  var cible = (id && document.getElementById(id)) || arts[0];
  arts.forEach(function (a) { a.classList.toggle('is-shown', a === cible); });
  var h = cible.querySelector('h2');
  var t = document.getElementById('detailTitle');
  if (h && t) {
    t.textContent = h.textContent;
    if (h.getAttribute('data-i18n')) t.setAttribute('data-i18n', h.getAttribute('data-i18n'));
  }
  if (h) document.title = h.textContent + ' — SCI4K';
})();


/* ---- Bouton flottant : deploie WhatsApp et le chat en ligne ---- */
(function () {
  var stack = document.getElementById('fabStack');
  var trigger = document.getElementById('fabTrigger');
  if (!stack || !trigger) return;

  function fermer() {
    stack.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');
  }
  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    var ouvert = stack.classList.toggle('is-open');
    trigger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
  });
  document.addEventListener('click', function (e) {
    if (!stack.contains(e.target)) fermer();
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') fermer();
  });

  /* ---- Chat en ligne (tawk.io) ----
     Le script n est charge qu au premier clic : aucun appel a tawk.to, et donc
     aucun traceur tiers, tant que le visiteur ne demande pas le chat.
     La bulle native de tawk.io est masquee pour ne pas doubler notre bouton :
     c est notre declencheur qui ouvre et ferme la fenetre. */
  var TAWK_ID = '6a88cadcbc557a344a5e472b/1k0j5gs6l';

  var charge = false;
  function chargerTawk() {
    if (charge) return false;
    charge = true;
    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();
    window.Tawk_API.onLoad = function () {
      window.Tawk_API.hideWidget();   // on masque la bulle native
      window.Tawk_API.maximize();     // et on ouvre directement la fenetre
    };
    // a la fermeture, on remasque la bulle : notre bouton reprend la main
    window.Tawk_API.onChatMinimized = function () { window.Tawk_API.hideWidget(); };
    window.Tawk_API.onChatEnded = function () { window.Tawk_API.hideWidget(); };
    var s = document.createElement('script');
    s.async = true;
    s.src = 'https://embed.tawk.to/' + TAWK_ID;
    s.charset = 'UTF-8';
    s.setAttribute('crossorigin', '*');
    document.head.appendChild(s);
    return true;
  }

  var chat = document.getElementById('fabChat');
  if (chat) {
    chat.addEventListener('click', function () {
      if (!chargerTawk() && window.Tawk_API && window.Tawk_API.maximize) {
        window.Tawk_API.maximize();
      }
      fermer();
    });
  }
})();


/* ---- Services : ouverture de la modale de detail ---- */
(function () {
  var modal = document.getElementById('svcModal');
  var corps = document.getElementById('svcModalBody');
  if (!modal || !corps) return;
  var dernier = null;

  function ouvrir(sid) {
    var src = document.getElementById('svcPanel-' + sid);
    if (!src) return;
    corps.innerHTML = src.innerHTML;
    var t = corps.querySelector('.svc-panel-title');
    if (t) t.id = 'svcModalTitle';
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    var c = modal.querySelector('.svc-modal-close');
    if (c) c.focus();
  }
  function fermer() {
    modal.hidden = true;
    document.body.style.overflow = '';
    if (dernier) dernier.focus();
  }

  document.querySelectorAll('[data-svc]').forEach(function (el) {
    el.addEventListener('click', function () {
      dernier = el;
      ouvrir(el.getAttribute('data-svc'));
    });
  });
  modal.querySelectorAll('[data-svc-close]').forEach(function (el) {
    el.addEventListener('click', fermer);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !modal.hidden) fermer();
  });
})();


/* ---- Newsletter (pied de page, toutes pages) ----
   Aucun backend n'est encore branche : l'inscription part par email vers
   l'agence plutot que d'etre silencieusement perdue. */
(function () {
  var EMAIL_AGENCE = 'contact@sci4k.com';

  document.querySelectorAll('.newsletter').forEach(function (bloc) {
    var champ = bloc.querySelector('input[type="email"]');
    var bouton = bloc.querySelector('.newsletter-btn');
    if (!champ || !bouton) return;

    function inscrire() {
      var adresse = champ.value.trim();

      /* On s'appuie sur la validation native du navigateur plutot que sur une
         expression reguliere maison, toujours plus laxiste ou plus stricte. */
      if (!adresse || !champ.checkValidity()) {
        champ.setAttribute('aria-invalid', 'true');
        champ.focus();
        return;
      }
      champ.removeAttribute('aria-invalid');

      var sujet = encodeURIComponent('Inscription newsletter SCI4K');
      var corps = encodeURIComponent('Bonjour,\n\nJe souhaite m\'inscrire à la newsletter SCI4K.\n\nAdresse email : ' + adresse + '\n');
      window.location.href = 'mailto:' + EMAIL_AGENCE + '?subject=' + sujet + '&body=' + corps;
      champ.value = '';
    }

    bouton.addEventListener('click', inscrire);
    champ.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); inscrire(); }
    });
  });
})();
