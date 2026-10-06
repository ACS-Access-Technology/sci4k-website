<?php

namespace App\Support;

/**
 * La version de MySQL que le projet supporte, et le moyen de la comparer au
 * serveur reellement joint.
 *
 * L'ECART QUI A MOTIVE CETTE CLASSE. La CI testait MySQL 8.0 ; la production
 * tournait sur MySQL 9.4. Personne ne l'avait decide ni remarque : il a fallu
 * un audit pour le lire dans la liste des services Railway. Deux versions,
 * dont aucune n'etait encore supportee par Oracle — 8.0 a quitte le support
 * etendu le 30 avril 2026, et 9.4, version « Innovation », n'etait supportee
 * que jusqu'a la sortie de 9.5, le 21 octobre 2025.
 *
 * POURQUOI 9.7. C'est la LTS courante : cinq ans de support, trois de support
 * etendu (avril 2031, avril 2034). Oracle documente la mise a jour en place
 * d'une serie Innovation vers la LTS qui la suit — 9.4 vers 9.7 — alors que
 * revenir de 9.4 a 8.4 exigerait un export complet et un reimport. Laravel,
 * lui, n'emet un SQL different qu'en dessous de 8.0.13 : 8.0, 8.4, 9.4 et 9.7
 * recoivent exactement les memes requetes.
 *
 * On compare la serie (majeure.mineure) et non le correctif : 9.7.2 et 9.7.5
 * sont la meme LTS, que les correctifs ne changent pas de format.
 *
 * A changer ICI, et en meme temps que l'image de la CI
 * (.github/workflows/verification.yml) : la CI appelle
 * « php artisan base:verifier-version » et echoue si les deux divergent.
 */
class MoteurDeBase
{
    public const MYSQL_SUPPORTE = '9.7';

    /**
     * La version rapportee par le serveur appartient-elle a la serie supportee ?
     *
     * « 9.7.2 » oui ; « 9.70.0 » ou « 9.4.0 » non. Un suffixe de distribution
     * (« 9.7.2-log ») est tolere.
     */
    public static function mysqlSupporte(string $version): bool
    {
        return preg_match('/^'.preg_quote(self::MYSQL_SUPPORTE, '/').'(\.|$|-)/', $version) === 1;
    }
}
