# Mise en ligne d'essai sur Railway

Pour **éprouver le site en conditions réelles**, sans engagement et sans
toucher au code. La mise en production, elle, suit `MISE_EN_LIGNE.md`.

Railway offre 5 $ de crédit sur 30 jours, sans carte bancaire, puis 5 $/mois.

## Pourquoi Railway plutôt que Vercel

Railway fournit **MySQL, un disque persistant et un cron**. Le projet s'y
déploie donc tel quel : aucune bascule vers un stockage objet, aucune route de
cron, aucun point d'entrée particulier.

Tout ce qui a été écrit pour Vercel — `vercel.json`, `app-laravel/api/`,
`STOCKAGE_PUBLIC_DISTANT` — reste dans le dépôt et **demeure inerte** : rien de
cela ne s'active sans les variables correspondantes.

## Ce que fait l'image

Le `Dockerfile` est à la racine, et n'a rien de propre à Railway : la même
image tourne sur Fly.io, Render, Koyeb ou un VPS. Une plateforme qui déçoit se
remplace alors sans rien réécrire.

Il construit en deux temps. Node produit les ressources front dans une première
étape, puis **disparaît** : le garder ajouterait une centaine de mégaoctets et
une surface d'attaque à une image qui n'en a plus besoin. La seconde étape
installe les dépendances PHP de production, copie le code, et dépose les
ressources du site statique.

FrankenPHP sert l'application : un processus, une configuration, ni nginx ni
php-fpm à assembler.

## Les étapes

### 1. Créer le projet

Sur [railway.com](https://railway.com) : *New Project* → *Deploy from GitHub
repo* → choisir `ACS-Access-Technology/sci4k-website`.

Railway détecte le `Dockerfile` et le `railway.json`. **Ne pas définir de
racine** : la construction a besoin de `maquettes-frontoffice/`, qui vit à la
racine du dépôt.

### 2. Ajouter MySQL

*New* → *Database* → *Add MySQL*. Railway injecte alors les variables de
connexion dans le projet.

### 3. Poser les variables

Dans les *Variables* du service web :

```
APP_NAME=SCI4K
APP_ENV=production
APP_DEBUG=false
APP_KEY=                     # php artisan key:generate --show, puis recopier
APP_URL=https://…            # l'adresse que Railway attribue

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_STACK=stderr             # Railway collecte la sortie standard

# Railway termine le TLS devant l'application. Sans cette ligne, isSecure()
# répond faux : les liens repartent en http, le cookie de session n'est jamais
# marqué Secure, et la limitation de débit compte toutes les visites sur une
# seule adresse — les quatre formulaires publics fermeraient au cinquième envoi.
TRUSTED_PROXIES=*

MAIL_MAILER=log              # pour un essai ; SMTP réel en production
```

La syntaxe `${{MySQL.MYSQLHOST}}` est celle de Railway : elle référence le
service MySQL sans recopier les identifiants.

### 4. Le volume des fichiers téléversés

*Settings* → *Volumes* → monter sur `/app/app-laravel/storage/app/public`.

**Sans volume, les images ajoutées depuis le backoffice disparaissent au
redéploiement suivant.** Le conteneur démarre quand même — c'est prévu, le
temps d'un essai — mais rien ne signalera la perte.

### 5. Le cron

*New* → *Cron Job*, sur le même dépôt, avec la planification `10 3 * * *` et
la commande :

```bash
cd /app/app-laravel && php artisan schedule:run
```

Sans lui, l'agrégation de la fréquentation et la purge du journal ne tournent
jamais, et les deux tables croissent sans borne.

## Les migrations

Elles tournent **au démarrage du conteneur**, faute de pouvoir tourner ailleurs :
la construction de l'image n'atteint pas la base.

C'est acceptable pour un essai, et discutable en production — deux instances
qui démarrent ensemble les lanceraient en même temps. Laravel pose un verrou,
mais le jour où ce site tournera sur plusieurs instances, il faudra les sortir
de là. `MIGRER_AU_DEMARRAGE=false` les désactive.

## Vérifier que ça marche

1. `https://…/up` doit répondre 200 — c'est la sonde de Railway.
2. La page d'accueil doit afficher les visuels : sinon `sync-frontoffice.sh`
   n'a pas tourné, ou `public/build` manque.
3. Se connecter au backoffice, **téléverser une image**, puis redéployer :
   elle doit survivre. Si elle disparaît, le volume n'est pas monté.
4. `php artisan frequentation:agreger` depuis la console Railway doit répondre
   sans erreur.

## Construire l'image en local

```bash
docker build -t sci4k .
```

Rien ne dépend de Railway dans cette commande : c'est le meilleur moyen de
vérifier une modification avant de la pousser.
