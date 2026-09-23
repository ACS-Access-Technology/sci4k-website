<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ce qui arrive a une image entre le formulaire et le disque.
 *
 * LE DEFAUT CORRIGE. Les cinq ecrans qui acceptent un visuel appelaient
 * `$fichier->store(...)` : le fichier partait sur le disque TEL QUEL. Un bien
 * accepte dix photos de 2 Mo — une fiche pouvait donc servir vingt megaoctets,
 * et ce sont precisement les photos de l'agence, encore attendues, qui les
 * auraient apportes. Optimiser les images de maquette pendant que cette porte
 * restait ouverte aurait ete soigner le symptome le plus leger.
 *
 * TROIS TRAITEMENTS, dans cet ordre, et l'ordre compte :
 *
 *   1. REDRESSER. Un telephone n'ecrit pas la photo dans le sens ou il la
 *      montre : il l'enregistre telle que le capteur l'a lue et joint une
 *      consigne de rotation dans l'EXIF. GD ignore cette consigne et la perd a
 *      la reecriture — convertir sans redresser d'abord, c'est publier a
 *      l'envers une photo qui s'affichait droite. La conversion AGGRAVE donc
 *      ce que le stockage brut laissait passer, d'ou ce premier temps.
 *
 *   2. REDUIRE. Au-dela de 1600 pixels de large, aucun ecran du site n'affiche
 *      un pixel de plus : la banniere la plus large fait 1600, les vignettes
 *      bien moins. Ce qui depasse se telecharge pour etre jete.
 *
 *   3. CONVERTIR en WebP, ou la meme image pese un quart a un tiers de moins
 *      qu'en JPEG a qualite comparable.
 *
 * L'EXIF disparait au passage, et c'est voulu : un appareil y grave la POSITION
 * GPS. Publier l'emplacement exact d'une villa a cote de son prix est un risque
 * pour qui l'habite, et rien dans le site ne l'aurait signale.
 *
 * EN CAS D'ECHEC, le fichier est depose sans traitement plutot que perdu. Une
 * image trop grande vaut mieux qu'un televersement qui disparait sans dire
 * pourquoi : l'editeur, lui, ne saurait pas que la conversion a echoue.
 */
class ImageTeleversee
{
    /** Au-dela, on telecharge des pixels que le site n'affichera jamais. */
    public const LARGEUR_MAX = 1600;

    /** Compromis usuel : au-dessus le gain fond, en dessous les aplats marbrent. */
    public const QUALITE = 82;

    /**
     * Depose une image televersee, allegee quand c'est possible.
     *
     * @return string Le chemin relatif au disque public, comme `store()`.
     */
    public static function deposer(UploadedFile $fichier, string $dossier, string $disque = 'public'): string
    {
        $traitee = self::convertir($fichier);

        if ($traitee === null) {
            return $fichier->store($dossier, $disque);
        }

        $chemin = trim($dossier, '/').'/'.Str::random(40).'.webp';
        Storage::disk($disque)->put($chemin, $traitee);

        return $chemin;
    }

    /**
     * Le contenu WebP redresse et reduit, ou null si l'on ne sait pas faire.
     *
     * Renvoyer null n'est pas une erreur : un SVG, un GIF anime ou une image
     * qu'aucun decodeur du serveur ne comprend doivent passer intacts.
     */
    protected static function convertir(UploadedFile $fichier): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $chemin = $fichier->getRealPath();

        if ($chemin === false || ! is_readable($chemin)) {
            return null;
        }

        // getimagesize repond null sur un SVG comme sur un fichier corrompu :
        // dans les deux cas on ne touche a rien.
        $infos = @getimagesize($chemin);

        if ($infos === false) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($chemin));

        if ($image === false) {
            return null;
        }

        try {
            $image = self::redresser($image, $chemin, (int) $infos[2]);
            $image = self::reduire($image);

            ob_start();
            $ok = imagewebp($image, null, self::QUALITE);
            $contenu = (string) ob_get_clean();

            return $ok && $contenu !== '' ? $contenu : null;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Applique la consigne de rotation que l'appareil a laissee dans l'EXIF.
     *
     * Sans `exif`, on renvoie l'image inchangee : mieux vaut une photo parfois
     * couchee qu'un televersement refuse. L'extension est installee par le
     * Dockerfile — voir la ligne install-php-extensions.
     */
    protected static function redresser(\GdImage $image, string $chemin, int $type): \GdImage
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($chemin);
        $orientation = (int) ($exif['Orientation'] ?? 0);

        // Seuls ces trois cas sont de simples rotations. Les orientations
        // miroir (2, 4, 5, 7) sont assez rares sur un appareil photo pour ne
        // pas justifier le code qui les traiterait.
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $pivotee = imagerotate($image, $angle, 0);

        if ($pivotee === false) {
            return $image;
        }

        imagedestroy($image);

        return $pivotee;
    }

    /** Ramene la largeur sous LARGEUR_MAX, en gardant les proportions. */
    protected static function reduire(\GdImage $image): \GdImage
    {
        $largeur = imagesx($image);
        $hauteur = imagesy($image);

        if ($largeur <= self::LARGEUR_MAX) {
            return $image;
        }

        $cible = (int) round($hauteur * self::LARGEUR_MAX / $largeur);
        $reduite = imagescale($image, self::LARGEUR_MAX, $cible);

        if ($reduite === false) {
            return $image;
        }

        imagedestroy($image);

        return $reduite;
    }
}
