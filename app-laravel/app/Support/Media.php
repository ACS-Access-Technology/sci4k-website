<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * L'adresse publique d'un visuel, qu'il vienne du depot ou d'un televersement.
 *
 * Le projet melange deux origines, et elles ne se servent pas de la meme façon.
 *
 * Les visuels LIVRES AVEC LE DEPOT vivent dans public/images : ce sont des
 * fichiers de maquettes-frontoffice/ deposes par tools/sync-frontoffice.sh, a
 * la construction. asset() les sert.
 *
 * Les fichiers TELEVERSES depuis l'administration portent le prefixe
 * « storage/ » et vivent sur le disque public — sur le serveur, ou dans un
 * stockage objet selon la configuration. C'est au DISQUE de dire ou il les
 * range : asset() prefixerait par APP_URL et designerait un fichier local
 * inexistant. Le site afficherait alors des cadres vides sans qu'une seule
 * erreur ne soit levee, ce qui est la panne la plus longue a diagnostiquer.
 */
class Media
{
    /** Le prefixe que portent, en base, les chemins des fichiers televerses. */
    public const PREFIXE_TELEVERSE = 'storage/';

    public static function url(?string $chemin): ?string
    {
        if (! $chemin) {
            return null;
        }

        if (! str_starts_with($chemin, self::PREFIXE_TELEVERSE)) {
            return asset($chemin);
        }

        $adresse = Storage::disk('public')->url(substr($chemin, strlen(self::PREFIXE_TELEVERSE)));

        // L'adresse doit etre ABSOLUE. Un disque local sans clef « url »
        // configuree rend « /storage/… », ce qui suffit dans un navigateur mais
        // ne pointe nulle part dans un courriel — l'alerte de demande de visite,
        // l'avis de nouveau commentaire — ni dans le plan du site. Le
        // destinataire verrait un cadre vide, sans qu'aucune erreur ne soit
        // levee cote serveur.
        return str_starts_with($adresse, 'http') ? $adresse : url($adresse);
    }
}
