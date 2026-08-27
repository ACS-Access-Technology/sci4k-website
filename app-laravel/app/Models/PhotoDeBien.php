<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une photo de la galerie d'un bien.
 *
 * La premiere par le rang sert de photo principale : une colonne « principale »
 * a cote du rang aurait permis d'avoir deux principales, ou aucune.
 *
 * Les six biens repris du site n'ont AUCUNE photo — ce sont des illustrations
 * dessinees. L'absence de photo est donc le cas normal du catalogue au
 * demarrage, pas une exception a traiter en bas de page.
 */
class PhotoDeBien extends Model
{
    protected $table = 'photos_de_bien';

    protected $fillable = ['bien_id', 'fichier', 'texte_alternatif_fr', 'texte_alternatif_en', 'ordre'];

    /** Dossier de stockage des photos de biens. */
    public const DOSSIER = 'storage/biens';

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    /** Texte alternatif dans la langue demandee, avec repli sur le francais. */
    public function texteAlternatif(string $langue = 'fr'): string
    {
        $valeur = $langue === 'en' ? $this->texte_alternatif_en : $this->texte_alternatif_fr;

        return (string) ($valeur ?: $this->texte_alternatif_fr);
    }

    /**
     * Chemin effacable sur le disque, ou null.
     *
     * Meme controle qu'au lot 2 pour les images de service : le seul prefixe
     * laisse passer « storage/biens/../autre.jpg », que Flysystem resout en un
     * fichier bien reel hors du dossier.
     */
    public function cheminEffacable(): ?string
    {
        $source = (string) $this->fichier;

        if (! str_starts_with($source, self::DOSSIER.'/')) {
            return null;
        }

        $relatif = substr($source, strlen('storage/'));

        return in_array('..', explode('/', $relatif), true) ? null : $relatif;
    }
}
