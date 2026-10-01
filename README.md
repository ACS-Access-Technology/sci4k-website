# SCI4K — site vitrine et administration

Site de la Société Civile Immobilière SCI4K, à Abidjan : achat, vente,
location, construction et gestion de patrimoine immobilier.

Le dépôt contient **une application** et **deux jeux de maquettes** qui lui
ont servi de référence.

| Dossier | Contenu | État |
|---|---|---|
| `app-laravel/` | L'application : site public rendu depuis la base, et son administration | **C'est ici que se fait le travail** |
| `maquettes-frontoffice/` | 12 pages HTML statiques, une feuille de style, un script | Référence visuelle, et source des ressources copiées dans `public/` |
| `maquettes-backoffice/` | 30 maquettes d'écrans, générées par des scripts Python | Référence visuelle uniquement — aucune donnée, aucun serveur |

Les deux dossiers de maquettes ne sont plus le produit livré. Ils restent
parce qu'ils décrivent l'intention, et parce que `maquettes-frontoffice/` est
la source de vérité des styles, des images et du script du site public.

## L'application

Laravel 13, Livewire 4, Fortify pour l'authentification, spatie/laravel-permission
pour les rôles. PHP 8.3 et **MySQL 9.7 LTS** — la raison du choix, et le
contrôle qui l'impose, sont dans `app/Support/MoteurDeBase.php`.

```bash
cd app-laravel
composer install
npm ci && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan compte:creer-administrateur
php artisan storage:link
cd .. && ./tools/sync-frontoffice.sh
cd app-laravel && php artisan serve
```

`db:seed` pose l'**ossature** du site — rôles, catégories, référentiels,
menus, services, FAQ, textes des sections — et **aucun compte**. Rejoué sur une
base en service, il ne modifie rien : il ne remplit que des tables vides.

`compte:creer-administrateur` crée le premier compte ; le mot de passe est
saisi au clavier, jamais passé en argument.

Pour voir des pages pleines en développement — témoignages, équipe, articles,
biens et réalisations de la maquette, **fictifs** :
`php artisan db:seed --class=DemonstrationSeeder`. Jamais lancé par défaut ; en
production, il demande confirmation et ne sème rien sans réponse.

`./tools/sync-frontoffice.sh` **est indispensable après chaque clonage** : il
dépose dans `app-laravel/public/` les styles, le script et les images. Les
douze pages HTML des maquettes, toutes portées en Blade, sont exclues de la
copie : servies avant Laravel, elles masqueraient les routes. Ces copies ne
sont pas versionnées — la
source unique reste `maquettes-frontoffice/`, et verser un second exemplaire
des mêmes fichiers garantirait qu'un jour l'un soit corrigé et l'autre oublié.

### Le site public

Sept pages sont rendues depuis la base : accueil, présentation, biens,
services, actualités, FAQ, contact, plus les deux pages légales et le plan du
site. Les anciennes adresses en `.html` répondent par une redirection
permanente, et les pages statiques correspondantes sont exclues de la
synchronisation — sans quoi le serveur les servirait avant d'entrer dans PHP,
et masquerait les routes.

Quatre formulaires écrivent en base : message de contact, inscription à la
lettre d'information, commentaire d'article, demande de visite. Ce sont les
seuls points d'écriture ouverts au public. Ils sont hors contrôle CSRF —
délibérément, le raisonnement est écrit dans `bootstrap/app.php` — et
protégés à la place par une limitation à 5 envois par minute, un champ piège
et des longueurs bornées.

**Bilingue.** La langue vient de l'**adresse**, et non plus de la session :
`/services` sert le français, `/en/services` l'anglais. En session, la même
adresse servait deux contenus — un moteur de recherche, qui n'a pas de
session, ne voyait donc que le français, et un lien anglais partagé s'ouvrait
en français chez le destinataire. Le backoffice fait exception et garde la
session : il n'est pas indexé, et préfixer cent routes d'administration
n'apporterait rien.

Les textes viennent de la base ; le dictionnaire `window.SCI4K_I18N` de
`maquettes-frontoffice/assets/main.js` ne sert plus qu'aux pages restées
statiques.

### L'administration

29 modèles, 47 composants Livewire d'administration, 42 migrations.
Quatre rôles : `administrateur`, `editeur`, `redacteur`, `lecteur`.

L'organisation est **une page d'administration par page publique** —
`/admin/pages/accueil`, `/pages/presentation`, `/pages/biens`, `/pages/services`,
`/pages/actualites`, `/pages/faq`, `/pages/contact` — et non un écran par type
de contenu. Les quinze écrans par type ont été retirés : chaque collection
s'édite depuis l'écran de la page qui l'affiche, où elle est embarquée avec
son formulaire. Deux adresses pour une même table, c'était deux endroits où
corriger le même défaut.

S'y ajoutent le tableau de bord, le journal des activités, les messages, les
demandes de visite, la lettre d'information, la médiathèque, la fréquentation,
et — réservés aux administrateurs — la configuration, les référentiels, les
menus et les comptes.

**Ce qui est borné.** Deux tables recevaient des lignes sans que rien ne les
arrête. Le détail des visites n'est gardé que quatre-vingt-dix jours :
`php artisan frequentation:agreger` en tire des comptages par jour puis purge
le reste, rattrape les jours manqués, et ne purge jamais un jour qu'elle n'a
pas réussi à compter. Le journal d'activité garde un an :
`php artisan journal:purger` retire ce qui dépasse — sans agrégat, un journal
d'audit répondant à « qui a touché à quoi » qu'un résumé ne remplacerait pas.

Les deux sont planifiées. En conteneur, le planificateur tourne à côté du
serveur ; sur un hébergement classique, **elles exigent une ligne de cron** :
voir `docs/MISE_EN_LIGNE.md`. Le tableau de bord signale un entretien qui ne
tourne plus.

**Rapport d'erreurs.** Sentry est branché mais inerte sans `SENTRY_LARAVEL_DSN`,
comme la traduction automatique l'est sans sa clé. Aucune donnée personnelle
n'est transmise — ni IP, ni navigateur, ni valeur saisie, ce que
`send_default_pii` à `false` ne garantissait pas seul : voir
`app/Support/SentryAvantEnvoi.php`. Seul l'identifiant interne du compte
backoffice connecté accompagne un rapport.

## Les maquettes d'administration

Les 30 pages de `maquettes-backoffice/` sont **générées**. Ne pas les modifier
à la main : la prochaine génération écraserait le changement.

```bash
cd maquettes-backoffice && python3 _build/build.py
```

Les sources sont `_build/pages_a.py`, `pages_b.py`, `pages_c.py` et
`layout.py`. Un contrôle d'intégration vérifie que le HTML versionné
correspond toujours à ce que produisent ces scripts.

## Contrôles

```bash
python3 tools/verifier-site.py
```

Références mortes, données structurées (dont la concordance entre la FAQ
balisée et la FAQ affichée), intitulés de formulaire, syntaxe JavaScript,
cohérence du plan de site.

```bash
cd app-laravel
./vendor/bin/pint --test        # formatage
./vendor/bin/phpstan analyse    # analyse statique, niveau 5
php artisan test                # la suite complete
```

Ces quatre contrôles tournent dans l'intégration continue à chaque poussée sur
`master`, `preprod` et `dev`, et sur chaque demande de fusion. Ils ne
**bloquent** toute fusion vers `preprod` et `master` : ils sont déclarés
obligatoires dans les protections de branche de GitHub, posées le
1er octobre 2026 — voir `docs/BRANCHES_ET_DEPLOIEMENT.md`. Les tests y sont rejoués deux fois : sur SQLite,
rapide, puis sur MySQL 9.7, la version supportée — les écarts de dialecte ne
se voient pas autrement. `php artisan base:verifier-version` vérifie ensuite
que l'image testée est bien cette version — `mysql:9.7.2`, exactement celle
de la production.

Un second workflow, `audit-dependances.yml`, confronte `composer.lock` et
`package-lock.json` aux failles publiées — aussi chaque lundi, sans changement
de code. Ce qui bloque et ce qui est seulement signalé, et comment traiter une
alerte : `docs/SECURITE_DEPENDANCES.md`.

**Limite connue, non bloquante — retour arrière sous SQLite.** Sur MySQL,
`php artisan migrate:reset` défait les 45 migrations puis les rejoue sans
erreur (vérifié sur 8.0, 8.4, 9.4 et 9.7). Sous SQLite, il échoue en cours de
route, sur l'index `abonnes_newsletter_jeton_unique` : SQLite ne sait pas
retirer une colonne qui porte encore un index. Cela ne touche que la base de
développement locale, jamais la production MySQL ; pour repartir de zéro en
local, `php artisan migrate:fresh` (qui supprime les tables au lieu de défaire
les migrations) fonctionne.

## Branches

| Branche | Rôle |
|---|---|
| `dev` | La branche de travail. C'est elle qui porte l'état courant. |
| `preprod` | Préproduction |
| `master` | **La production.** Il n'y a pas de branche `prod` : `master` tient ce rôle. |

Le flux est obligatoire : `dev` → `preprod` → `master`, par demandes de fusion,
correctifs urgents compris ; le workflow « Flux des branches » refuse toute
autre origine. Avant chaque mise en ligne,
`./tools/verifier-avant-deploiement.sh` vérifie que le dossier de travail est
bien `master`, tel que la CI l'a validé. Le détail, et les protections à poser
dans GitHub : `docs/BRANCHES_ET_DEPLOIEMENT.md`.

## Documents

| Fichier | Objet |
|---|---|
| `docs/PREMIER_DEPLOIEMENT.md` | Chaque mise en ligne : sauvegardes, vérifications, retour arrière, bloquants |
| `docs/DEPLOIEMENT_RAILWAY.md` | La plateforme : services, variables, volume, MySQL |
| `docs/MISE_EN_LIGNE.md` | Les réglages de production, ce qu'il faut obtenir de tiers, et l'hébergement classique en repli |
| `docs/BRANCHES_ET_DEPLOIEMENT.md` | Flux des branches, demandes de fusion, protections GitHub, vérification avant mise en ligne |
| `docs/RELAIS_PROJET.md` | Reprise du contexte projet |
| `ECARTS_FRONT_BACKOFFICE.md` | Confrontation du site public au périmètre couvert par l'administration |
| `BACKOFFICE_SECTIONS.md` | Champs attendus, section par section |
| `WIREFRAME_BACKOFFICE.md` | Maquettes filaires des écrans |
| `maquettes-frontoffice/images/A-REMPLACER.md` | Six visuels provisoires issus de Wikimedia, à remplacer par des photographies de l'agence |

## Points ouverts

Aucun ne se règle en programmant.

- Les mentions légales attendent le numéro **RCCM**, le **Compte
  Contribuable** et le nom de l'**hébergeur**.
- Le nom du **directeur de publication**, et l'autorisation d'afficher les
  **logos des partenaires**.
- Six visuels sont provisoires (voir `A-REMPLACER.md`).
- Domaine, HTTPS, SMTP : voir `docs/MISE_EN_LIGNE.md` ; plan Railway,
  sauvegardes et retour arrière : `docs/PREMIER_DEPLOIEMENT.md`.
