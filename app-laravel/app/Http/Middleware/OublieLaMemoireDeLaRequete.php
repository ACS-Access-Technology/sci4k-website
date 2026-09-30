<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Once;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chaque requete repart d'une memoire vide.
 *
 * Quelques lectures sont memorisees par once() le temps d'UNE requete : les
 * reglages (Parametre::tous()), l'existence des tables, la section des textes
 * de l'habillage. Sans cela, une page publique relisait les memes lignes une
 * trentaine de fois — pres de 90 requetes SQL par page, dont 70 pour rien.
 *
 * once() vit aussi longtemps que l'APPLICATION, pas que la requete. En
 * production, FrankenPHP en mode classique repart d'une application neuve a
 * chaque requete, et la question ne se pose pas ; mais dans un test, toutes
 * les requetes d'un meme cas partagent la meme application, et en mode
 * « worker » ce serait le cas de toutes les requetes du processus. Une
 * memoire qui survit a sa requete fige une lecture perimee — c'est exactement
 * le defaut qu'avait cause une variable `static` dans AppServiceProvider,
 * retiree pour cette raison.
 *
 * D'ou ce middleware, en tete de la pile globale : la memoire est videe au
 * debut de chaque requete, partout. C'est ce que fait Octane pour la meme
 * raison.
 */
class OublieLaMemoireDeLaRequete
{
    public function handle(Request $request, Closure $suite): Response
    {
        Once::flush();

        return $suite($request);
    }
}
