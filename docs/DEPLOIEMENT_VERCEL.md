# Mise en ligne sur Vercel

Ce document ne remplace pas `MISE_EN_LIGNE.md`, qui décrit le déploiement sur
un hébergement ordinaire. Il décrit ce que Vercel change, et **seulement cela**.

Vercel ne fournit ni disque persistant, ni base de données, ni ligne de
commande. Trois services doivent donc être souscrits à côté, et c'est le vrai
coût de ce choix.

---

## 1. Ce qu'il faut souscrire

| Service | Pourquoi | Ordre de prix |
|---|---|---|
| **Vercel** | L'application | 0 € (Hobby) à 20 $/mois (Pro) |
| **Une base MySQL 8** managée | Vercel n'en héberge pas | 15–25 €/mois |
| **Un stockage objet S3** | Le disque de Vercel est en lecture seule | ~1 €/mois |

Pour le stockage, **Cloudflare R2** est le plus adapté : compatible S3, pas de
frais de sortie — ce qui compte pour un site qui sert des images — et gratuit
en dessous de 10 Go.

Pour la base, tout MySQL 8 accessible depuis l'extérieur convient. **Choisir la
région la plus proche de celle des fonctions Vercel** : chaque interaction du
backoffice fait un aller-retour Livewire, et chaque aller-retour interroge la
base. Une base sur un autre continent se sent immédiatement.

## 2. Les variables d'environnement

À poser dans la console Vercel, pour *Production* comme pour *Preview*.

```
APP_NAME=SCI4K
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # php artisan key:generate --show, puis recopier
APP_URL=https://…   # l'adresse réelle du site

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=…
DB_PORT=3306
DB_DATABASE=…
DB_USERNAME=…
DB_PASSWORD=…

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

# Vercel termine le TLS devant PHP. Sans cette ligne, isSecure() répond faux :
# les liens repartent en http, le cookie de session n'est jamais marqué Secure,
# et la limitation de débit compte toutes les visites sur une seule adresse.
TRUSTED_PROXIES=*

# Les fichiers téléversés partent vers le stockage objet. Sans cette bascule,
# ils seraient écrits dans un /tmp éphémère et disparaîtraient au déploiement
# suivant — sans qu'aucune erreur ne soit levée.
STOCKAGE_PUBLIC_DISTANT=true
AWS_ACCESS_KEY_ID=…
AWS_SECRET_ACCESS_KEY=…
AWS_BUCKET=…
AWS_DEFAULT_REGION=auto
AWS_ENDPOINT=https://<compte>.r2.cloudflarestorage.com
AWS_URL=https://…            # l'adresse publique du bucket
AWS_USE_PATH_STYLE_ENDPOINT=true

# Déclencheur des tâches d'entretien. Vercel envoie ce jeton en en-tête
# Authorization sur la route de cron. Sans lui, la route n'existe pas.
CRON_SECRET=                 # une chaîne longue et aléatoire

MAIL_MAILER=smtp
MAIL_HOST=…
MAIL_PORT=587
MAIL_USERNAME=…
MAIL_PASSWORD=…
MAIL_FROM_ADDRESS=…

SENTRY_LARAVEL_DSN=          # facultatif
```

**`LOG_CHANNEL` et `VIEW_COMPILED_PATH` ne sont pas à poser** : le point
d'entrée `app-laravel/api/preparer-environnement.php` s'en charge, parce qu'ils
doivent l'être avant le démarrage du framework.

## 3. Les migrations

**Elles ne tournent pas à la construction**, délibérément. Celle-ci s'exécute
sur une machine jetable, sans garantie d'atteindre la base, et chaque
déploiement d'aperçu toucherait alors la base de production.

À lancer à la main, une fois, depuis un poste connecté à la base :

```bash
cd app-laravel
DB_HOST=… DB_DATABASE=… DB_USERNAME=… DB_PASSWORD=… php artisan migrate --force
```

Puis à rejouer manuellement après chaque déploiement qui ajoute une migration.

## 4. Ce qui change dans le code, et ce qui ne change pas

**Ne change pas** : les treize points qui écrivent ou effacent une image. Ils
passent tous par `Storage::disk('public')`, et c'est ce disque qui bascule —
le contrat `Filesystem` de Laravel rend le déplacement invisible au code
métier.

**A changé** :

- La médiathèque, seul écran qui parcourait le disque physiquement. Elle passe
  désormais par la même abstraction.
- Les adresses des visuels, centralisées dans `App\Support\Media`. Un chemin
  préfixé `storage/` est demandé au disque ; les autres restent servis par
  `asset()`, ce sont des fichiers du dépôt.
- Le déclencheur d'entretien, `/_taches-planifiees`, gardé par `CRON_SECRET`.
  Il répond **404** quand le jeton manque, et non 401 : un 401 confirmerait que
  l'adresse existe.

## 5. Les limites à connaître

- **Démarrage à froid.** Les fonctions sont archivées après deux semaines sans
  appel en production, quarante-huit heures en aperçu. Le premier appel suivant
  paie une seconde de plus.
- **Les vues Blade se compilent à chaque instance froide**, dans `/tmp`. C'est
  un choix : les compiler à la construction les déposerait là où l'exécution ne
  les lit pas.
- **Trente secondes par requête** au plus (`maxDuration`). Suffisant ici, aucune
  opération longue n'existe.
- **Aucune tâche en file d'attente.** Les huit envois de courriel sont
  synchrones, ce qui tombe bien : un travailleur de file d'attente n'aurait pas
  où tourner.

## 6. Revenir en arrière

Le portage est **réversible sans toucher au code**. Sur un hébergement
ordinaire, il suffit de ne pas poser `STOCKAGE_PUBLIC_DISTANT` : le disque
redevient local, `tools/deployer.sh` reprend la main, et la ligne de cron
remplace la route. `vercel.json` et `app-laravel/api/` sont alors inertes.
