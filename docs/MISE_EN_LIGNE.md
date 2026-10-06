# Mise en ligne

Ce que le déploiement demande, dans l'ordre, et ce qu'il reste à obtenir de
tiers. Établi le 7 septembre 2026 à partir de la branche `dev`, complété le
14 septembre 2026 : sauvegarde de la base avant une migration destructive, et
partage des rôles entre cette séquence et ce que le conteneur tient déjà.

Le document sépare trois choses qui se confondent facilement : ce qu'il faut
**commander**, ce qu'il faut **régler**, et ce qu'il faut **attendre de
quelqu'un d'autre**. Le dernier groupe ne dépend d'aucun code.

**La stratégie de déploiement retenue est unique** : un conteneur sur Railway,
mis en ligne par `railway up` depuis `master`, après
`tools/verifier-avant-deploiement.sh`. La procédure de chaque mise en ligne —
sauvegardes, vérifications, retour arrière — est dans
`PREMIER_DEPLOIEMENT.md` ; la plateforme, dans `DEPLOIEMENT_RAILWAY.md`. Le
passage au domaine définitif suit la même voie : ce document en liste les
réglages (§3) et ce qu'il faut obtenir de tiers (§4).

Le §1 et le §2 décrivent un **hébergement classique** (SSH, cron). Ils sont
gardés en plan de repli, pour le jour où la plateforme changerait ; ils ne
servent pas aujourd'hui.

---

## 1. Ce qu'il faut commander (hébergement classique, plan de repli)

En conteneur, l'image apporte PHP, ses extensions et Node ; le planificateur
tourne à côté du serveur. Le projet Railway est sur le plan Hobby, dont les
limites — aucune sauvegarde de volume, notamment — sont dans
`PREMIER_DEPLOIEMENT.md`, §1. Restent à obtenir : le domaine, et un compte
SMTP.

| Élément | Contrainte vérifiée | D'où vient la contrainte |
|---|---|---|
| Hébergement PHP **8.3** ou plus | `composer.json` exige `^8.3` | Refus d'installation sous 8.2 |
| Extensions PHP | `mbstring`, `pdo_mysql`, `gd`, `intl` | Étape « Installer PHP » de la CI |
| **MySQL 9.7** (LTS) | `DB_CONNECTION=mysql`, testé contre `mysql:9.7` ; `php artisan base:verifier-version` compare le serveur joint | 8.0 n'est plus supporté par Oracle depuis avril 2026, et les versions « Innovation » (9.4…) ne le sont que quelques mois — voir `app/Support/MoteurDeBase.php` |
| Accès **SSH** ou équivalent | `composer install`, `artisan migrate`, `storage:link` | Un hébergement FTP seul ne suffit pas |
| **Node 20** au moment de la construction | `npm run build` produit le manifeste Vite | Peut se faire ailleurs et être téléversé |
| Un **domaine** | — | — |
| Un **certificat HTTPS** | Le cookie de session doit porter `Secure` | Voir §3 |
| Un **compte SMTP** | 6 courriels partent de l'application, plus la réinitialisation de mot de passe (Fortify) | Voir §3 |
| Un **accès cron** | `schedule:run` chaque minute — sauf en conteneur, où le planificateur tourne à côté du serveur | Sans lui la table des visites croît sans borne |

L'application ne réclame **ni Redis, ni superviseur de file d'attente** :
aucune classe n'implémente `ShouldQueue` et les six envois de courriel sont
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
que deux tâches, à 3 h 10 et 3 h 40. Une ligne à `10 3 * * *` ne suffirait
pas : `schedule:run` n'exécute que ce qui est dû à la minute même, la purge de
3 h 40 ne tournerait jamais. Les deux sont rejouables sans risque ; celle de la
fréquentation rattrape en outre les jours manqués, donc un cron interrompu
quelques jours se répare tout seul à la reprise.

**En conteneur (Railway), aucune ligne de cron** : le planificateur tourne à
côté du serveur, relancé s'il s'arrête. Voir `docs/DEPLOIEMENT_RAILWAY.md`.

**Savoir si l'entretien tourne** : le panneau « Entretien automatique » du
tableau de bord de l'administration, en alerte au-delà de 26 h sans passe
réussie ; et, si `SENTRY_LARAVEL_DSN` est renseigné, les moniteurs Sentry
Crons, qui préviennent d'eux-mêmes.

## 2. La séquence de déploiement (hébergement classique, plan de repli)

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

# 5. La base, son ossature, puis le premier administrateur. db:seed ne cree
#    aucun compte et ne seme aucun contenu fictif ; sur une base deja remplie,
#    il ne modifie rien. Le mot de passe se saisit au clavier.
php artisan migrate --force
php artisan db:seed --force
php artisan compte:creer-administrateur

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

**Cette séquence est celle d'un hébergement classique, avec un accès SSH.**
Sur une plateforme à conteneurs, elle est déjà tenue par l'image et par le
script de démarrage : `Dockerfile` enchaîne `npm run build` et
`tools/sync-frontoffice.sh`, `tools/demarrer-conteneur.sh` fait le lien de
`storage/`, les migrations et les trois caches. Il n'y a donc rien à rejouer
à la main après un déploiement Railway — et surtout rien à oublier. Voir
`DEPLOIEMENT_RAILWAY.md`.

### Sauvegarder avant toute migration qui supprime

Le mot « sauvegarde » ne figurait nulle part dans cette note. Il y entre
maintenant, parce que la première migration destructive du projet vient
d'arriver.

Toutes les migrations ne se défont pas également. Ajouter une colonne se
retire sans perte. En **supprimer** une emporte son contenu, et le `down()`
qui prétend la reconstruire ne restitue que ce qu'une autre table conserve
encore — il rejoue une copie, il ne ressuscite rien.

La première de ce genre est
`2026_09_14_120000_cree_les_equipements_administrables` : elle verse les
équipements de `biens.equipements` dans le référentiel et dans la table de
jointure, puis supprime la colonne. Son retour arrière a été éprouvé sur des
données réellement saisies depuis le back-office et ne perd rien — mais il ne
vaut que tant que la table de jointure est intacte. Une seconde erreur
par-dessus, et il n'existe plus de source à recopier.

```bash
mysqldump --single-transaction --routines --triggers \
  -u <utilisateur> -p <base> > sauvegarde-$(date +%Y%m%d-%H%M).sql
```

Vérifier que le fichier n'est pas vide avant d'aller plus loin. Sur un
hébergement à conteneurs, le disque est éphémère : la sauvegarde doit
**sortir** du conteneur, rapatriée en local ou déposée sur un stockage objet.
Un fichier écrit à côté de l'application disparaît au déploiement suivant.

**Sur Railway, les migrations n'attendent personne.**
`tools/demarrer-conteneur.sh` les lance au démarrage du conteneur, et
`MIGRER_AU_DEMARRAGE` vaut `true` par défaut : pousser sur `master` suffit à
ce qu'une migration destructive s'exécute, sans aucune étape manuelle où
s'arrêter pour sauvegarder. Pour une mise à jour qui en contient une :

1. poser `MIGRER_AU_DEMARRAGE=false` dans les variables du service ;
2. déployer ;
3. sauvegarder la base ;
4. lancer `php artisan migrate --force` depuis la console du service ;
5. remettre `MIGRER_AU_DEMARRAGE=true`.

Sans cette précaution, la sauvegarde arrive après la suppression, ce qui
revient à ne pas en avoir.

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
   invitation d'un compte du backoffice, message d'essai. `log` n'est pas une
   configuration de production. Variables : `MAIL_MAILER=smtp`, `MAIL_HOST`,
   `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, et **`MAIL_FROM_ADDRESS`** —
   une adresse du domaine de l'agence, autorisée par le fournisseur (SPF,
   DKIM) ; vide, Laravel envoie au nom de `hello@example.com`. L'écran
   Configuration peut aussi porter le serveur, et comporte un bouton d'essai ;
   il ne s'applique qu'aux pages, pas aux commandes console. Les identifiants
   se posent dans les variables du service, jamais dans un fichier du dépôt.

5. **`php artisan storage:link`.** Voir l'étape 6 ci-dessus.

6. **Journalisation.** `LOG_STACK=stderr` et `LOG_LEVEL=warning` : en
   conteneur, Railway collecte la sortie d'erreur, et un fichier écrit dans le
   conteneur disparaîtrait au redéploiement. Sur hébergement classique,
   `LOG_STACK=daily`. Jamais le réglage du gabarit de développement : un
   fichier unique que rien ne fait tourner, au niveau `debug` — donc chaque
   requête SQL, avec ses valeurs.

   **`PASSKEYS_USER_HANDLE_SECRET`** : vide, c'est `APP_KEY` qui en tient lieu,
   et changer `APP_KEY` rend alors inutilisables toutes les passkeys. Y poser
   la valeur actuelle de `APP_KEY` les en détache sans rien casser. Le domaine
   définitif, lui, les invalidera de toute façon : l'identifiant de « partie
   de confiance » est l'hôte de `APP_URL`. Détail dans
   `.env.production.example`.

7. **`SENTRY_LARAVEL_DSN`** est facultatif mais vivement conseillé : sans lui,
   une erreur en production n'est signalée à personne et ne se découvre que
   par un visiteur qui se plaint. Aucune donnée personnelle n'est transmise :
   ni adresse IP, ni navigateur, ni corps de requête, ni chaîne de requête, ni
   valeur saisie — pas même dans un message d'erreur SQL. `send_default_pii`
   à `false` n'y suffisait pas : le détail, et le test qui intercepte ce qui
   part réellement, sont dans `app/Support/SentryAvantEnvoi.php`. Seul
   l'identifiant interne du compte backoffice connecté accompagne le rapport,
   ce qui laisse la politique de confidentialité vraie telle qu'elle est
   écrite.

   Poser aussi **`SENTRY_ENVIRONMENT`** : sans lui, Sentry reprend `APP_ENV`,
   et l'instance d'essai — en `APP_ENV=production` — mêlerait ses erreurs à
   celles du site définitif. La version (release) se renseigne seule sur
   Railway : l'identifiant du déploiement, `railway up` ne transmettant pas de
   commit ; ailleurs, `SENTRY_RELEASE`. `SENTRY_TRACES_SAMPLE_RATE` reste à
   `0` : les traces consomment le quota bien plus vite que les erreurs.

   À préparer avant la mise en ligne définitive : un projet Sentry (PHP /
   Laravel), son DSN posé dans les variables du service, et deux alertes —
   toute nouvelle erreur, et les moniteurs Crons `sci4k-frequentation-agreger`
   et `sci4k-journal-purger`, créés d'eux-mêmes au premier signal. Un essai :
   `php artisan sentry:test`, sous `www-data`, depuis la console du service.

8. **`DEEPL_API_KEY`** reste facultatif : sans clé, la traduction automatique
   des articles se tait et les deux langues se saisissent à la main.

### L'indexation : une case, et le bon moment

Dans `/admin/configuration`, la case **« Autoriser l'indexation par les
moteurs de recherche »** gouverne à la fois le `robots.txt` et la balise
`<meta name="robots">`, sur toutes les pages et tous les gabarits. Une seule
source, pas de risque qu'ils se contredisent.

**Elle doit rester décochée tant que le site vit sur une adresse provisoire.**
Sur l'instance d'essai, PageSpeed note le SEO 69 sur 100 et annonce
« L'indexation de la page est bloquée » : ce n'est pas un défaut à corriger,
c'est la conséquence attendue et voulue.

La cocher trop tôt coûte cher, et longtemps. Google indexerait l'adresse
provisoire — `*.up.railway.app` — qui entrerait alors en concurrence avec le
domaine définitif le jour du lancement. Et l'ancienneté joue en sa faveur :
c'est la mauvaise adresse qui sortirait en premier. Sortir une adresse de
l'index demande ensuite des semaines, pendant lesquelles le site doit rester
en ligne à servir un `noindex` pour que les robots le constatent.

**À cocher donc au lancement, une fois le domaine définitif en place** — et
pas avant. Deux conditions l'accompagnent : les pages légales complètes (voir
§4), puisqu'un site indexé est un site qui se visite, et un contenu réel
plutôt que les biens de démonstration.

### Le contenu de démonstration, et comment le retirer

Deux jeux de contenu fictif cohabitent aujourd'hui dans la table `biens`, et
aucun ne doit survivre au lancement.

**Les six biens du catalogue** viennent de la maquette : ils étaient écrits en
dur dans `frontoffice/assets/main.js` avant le passage sous Laravel, et ont été
importés tels quels par `BiensSeeder`. Ils n'ont ni prix, ni référence, ni
numéro de titre, ni photo — un bien réel a les quatre. À remplacer par le
catalogue de l'agence.

**Les six réalisations** des rubriques « Construction » et « Administration de
biens » ont été écrites pour juger de la mise en page, faute de références
réelles à montrer. Elles portent toutes une référence préfixée `DEMO-`,
précisément pour rester reconnaissables une fois mêlées à du contenu vrai :

```bash
php artisan tinker --execute="App\Models\Bien::where('reference','like','DEMO-%')->delete();"
```

La suppression emporte les rattachements aux activités, la table de jointure
étant en cascade. Les deux rubriques redeviennent alors vides — ce qui est
l'état honnête tant que l'agence n'a pas fourni ses vraies références.

**Pourquoi ce n'est pas un détail :** un visiteur ne distingue pas une fiche
inventée d'une vraie. Publier « Résidence Akwaba · 24 lots gérés » sur le
domaine définitif, c'est annoncer une référence commerciale qui n'existe pas.

**État réel de la production, relevé le 6 octobre 2026** (base en lecture
seule, rapprochée des données de la maquette, `database/data/*.json`) :

| Contenu | Origine | Retouché depuis le 7 septembre ? | Statut |
|---|---|---|---|
| 12 articles | maquette, tous | non | démonstration — à retirer |
| 3 témoignages (« Mireille K. », « Serge D. », « Aïcha Y. ») | maquette | non | démonstration — à retirer |
| 3 chiffres clés (120 biens, 8 ans, 96 %) | maquette | non | démonstration — à retirer ou à remplacer par des chiffres réels |
| 7 partenaires et leurs logos | maquette | non | à retirer, sauf accord écrit de chaque organisme |
| 6 biens | maquette (« Villa Les Palmiers » renommé « Villa F6 ») | **oui**, les 15, 23 et 30 septembre | retravaillés par l'agence : **décision de l'agence**, bien par bien |
| Équipe (3 personnes) | **pas la maquette** (qui en nomme 4 autres) | oui, les 11 et 28 septembre | **contenu réel — à garder** |
| Réalisations `DEMO-` | — | — | aucune en production |

Rien n'a été supprimé. Le retrait se fait **après** une sauvegarde
(`PREMIER_DEPLOIEMENT.md`, §4), de préférence depuis le backoffice, table par
table, et seulement sur décision de l'agence pour les biens.

### Ce qui est déjà correct

Vérifié, rien à faire :

- Les six envois de courriel sont enveloppés dans `try/catch` avec `report()`,
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
tests Laravel s'exécutent sur les trois branches et passent, l'audit des
dépendances et le contrôle du flux des branches s'y ajoutent. Depuis le
1er octobre 2026, les protections de branche sont posées dans GitHub : ces
cinq checks bloquent toute fusion vers `preprod` et `master`, administrateurs
compris (`docs/BRANCHES_ET_DEPLOIEMENT.md`). Un check supplémentaire vient de
SonarCloud, hors GitHub Actions, sur la qualité du code ; il est informatif,
absent des règles, et sa « Quality Gate » échoue au 1er octobre 2026.

## 5. Les branches

Trois branches permanentes chez `acs`, alignées sur le même commit :

| Branche | Rôle |
|---|---|
| `dev` | La branche de travail |
| `preprod` | Préproduction |
| `master` | **La production.** C'est depuis elle que le site se déploie. |

Le flux `dev` → `preprod` → `master` est obligatoire, et la mise en ligne passe
par `./tools/verifier-avant-deploiement.sh` : voir
`docs/BRANCHES_ET_DEPLOIEMENT.md`.

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

## 6. Lancement définitif : état au 6 octobre 2026

Le site d'essai suit la procédure de `PREMIER_DEPLOIEMENT.md`. Le lancement
sur le domaine définitif demande en plus ce qui suit. **Aucun de ces points
n'est du code, sauf le retrait du contenu, préparé ci-dessus.**

| Prérequis | État vérifié | Ce qui manque, et à qui le demander |
|---|---|---|
| Domaine, DNS, HTTPS | `sci4k.com` sert toujours le WordPress de SCI 4K ; **pré-production en service sur `https://nouveau.sci4k.com`** (certificat Let's Encrypt, `APP_URL` posé) ; le WordPress a 11 sauvegardes hebdomadaires Plesk, la dernière le 4 octobre | la bascule : `DOMAINE_ET_BASCULE.md`, §4 ; télécharger une sauvegarde Plesk hors du serveur juste avant |
| Mentions légales | directeur de publication renseigné ; restent « [à compléter] » : RCCM, compte contribuable, hébergeur ; capital social absent de la page | RCCM, compte contribuable, capital social — la direction. Hébergeur **renseigné** dans `maquettes-frontoffice/mentions-legales.html` (en ligne au prochain déploiement) |
| Politique de confidentialité | rédigée ; à faire valider | validation juridique — la direction |
| Six visuels provisoires | identifiés, fichier de destination compris : `maquettes-frontoffice/images/A-REMPLACER.md` | six photographies de l'agence, aux mêmes noms de fichier |
| Logos des partenaires | 7 organismes affichés, aucun accord écrit connu | un accord écrit par organisme, ou leur retrait |
| Courrier | Resend, expéditeur d'essai `onboarding@resend.dev` — **pas une configuration de production** ; destinataire des formulaires **vide** : aucune notification ne part ; le plan Hobby de Railway **bloque les ports SMTP 587 et 465** (`DOMAINE_ET_BASCULE.md`, §6) | **fait le 6 octobre** : `sci4k.com` vérifié dans Resend, expéditeur `noreply@sci4k.com`, réception et adresse publique `info@acsgroupe.ci` (`contact@sci4k.com` abandonnée, elle n'existait pas) ; contact et visite livrés. Reste : vérifier la boîte, nouvelle clé API Resend |
| Sentry | aucun DSN | un projet Sentry et son DSN, posés en variable (`SENTRY_LARAVEL_DSN`, `SENTRY_ENVIRONMENT=production`) |
| Contenu de démonstration | **retiré le 6 octobre** : 5 biens fictifs, 12 articles (et leurs 8 commentaires). Gardés à la demande de l'agence : 3 avis, 3 chiffres clés. Les 11 demandes de visite d'essai **supprimées** le 6 octobre ; la Villa F6 est passée de `/biens/villa-test` à `/biens/villa-f6`. Les 14 messages de contact d'essai (recette, équipe ACS) **supprimés** le 6 octobre, sauvegarde faite juste avant | rien |
| Indexation | désactivée (`autoriser_indexation = 0`) — juste tant qu'on est en essai | la cocher le jour où le domaine définitif répond, pas avant |
| Sauvegardes | `mysqldump` par `railway ssh`, éprouvé et restauré ; aucune sauvegarde Railway sur ce plan | une copie hors de ce poste (stockage de l'agence) |
| Clé d'application | **tournée le 6 octobre** (l'ancienne était apparue dans un journal local) ; `PASSKEYS_USER_HANDLE_SECRET` désormais distinct ; l'ancienne clé reste dans `APP_PREVIOUS_KEYS` pour relire le mot de passe SMTP enregistré | retirer `APP_PREVIOUS_KEYS` une fois le mot de passe SMTP ressaisi dans *Configuration* |
| Réglages de `railway.json` | **reportés sur le service le 6 octobre 2026** | rien ; retirer le fichier au prochain déploiement |

**Le jour J, dans l'ordre :** sauvegarde ; domaine posé et vérifié en HTTPS ;
`APP_URL` sur le domaine ; contenu de démonstration retiré ; mentions légales
et photos en place ; essai d'envoi de courriel ; Sentry reçoit l'erreur de
`php artisan sentry:test` ; indexation cochée ; `robots.txt` et plan du site
vérifiés sur le domaine.
