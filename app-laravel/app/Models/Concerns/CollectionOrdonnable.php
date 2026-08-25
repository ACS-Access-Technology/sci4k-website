<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Collection dont l'ordre d'affichage se regle a la main.
 *
 * Les sept collections du lot 2 partagent ce comportement. L'ecrire une fois
 * evite d'avoir sept endroits a corriger au premier defaut — meme raisonnement
 * que TraduitParColonnes au lot 1.
 */
trait CollectionOrdonnable
{
    /** Du premier au dernier rang. */
    public function scopeOrdonnees(Builder $requete): Builder
    {
        return $requete->orderBy('ordre')->orderBy('id');
    }

    /** Ce que le public voit. */
    public function scopeVisibles(Builder $requete): Builder
    {
        return $requete->where('visible', true);
    }

    /**
     * Reecrit les rangs dans l'ordre recu, en repartant de 1.
     *
     * Les identifiants viennent du navigateur : ceux qui ne correspondent a
     * rien sont ignores plutot que de faire echouer l'operation entiere, et
     * surtout sans decaler les rangs des elements legitimes. Un identifiant
     * repete n'est retenu qu'a sa premiere apparition, sans quoi le rang
     * qu'il recoit serait ecrase par son propre doublon et la numerotation
     * sauterait un rang.
     *
     * Une seule transaction : un reordonnancement interrompu a mi-chemin
     * laisserait des rangs en double.
     */
    public static function reordonner(array $idsDansLOrdre): void
    {
        $idsDansLOrdre = array_values(array_unique($idsDansLOrdre));

        $connus = static::query()
            ->whereIn('id', $idsDansLOrdre)
            ->pluck('id')
            ->all();

        $rang = 0;

        DB::transaction(function () use ($idsDansLOrdre, $connus, &$rang) {
            foreach ($idsDansLOrdre as $id) {
                if (! in_array($id, $connus)) {
                    continue;
                }

                static::query()->whereKey($id)->update(['ordre' => ++$rang]);
            }
        });
    }
}
