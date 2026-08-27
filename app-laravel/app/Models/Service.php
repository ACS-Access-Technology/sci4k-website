<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use TraduitParColonnes;

    /**
     * Prefixe des visuels televerses, tel qu'il figure dans `image_source`.
     * Meme role que Article::DOSSIER_COUVERTURES : il distingue un fichier
     * depose par l'administration, qu'on peut effacer, d'un visuel du site
     * statique, qu'on ne doit jamais toucher.
     */
    public const DOSSIER_COUVERTURES = 'storage/services';

    protected $fillable = [
        'slug', 'categorie_id', 'ordre', 'visible',
        'nom_fr', 'nom_en', 'accroche_fr', 'accroche_en',
        'description_fr', 'description_en',
        'atout1_fr', 'atout1_en', 'atout2_fr', 'atout2_en', 'atout3_fr', 'atout3_en',
        'libelle_bouton_fr', 'libelle_bouton_en',
        'icone_svg', 'image_source',
    ];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /** Nom du service dans la langue demandee, francais par defaut. */
    public function nom(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('nom', $langue);
    }

    /** Phrase courte affichee sur la tuile. */
    public function accroche(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('accroche', $langue);
    }

    /** Texte long affiche dans la fiche du service. */
    public function description(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('description', $langue);
    }

    /** Libelle du bouton d'appel a l'action, repli sur une valeur traduite. */
    public function libelleBouton(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle_bouton', $langue) ?: __('En savoir plus');
    }

    /**
     * Les atouts, dans l'ordre, sans les emplacements laisses vides.
     *
     * Trois colonnes plutot qu'une table dediee : le site en affiche
     * exactement trois depuis toujours, et une table pour trois valeurs fixes
     * couterait une jointure a chaque affichage pour aucun gain.
     */
    public function atouts(string $langue = 'fr'): array
    {
        return array_values(array_filter([
            $this->texteDansLaLangue('atout1', $langue),
            $this->texteDansLaLangue('atout2', $langue),
            $this->texteDansLaLangue('atout3', $langue),
        ], fn ($a) => $a !== ''));
    }

    /**
     * Adresse de l'image du service, ou null s'il n'en a pas.
     *
     * Deux origines cohabitent dans `image_source`, et c'est voulu :
     *
     *   - `images/services/foncier.jpg` pour les six services repris du site,
     *     resolu depuis images.css par le script d'extraction ;
     *   - `storage/services/…` pour une image televersee depuis
     *     l'administration.
     *
     * L'ecran d'administration doit montrer l'image REELLEMENT servie, d'ou
     * qu'elle vienne : sans le premier cas, il annoncerait « aucune image »
     * pour les six services alors que le site en affiche six.
     */
    public function urlImage(): ?string
    {
        if (! $this->image_source) {
            return null;
        }

        return asset($this->image_source);
    }

    /**
     * L'image vient-elle d'un televersement, ou du site statique ?
     *
     * La distinction commande deux choses : seul un fichier televerse peut
     * etre efface, et seul lui merite un style en ligne sur la page publique —
     * une image du site statique est deja servie par sa regle CSS, laquelle
     * fournit en prime une variante allegee sur mobile.
     */
    public function imageTeleversee(): bool
    {
        return $this->cheminEffaçable() !== null;
    }

    /**
     * Chemin du fichier a effacer sur le disque public, ou null.
     *
     * Le seul controle du prefixe ne suffit pas : « storage/services/../
     * couvertures/article.jpg » commence bien par le prefixe attendu, et
     * Flysystem le resout sans broncher en « couvertures/article.jpg », qui
     * est dans la racine du disque. Un editeur pouvait donc detruire la
     * couverture d'un article depuis le formulaire d'un service — verifie par
     * un test, qui echouait avant cette garde.
     *
     * On refuse donc tout segment de remontee, et on exige que ce qui reste
     * tienne dans le dossier des services. Le prefixe est une intention, le
     * chemin resolu est le fait.
     */
    public function cheminEffaçable(): ?string
    {
        $source = (string) $this->image_source;

        if (! str_starts_with($source, self::DOSSIER_COUVERTURES.'/')) {
            return null;
        }

        $relatif = substr($source, strlen('storage/'));

        if (in_array('..', explode('/', $relatif), true)) {
            return null;
        }

        return $relatif;
    }

    /**
     * Le visuel que le site statique sert pour ce service, ou null.
     *
     * Resolu depuis images.css, comme le fait le script d'extraction : le nom
     * du fichier ne se deduit PAS du slug — le service « gestion » s'appuie sur
     * gestion-location.jpg. La feuille de style reste la source unique.
     *
     * Sert de repli quand l'editeur retire une image televersee. Sans lui,
     * `image_source` retombait a null et l'ecran d'administration annonçait
     * « aucune image » pendant que la page publique continuait d'afficher le
     * visuel par la classe CSS service-bg-{slug} — l'ecran se remettait a
     * mentir sur l'etat du site, ce que le Ruling L declarait pire qu'une
     * fonction manquante.
     */
    public function imageDuSiteStatique(): ?string
    {
        $feuille = public_path('assets/images.css');

        if (! is_file($feuille)) {
            return null;
        }

        $motif = '/--img-service-'.preg_quote($this->slug, '/').':\s*url\([\'"]?[^\'")]*?(images\/[^\'")]+)/';

        return preg_match($motif, file_get_contents($feuille), $trouve)
            ? $trouve[1]
            : null;
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
