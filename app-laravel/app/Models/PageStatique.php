<?php

namespace App\Models;

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
     * maintenant depuis « Sections du site » (contact.page, contact.form,
     * contact.map) et depuis l'onglet « Contact » de la configuration.
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

    public function contenu(string $langue = 'fr'): string
    {
        return $this->{'contenu_'.$langue} ?: $this->contenu_fr;
    }
}
