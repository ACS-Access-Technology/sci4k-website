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

# Les extensions exigees par composer et ses dependances. gd et intl ne sont pas
# utilisees par le code — le recadrage des images se fait dans le navigateur —
# mais Laravel les attend sur certains chemins, et leur cout est negligeable.
RUN install-php-extensions pdo_mysql pdo_sqlite gd intl zip opcache

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

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY --from=dependances /app/vendor ./app-laravel/vendor

COPY . .

# Les scripts de composer ont ete differes plus haut : le code n'etait pas
# encore la. L'autoloader se recompose maintenant qu'il l'est.
RUN cd app-laravel && composer dump-autoload --optimize --no-dev

COPY --from=ressources /construction/public/build ./app-laravel/public/build

# Depose dans public/ les styles, le script, les images et les pages non encore
# portees en Blade.
RUN ./tools/sync-frontoffice.sh

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
