# Image du site SCI4K, pour toute plateforme a conteneurs.
#
# Ecrite pour Railway, mais rien ici ne lui est propre : la meme image tourne
# sur Fly.io, Render, Koyeb ou un VPS. C'est deliberé — une plateforme qui
# decoit se remplace alors sans rien reecrire.
#
# POURQUOI UNE IMAGE PLUTOT QUE LA DETECTION AUTOMATIQUE : composer.json vit
# dans app-laravel/, tandis que tools/sync-frontoffice.sh a besoin de
# maquettes-frontoffice/, a la racine. Restreindre la racine du depot casserait
# la synchronisation ; construire depuis la racine avec un Dockerfile la garde.
#
# FrankenPHP sert Laravel sans nginx ni php-fpm a assembler : un processus, une
# configuration. Pour un environnement de test, c'est autant de pieces en moins
# a regler et a surveiller.

# --- Les dependances PHP -----------------------------------------------------
#
# Cette etape vient EN PREMIER, et pas seulement pour le cache de Docker :
# l'etape suivante a besoin de vendor/. Voir pourquoi juste en dessous.
FROM composer:2 AS dependances

WORKDIR /app
COPY app-laravel/composer.json app-laravel/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction \
        --no-scripts --prefer-dist --ignore-platform-reqs

# --- Les ressources front ----------------------------------------------------
#
# Node ne sert QU'A CELA. Le garder dans l'image finale y ajouterait une
# centaine de megaoctets et une surface d'attaque, pour un outil dont plus rien
# n'a besoin une fois les fichiers produits.
FROM node:20-alpine AS ressources

WORKDIR /construction
COPY app-laravel/package.json app-laravel/package-lock.json ./
# --ignore-scripts : un paquet installe peut executer du code arbitraire par ses
# scripts de cycle de vie. Verifie sur ce projet : le build n'en a besoin
# d'aucun.
RUN npm ci --ignore-scripts

COPY app-laravel/vite.config.js ./
COPY app-laravel/resources ./resources

# TAILWIND SCANNE AUSSI VENDOR/, et l'oublier casse la pagination.
#
# resources/css/app.css declare deux sources : ../views, et les gabarits de
# pagination de Laravel, qui vivent dans vendor/. Sans eux, Tailwind ne voit
# jamais leurs classes et produit un CSS qui n'en contient aucune — la
# pagination s'affiche alors sans style sur toutes les listes du backoffice,
# sans qu'une seule erreur ne soit levee a la construction.
COPY --from=dependances \
     /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
     ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN npm run build

# --- L'application -----------------------------------------------------------
FROM dunglas/frankenphp:php8.3

# Les extensions exigees par composer et ses dependances.
#
# gd N'EST PLUS FACULTATIVE : App\Support\ImageTeleversee s'en sert pour
# reduire et convertir chaque visuel televerse. Sans elle le depot fonctionne
# encore — le fichier part tel quel, par repli deliberé — mais une fiche de
# bien peut alors servir vingt megaoctets de photos.
#
# exif l'accompagne, et pour une raison precise : un telephone enregistre la
# photo telle que le capteur l'a lue et joint une consigne de rotation. GD
# ignore cette consigne et la perd a la reecriture. Sans exif, une photo prise
# en portrait se publierait donc COUCHEE — la conversion aggraverait ce que le
# stockage brut laissait passer.
RUN install-php-extensions pdo_mysql pdo_sqlite gd exif intl zip opcache

# rsync : tools/sync-frontoffice.sh s'en sert pour deposer les ressources du
# site statique. Il est fourni sur macOS et sur les executeurs GitHub, mais pas
# dans cette image — la construction echouait ici, et aucune relecture de
# chemins n'aurait pu le prevoir.
RUN apt-get update \
    && apt-get install -y --no-install-recommends rsync \
    && rm -rf /var/lib/apt/lists/*

# Les reglages de PHP. L'image n'en charge aucun par defaut, et les valeurs
# d'usine plafonnent un televersement a 2 Mo — sous ce que les ecrans
# d'administration declarent accepter. Voir docker/php.ini.
COPY docker/php.ini /usr/local/etc/php/conf.d/sci4k.ini

# Le serveur, avec les en-tetes de cache. L'image n'en pose AUCUN sur les
# fichiers statiques : chaque visite redemandait donc les images pour
# s'entendre repondre « inchangee ». Voir docker/Caddyfile, qui reprend la
# configuration d'origine et n'y ajoute que ces en-tetes.
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY --from=dependances /app/vendor ./app-laravel/vendor

# Trois dossiers NOMMES, et non « COPY . . ».
#
# Copier tout le contexte faisait de .dockerignore la seule barriere entre le
# poste qui deploie et l'image : un fichier qu'il oubliait partait en
# production. C'est arrive — la base SQLite de developpement, table des
# comptes comprise, a voyage dans chaque image jusqu'a ce que SonarCloud
# signale la ligne (docker:S6470).
#
# Une liste de ce qu'on VEUT ne depend plus de ce qu'on a pense a exclure : les
# notes .md de la racine, railway.json, vercel.json ou un fichier de
# sauvegarde pose la par megarde n'y entrent pas, quoi que dise .dockerignore.
# Celui-ci reste utile A L'INTERIEUR de ces dossiers — vendor/, .env, storage/,
# la base locale — et continue de s'appliquer ici.
#
# Ce que chacun apporte, verifie contre la suite du fichier :
#   app-laravel/            l'application ; vendor/ est deja pose au-dessus et
#                           ecarte du contexte, donc pas ecrase ici.
#   maquettes-frontoffice/  la source que sync-frontoffice.sh depose dans
#                           public/, quelques lignes plus bas.
#   tools/                  ce script-la, et celui du demarrage.
# Aucun code execute en production ne lit hors d'app-laravel : verifie par une
# recherche des chemins en « ../ » dans app/, config/, routes/, bootstrap/,
# les vues, les migrations et les seeders.
COPY app-laravel ./app-laravel
COPY maquettes-frontoffice ./maquettes-frontoffice
COPY tools ./tools

# Les scripts de composer ont ete differes plus haut : le code n'etait pas
# encore la. L'autoloader se recompose maintenant qu'il l'est.
RUN cd app-laravel && composer dump-autoload --optimize --no-dev

COPY --from=ressources /construction/public/build ./app-laravel/public/build

# Depose dans public/ les styles, le script, les images et les pages non encore
# portees en Blade.
#
# Le chmod n'est pas superflu. Git enregistre bien ce script en 100755, mais
# « railway up » televerse le DOSSIER DE TRAVAIL, pas le depot : depuis
# Windows, ou NTFS n'a pas de bit d'execution, le mode se perd en route et la
# construction echouait sur « Permission denied ». Un deploiement partant d'un
# clone git — la liaison GitHub, ou une machine Unix — n'a jamais montre le
# defaut, ce qui le rendait invisible jusqu'ici.
#
# Et « chmod +x » plutot que « sh ./tools/... » : le script porte un shebang
# bash et se sert de tableaux, que le dash de Debian ne comprend pas.
RUN chmod +x ./tools/sync-frontoffice.sh && ./tools/sync-frontoffice.sh

# Le disque du conteneur est jetable : storage/ doit venir d'un volume monte
# par la plateforme, sinon les images televersees disparaissent au redeploiement.
# Ces dossiers sont crees ici pour que l'application demarre meme sans volume,
# le temps d'un essai.
RUN mkdir -p app-laravel/storage/framework/{cache,sessions,views} \
             app-laravel/storage/app/public \
             app-laravel/storage/logs \
    # 775 et non 777 : le conteneur tourne sous root, qui possede ces dossiers,
    # et le volume monte par la plateforme l'est aussi. Donner l'ecriture au
    # reste du monde n'ouvrait donc rien d'utile — seulement un chemin de plus.
    && chmod -R 775 app-laravel/storage app-laravel/bootstrap/cache

ENV SERVER_NAME=:8080
ENV SERVER_ROOT=/app/app-laravel/public

EXPOSE 8080

COPY tools/demarrer-conteneur.sh /usr/local/bin/demarrer
RUN chmod +x /usr/local/bin/demarrer

CMD ["/usr/local/bin/demarrer"]
