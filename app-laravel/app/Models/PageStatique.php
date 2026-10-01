<?php

namespace App\Models;

use App\Support\HtmlEditorial;
use Illuminate\Database\Eloquent\Model;

class PageStatique extends Model
{
    protected $table = 'pages_statiques';

    /**
     * Les seules pages editables, et leur intitule a l'ecran.
     *
     * La liste vivait a deux endroits qui ne se parlaient pas : le menu
     * deroulant de l'ecran d'edition, et le controleur public. Rien
     * n'empechait un slug arbitraire d'entrer par le premier — une propriete
     * publique Livewire est fixee par le navigateur — puis de creer une ligne
     * que le second ne servirait jamais. Une seule source, lue par les deux.
     *
     * @var array<string, string>
     */
    public const EDITABLES = [
        'mentions-legales' => 'Mentions légales',
        'politique-confidentialite' => 'Politique de confidentialité',
    ];

    /*
     * « contact » a quitte cette liste : la page est desormais rendue par
     * PagePubliqueController::contact(), et non plus par un bloc de texte.
     * L'y laisser aurait fait un ecran menteur — un editeur aurait saisi un
     * contenu que plus aucune adresse ne sert. Ses textes se modifient
     * maintenant depuis « Pages du site → Contact ».
     *
     * Attention : la liste gouverne aussi la page ouverte au chargement de
     * l'ecran. En retirer une qui y etait ecrite en dur a rendu cet ecran
     * inaccessible — il repondait 404 a tout le monde. PagesStatiques prend
     * desormais la premiere de cette liste, quelle qu'elle soit.
     */

    /** @return list<string> */
    public static function slugsEditables(): array
    {
        return array_keys(self::EDITABLES);
    }

    protected $fillable = ['slug', 'titre_fr', 'titre_en', 'contenu_fr', 'contenu_en', 'publie'];

    protected $casts = ['publie' => 'boolean'];

    public function titre(string $langue = 'fr'): string
    {
        return $this->{'titre_'.$langue} ?: $this->titre_fr;
    }

    /**
     * Le contenu tel que le visiteur le recoit : filtre, donc sur a rendre sans
     * echappement.
     *
     * Le filtre joue ICI, a la sortie, et non a l'enregistrement. Il reencode
     * le texte — une apostrophe devient &#039;, une arobase &#64; — ce qui ne
     * change rien a l'ecran du visiteur mais remplirait d'entites illisibles
     * le champ HTML de l'administrateur. La base garde donc la source telle
     * qu'elle a ete tapee, et tout ce qui la rend passe par ici : y compris un
     * texte saisi avant ce filtre, ou verse par une migration.
     *
     * Le resultat contient des entites : qui en tire du texte brut (la
     * description de la page) doit les decoder, sans quoi l'echappement de
     * Blade les afficherait telles quelles.
     */
    public function contenu(string $langue = 'fr'): string
    {
        return HtmlEditorial::nettoyer($this->{'contenu_'.$langue} ?: $this->contenu_fr);
    }
}
