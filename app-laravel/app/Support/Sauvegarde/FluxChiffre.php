<?php

namespace App\Support\Sauvegarde;

use RuntimeException;

/**
 * Chiffre et dechiffre un fichier, par blocs, avec libsodium.
 *
 * AUCUNE DEPENDANCE : ni Laravel, ni Composer. Ce fichier est aussi lu tel quel
 * par tools/dechiffrer-sauvegarde.php, pour qu'une sauvegarde se rouvre sur un
 * poste qui n'a que PHP, le jour ou l'application n'existe plus.
 *
 * FORMAT : « SCI4KBK1 », l'en-tete du flux (24 octets), puis des blocs
 * [longueur sur 4 octets][texte chiffre]. Chaque bloc est authentifie par
 * crypto_secretstream_xchacha20poly1305 : un octet modifie, un bloc retire,
 * deux blocs permutes, ou un fichier tronque — le dernier bloc porte une marque
 * de fin — font echouer le dechiffrement. Un fichier tronque ne passe donc pas
 * pour une sauvegarde complete.
 *
 * La cle fait 32 octets, fournie en base64 par SAUVEGARDE_CLE. Elle n'est
 * jamais ecrite dans un journal, et la perdre rend les sauvegardes illisibles :
 * elle se conserve HORS du serveur (voir docs/SAUVEGARDES.md).
 */
final class FluxChiffre
{
    public const MAGIE = 'SCI4KBK1';

    /** Taille d'un bloc en clair. */
    public const BLOC = 1048576;

    /** Lit la cle en base64 et refuse tout ce qui n'a pas 32 octets. */
    public static function cle(string $base64): string
    {
        $cle = base64_decode(trim($base64), true);

        if ($cle === false || strlen($cle) !== SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_KEYBYTES) {
            throw new RuntimeException('La cle de sauvegarde doit etre 32 octets encodes en base64.');
        }

        return $cle;
    }

    public static function chiffrer(string $entree, string $sortie, string $cle): void
    {
        $lecture = self::ouvrir($entree, 'rb');
        $ecriture = self::ouvrir($sortie, 'wb');

        [$etat, $entete] = sodium_crypto_secretstream_xchacha20poly1305_init_push($cle);
        self::ecrire($ecriture, self::MAGIE.$entete);

        $bloc = self::lire($lecture, self::BLOC);

        do {
            $suivant = self::lire($lecture, self::BLOC);
            $dernier = $suivant === '';
            $chiffre = sodium_crypto_secretstream_xchacha20poly1305_push(
                $etat,
                $bloc,
                '',
                $dernier ? SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL : SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_MESSAGE,
            );
            self::ecrire($ecriture, pack('N', strlen($chiffre)).$chiffre);
            $bloc = $suivant;
        } while (! $dernier);

        fclose($lecture);
        fclose($ecriture);
        sodium_memzero($cle);
    }

    public static function dechiffrer(string $entree, string $sortie, string $cle): void
    {
        $lecture = self::ouvrir($entree, 'rb');

        $debut = self::lire($lecture, strlen(self::MAGIE) + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES);

        if (! str_starts_with($debut, self::MAGIE)
            || strlen($debut) !== strlen(self::MAGIE) + SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_HEADERBYTES) {
            throw new RuntimeException("Ce fichier n'est pas une sauvegarde SCI4K chiffree.");
        }

        $etat = sodium_crypto_secretstream_xchacha20poly1305_init_pull(substr($debut, strlen(self::MAGIE)), $cle);
        $ecriture = self::ouvrir($sortie, 'wb');
        $fini = false;

        while (! feof($lecture) && ! $fini) {
            $longueur = self::lire($lecture, 4);

            if ($longueur === '') {
                break;
            }

            if (strlen($longueur) !== 4) {
                throw new RuntimeException('Sauvegarde tronquee : longueur de bloc incomplete.');
            }

            $taille = unpack('N', $longueur)[1];

            if ($taille < SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_ABYTES || $taille > self::BLOC + 64) {
                throw new RuntimeException('Sauvegarde corrompue : taille de bloc invalide.');
            }

            $chiffre = self::lire($lecture, $taille);

            if (strlen($chiffre) !== $taille) {
                throw new RuntimeException('Sauvegarde tronquee : bloc incomplet.');
            }

            $clair = sodium_crypto_secretstream_xchacha20poly1305_pull($etat, $chiffre);

            if ($clair === false) {
                throw new RuntimeException('Dechiffrement impossible : mauvaise cle, ou fichier altere.');
            }

            [$texte, $marque] = $clair;
            self::ecrire($ecriture, $texte);
            $fini = $marque === SODIUM_CRYPTO_SECRETSTREAM_XCHACHA20POLY1305_TAG_FINAL;
        }

        fclose($lecture);
        fclose($ecriture);
        sodium_memzero($cle);

        if (! $fini) {
            throw new RuntimeException('Sauvegarde tronquee : la marque de fin est absente.');
        }
    }

    /** @return resource */
    private static function ouvrir(string $chemin, string $mode)
    {
        $f = @fopen($chemin, $mode);

        if ($f === false) {
            throw new RuntimeException("Impossible d'ouvrir le fichier : $chemin");
        }

        return $f;
    }

    /** Lit exactement $n octets, sauf en fin de fichier. */
    private static function lire($f, int $n): string
    {
        $tampon = '';

        while (strlen($tampon) < $n && ! feof($f)) {
            $morceau = fread($f, $n - strlen($tampon));

            if ($morceau === false || $morceau === '') {
                break;
            }

            $tampon .= $morceau;
        }

        return $tampon;
    }

    private static function ecrire($f, string $donnees): void
    {
        if ($donnees !== '' && fwrite($f, $donnees) !== strlen($donnees)) {
            throw new RuntimeException('Ecriture incomplete : disque plein ou fichier ferme.');
        }
    }
}
