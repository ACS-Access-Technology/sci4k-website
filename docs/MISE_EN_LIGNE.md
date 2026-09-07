# Mise en ligne

Ce que le déploiement demande, dans l'ordre, et ce qu'il reste à obtenir de
tiers. Établi le 7 septembre 2026 à partir de la branche `dev`.

Le document sépare trois choses qui se confondent facilement : ce qu'il faut
**commander**, ce qu'il faut **régler**, et ce qu'il faut **attendre de
quelqu'un d'autre**. Le dernier groupe ne dépend d'aucun code.

---

## 1. Ce qu'il faut commander

| Élément | Contrainte vérifiée | D'où vient la contrainte |
|---|---|---|
| Hébergement PHP **8.3** ou plus | `composer.json` exige `^8.3` | Refus d'installation sous 8.2 |
| Extensions PHP | `mbstring`, `pdo_mysql`, `gd`, `intl` | Étape « Installer PHP » de la CI |
| **MySQL 8.0** | `DB_CONNECTION=mysql`, testé contre `mysql:8.0` | Le type énuméré des statuts diverge sur les moteurs plus anciens |
| Accès **SSH** ou équivalent | `composer install`, `artisan migrate`, `storage:link` | Un hébergement FTP seul ne suffit pas |
| **Node 20** au moment de la construction | `npm run build` produit le manifeste Vite | Peut se faire ailleurs et être téléversé |
| Un **domaine** | — | — |
| Un **certificat HTTPS** | Le cookie de session doit porter `Secure` | Voir §3 |
| Un **compte SMTP** | 6 courriels partent de l'application | Voir §3 |
| Un **accès cron** | `schedule:run` chaque minute | Sans lui la table des visites croît sans borne |

L'application ne réclame **ni Redis, ni superviseur de file d'attente** :
aucune classe n'implémente `ShouldQueue` et les huit envois de courriel sont
synchrones. `QUEUE_CONNECTION=database` est présent mais inerte.

Elle réclame en revanche **une ligne de cron**. Deux tâches quotidiennes
bornent ce qui grossirait sans fin : l'une agrège les visites et purge le
détail au-delà de quatre-vingt-dix jours, l'autre retire du journal d'activité
les entrées de plus d'un an. Sans cette ligne, les deux tables recommencent à
croître sans borne :

```cron
* * * * * cd /chemin/vers/app-laravel && php artisan schedule:run >> /dev/null 2>&1
```

Chaque minute, oui : c'est Laravel qui décide ensuite quoi lancer, et il n'y a
que deux tâches, à 3 h 10 et 3 h 40. Les deux sont rejouables sans risque ;
celle de la fréquentation rattrape en outre les jours manqués, donc un cron
interrompu quelques jours se répare tout seul à la reprise.

## 2. La séquence de déploiement

Dans cet ordre. Chaque étape a une raison d'être avant la suivante.

```bash
# 1. Le code
git clone <depot> && cd <depot>

# 2. Les dependances PHP, sans les outils de developpement
cd app-laravel
composer install --no-dev --optimize-autoloader

# 3. Les ressources construites (Node 20). Sans cette etape, toute page
#    d'administration echoue sur « Vite manifest not found » : les vues
#    appellent @vite et public/build n'est pas versionne.
npm ci && npm run build

# 4. La configuration. NE PAS copier .env.example : il porte APP_ENV=local
#    et APP_DEBUG=true.
cp .env.production.example .env
#    puis renseigner ce qui y est marque « A RENSEIGNER »
php artisan key:generate

# 5. La base
php artisan migrate --force

# 6. Le lien des images televersees. Sans lui, toute image ajoutee depuis le
#    backoffice est ecrite mais ne s'affiche jamais : le code ecrit
#    systematiquement sur le disque « public », qui vit dans storage/.
php artisan storage:link

# 7. Les ressources du site statique, deposees dans public/
cd .. && ./tools/sync-frontoffice.sh

# 8. Les caches de production
cd app-laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**La racine du serveur web doit être `app-laravel/public/`**, pas la racine du
dépôt. Autrement `.env`, `storage/` et le code source deviennent
téléchargeables.

Les étapes 2, 3, 5, 6, 7 et 8 sont à rejouer à chaque mise à jour. L'étape 4
ne se rejoue pas : elle écraserait la configuration en place.

## 3. Les réglages à ne pas manquer

Relevés en lisant la configuration. Les cinq premiers sont bloquants.

1. **`APP_DEBUG=false` et `APP_ENV=production`.** `.env.example` porte
   l'inverse, parce que c'est le gabarit de développement. Un déploiement qui
   le copie affiche la trace d'exécution complète — requêtes SQL et variables
   d'environnement comprises — au premier visiteur qui déclenche une erreur.
   C'est la raison d'être de `.env.production.example`.

2. **`SESSION_SECURE_COOKIE=true`.** Non défini, le cookie de session part
   aussi en clair et se laisse lire sur le trajet. Le gabarit de production le
   pose déjà.

3. **`TRUSTED_PROXIES`, si un proxy se tient devant PHP** — nginx en frontal,
   un répartiteur de charge, Cloudflare. Sans cette déclaration, `isSecure()`
   répond faux : les liens et les redirections repartent en `http`, le cookie
   ci-dessus n'est jamais marqué `Secure` malgré le réglage, et la limitation
   de débit compte toutes les visites sur l'adresse du proxy — un seul
   compteur `throttle:5,1` pour l'ensemble des visiteurs, ce qui ferme les
   quatre formulaires publics au cinquième envoi du jour.

4. **SMTP réel.** Sous `MAIL_MAILER=log`, les courriels sont écrits dans le
   journal et ne partent pas : accusé d'un message de contact, alerte d'une
   demande de visite, avis d'un nouveau commentaire, réponse à un message,
   invitation d'un compte du backoffice, message d'essai. L'écran
   Configuration comporte un bouton d'essai pour vérifier les identifiants.

5. **`php artisan storage:link`.** Voir l'étape 6 ci-dessus.

6. **Journalisation.** `LOG_STACK=daily` et `LOG_LEVEL=warning`. Le gabarit de
   développement écrit dans un fichier unique que rien ne fait tourner, au
   niveau `debug` — donc chaque requête SQL. Il sature le disque à terme.

7. **`SENTRY_LARAVEL_DSN`** est facultatif mais vivement conseillé : sans lui,
   une erreur en production n'est signalée à personne et ne se découvre que
   par un visiteur qui se plaint. Aucune donnée personnelle n'est transmise —
   `send_default_pii` reste à `false`, donc ni adresse IP, ni en-têtes, ni
   corps de requête. Seul l'identifiant interne du compte backoffice connecté
   accompagne le rapport, ce qui laisse la politique de confidentialité vraie
   telle qu'elle est écrite.

8. **`DEEPL_API_KEY`** reste facultatif : sans clé, la traduction automatique
   des articles se tait et les deux langues se saisissent à la main.

### Ce qui est déjà correct

Vérifié, rien à faire :

- Les huit envois de courriel sont enveloppés dans `try/catch` avec `report()`,
  et le message est enregistré en base **avant** l'envoi. Un SMTP en panne ne
  perd donc aucune demande — elle attend dans le backoffice.
- `/up` reste ouvert quand le mode maintenance est actif : la sonde de
  l'hébergeur ne verra pas le site comme mort pendant une fermeture volontaire.
- `http_only` à `true` et `same_site` à `lax` sur le cookie de session.
- Les quatre routes d'écriture publiques sont limitées à 5 envois par minute,
  avec un champ piège et des longueurs bornées dans chaque contrôleur.

## 4. Ce qui ne dépend pas du code

Aucune de ces lignes ne se règle en programmant. Elles bloquent la mise en
ligne autant que le reste.

| À obtenir | Auprès de qui |
|---|---|
| RCCM et numéro de compte contribuable | Le client |
| Nom du directeur de publication | Le client |
| Autorisation d'afficher les logos des partenaires | Le client |
| Coordonnées de l'hébergeur pour les mentions légales | Le client, une fois l'hébergement choisi |
| Textes juridiques définitifs (mentions légales, politique de confidentialité) | La direction |

L'intégration continue, elle, tourne : les contrôles de non-régression et les
tests Laravel s'exécutent sur les trois branches et passent. Un check
supplémentaire vient de SonarCloud, hors GitHub Actions, sur la qualité du
code.

## 5. Les branches

Trois branches permanentes chez `acs`, alignées sur le même commit :

| Branche | Rôle |
|---|---|
| `dev` | La branche de travail |
| `preprod` | Préproduction |
| `master` | **La production.** C'est depuis elle que le site se déploie. |

Il n'y a **pas** de branche `prod`, et c'est délibéré : `master` tient ce
rôle. Une quatrième branche qui suivrait `master` pas à pas n'ajouterait
qu'un endroit de plus où oublier de pousser.

Un relevé antérieur affirmait qu'une branche `prod` existait et pointait sur
un commit orphelin, `68dba05 "Initial commit: Laravel project setup"`, sans
lien d'historique avec `master` et avec l'application à la racine du dépôt au
lieu de `app-laravel/`. Ce constat était faux : il venait des références d'un
second clone git logé dans `app-laravel/`, qui n'avait pas resynchronisé
depuis 195 commits et montrait donc l'état du dépôt tel qu'il était des
semaines plus tôt.

Ce clone a été retiré. La leçon vaut d'être retenue : un dépôt imbriqué
répond aux commandes git à la place du vrai, avec ses propres références
périmées, sans que rien ne le signale.
