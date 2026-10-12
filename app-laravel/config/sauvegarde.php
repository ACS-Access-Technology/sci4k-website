<?php

/**
 * Les sauvegardes externes. Voir docs/SAUVEGARDES.md.
 *
 * INERTE tant que SAUVEGARDE_ACTIVE n'est pas vrai : rien n'est planifie,
 * aucune connexion n'est ouverte. Les secrets (cle de chiffrement, jetons du
 * stockage) ne vivent que dans l'environnement du service, jamais dans le depot.
 */
return [

    'active' => filter_var(env('SAUVEGARDE_ACTIVE', false), FILTER_VALIDATE_BOOLEAN),

    /** 32 octets en base64 : php -r "echo base64_encode(random_bytes(32)), PHP_EOL;" */
    'cle' => env('SAUVEGARDE_CLE'),

    'bucket' => env('SAUVEGARDE_S3_BUCKET'),

    /** Prefixe des objets, pour partager un bucket sans melanger. */
    'prefixe' => env('SAUVEGARDE_PREFIXE', 'sci4k'),

    /** Heure UTC. 3 h 20 : entre l'agregation (3 h 10) et la purge (3 h 40) du planificateur existant, sans les croiser. */
    'heure' => env('SAUVEGARDE_HEURE', '03:20'),

    /** Adresse de surveillance « dead man's switch » : prevenir quand le signal n'arrive plus. */
    'url_surveillance' => env('SAUVEGARDE_PING_URL'),

];
