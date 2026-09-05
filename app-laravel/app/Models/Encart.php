<?php

namespace App\Models;

use App\Models\Concerns\CollectionOrdonnable;
use App\Models\Concerns\JournaliseSesChangements;
use App\Models\Concerns\TraduitParColonnes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Encart d'appel a l'action — la banderole de l'accueil, aujourd'hui.
 *
 * Le slug designe l'endroit du site qui l'affiche, comme pour les services.
 */
class Encart extends Model
{
    use CollectionOrdonnable;
    use HasFactory;
    use JournaliseSesChangements;
    use TraduitParColonnes;

    protected $table = 'encarts';

    protected $fillable = ['slug', 'ordre', 'visible', 'etiquette_fr', 'etiquette_en', 'titre_fr', 'titre_en', 'texte_fr', 'texte_en', 'libelle_bouton_fr', 'libelle_bouton_en', 'cible_bouton', 'image_source', 'diffusion_de', 'diffusion_a', 'impressions'];

    protected $casts = ['visible' => 'boolean', 'ordre' => 'integer', 'diffusion_de' => 'datetime', 'diffusion_a' => 'datetime', 'impressions' => 'integer'];

    protected $attributes = ['ordre' => 0, 'visible' => true];

    /**
     * Compte un affichage, sans rien remuer d'autre.
     *
     * DEUX precautions, et chacune repare un defaut distinct.
     *
     * `incrementQuietly` tait les evenements du modele : un `increment`
     * ordinaire declenche `updated`, donc le trait de journalisation, donc
     * DEUX lignes de journal a chaque visite de l'accueil — l'annonce et la
     * banderole. Le journal des activites, qui doit dire ce que font les
     * comptes du backoffice, se remplissait de passages de visiteurs.
     *
     * `withoutTimestamps` empeche de toucher `updated_at`. On croyait que
     * « quietly » s'en chargeait ; il ne fait que taire les evenements. La
     * date de modification suivait donc les visites au lieu des modifications.
     *
     * La methode vit SUR LE MODELE parce que `incrementQuietly` est protegee :
     * appelee de l'exterieur elle ne passe que par la magie de `__call`, qui
     * fonctionne mais qu'aucun lecteur ne peut verifier a l'oeil.
     */
    public function compterUneImpression(): void
    {
        static::withoutTimestamps(fn () => $this->incrementQuietly('impressions'));
    }

    public static function boot(): void
    {
        parent::boot();

        static::saving(function (self $modele) {
            foreach (['diffusion_de', 'diffusion_a'] as $champ) {
                $brut = $modele->getAttributes()[$champ] ?? null;
                if (is_string($brut) && $brut === '') {
                    $modele->setAttribute($champ, null);
                }
            }
        });
    }

    public function estDiffuse(): bool
    {
        return $this->visible
            && (! $this->diffusion_de || $this->diffusion_de->isPast())
            && (! $this->diffusion_a || $this->diffusion_a->isFuture());
    }

    /** Etiquette au-dessus du titre. */
    public function etiquette(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('etiquette', $langue);
    }

    /** Titre de l'encart. */
    public function titre(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('titre', $langue);
    }

    /** Corps de l'encart. */
    public function texte(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('texte', $langue);
    }

    /** Libelle du bouton d'appel a l'action. */
    public function libelleBouton(string $langue = 'fr'): string
    {
        return $this->texteDansLaLangue('libelle_bouton', $langue);
    }

    /**
     * Adresse du visuel, ou null si l'encart n'en a pas.
     *
     * Meme forme que Article::urlCouverture() et Service::urlImage() : deux
     * origines cohabitent dans `image_source` — un chemin `images/…` pour les
     * visuels repris du site statique, `storage/encarts/…` pour ceux
     * televerses depuis l'administration — et les vues n'ont qu'un point
     * d'appel.
     *
     * Il manquait, et avec lui tout rendu : le formulaire acceptait une image,
     * la stockait et l'affichait en apercu, mais aucune page publique ne la
     * lisait. L'accueil montrait un visuel FIGE par une classe CSS, la page
     * Services n'en montrait aucun.
     */
    public function urlImage(): ?string
    {
        if (! $this->image_source) {
            return null;
        }

        return asset($this->image_source);
    }
}
