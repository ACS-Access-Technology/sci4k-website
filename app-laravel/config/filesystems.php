<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /*
         * Le disque des fichiers televerses depuis le backoffice : couvertures
         * d'articles, photos de biens, visuels de services, photos de profil.
         *
         * Il vit sur le serveur par defaut, servi par le lien public/storage.
         * Mais une plateforme sans systeme de fichiers persistant — Vercel, ou
         * le disque est en lecture seule hors d'un /tmp ephemere — perdrait
         * chaque image a la mise en ligne suivante. STOCKAGE_PUBLIC_DISTANT le
         * bascule alors vers un stockage objet compatible S3.
         *
         * LES TREIZE POINTS DU CODE QUI ECRIVENT OU EFFACENT UNE IMAGE NE
         * CHANGENT PAS : ils passent tous par Storage::disk('public'), et c'est
         * le contrat Filesystem de Laravel qui rend ce deplacement possible.
         * Seule la mediatheque faisait exception, en parcourant le disque
         * physiquement ; elle passe desormais par la meme abstraction.
         */
        'public' => filter_var(env('STOCKAGE_PUBLIC_DISTANT', false), FILTER_VALIDATE_BOOLEAN) ? [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            // Sans visibilite publique, les images seraient ecrites puis
            // refusees a la lecture : le site afficherait des cadres vides.
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
