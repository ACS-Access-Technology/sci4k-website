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
pour les rôles. PHP 8.3 et MySQL 8.

```bash
cd app-laravel
composer install
npm ci && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan storage:link
cd .. && ./tools/sync-frontoffice.sh
cd app-laravel && php artisan serve
```

`./tools/sync-frontoffice.sh` **est indispensable après chaque clonage** : il
dépose dans `app-laravel/public/` les styles, le script, les images et les
pages non encore portées en Blade. Ces copies ne sont pas versionnées — la
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

Ces quatre contrôles sont **bloquants** dans l'intégration continue, sur
`master`, `preprod` et `dev`. Les tests y sont rejoués deux fois : sur SQLite,
rapide, puis sur MySQL, le moteur réellement servi en production — les écarts
de dialecte ne se voient pas autrement.

## Branches

| Branche | Rôle |
|---|---|
| `dev` | La branche de travail. C'est elle qui porte l'état courant. |
| `master` | Intégration |
| `preprod` | Préproduction |
| `prod` | **À refaire** — voir `docs/MISE_EN_LIGNE.md`, §5 |

## Documents

| Fichier | Objet |
|---|---|
| `docs/MISE_EN_LIGNE.md` | Ce que le déploiement demande, et les réglages de production |
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
- La **facturation GitHub Actions** de l'organisation : tant qu'elle n'est pas
  réglée, les contrôles ci-dessus sont refusés avant démarrage et ne protègent
  rien.
- Domaine, hébergement, HTTPS, SMTP, sauvegardes : voir `docs/MISE_EN_LIGNE.md`.
