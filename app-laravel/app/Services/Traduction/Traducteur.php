<?php

namespace App\Services\Traduction;

/**
 * Traduit des textes d'une langue vers l'autre.
 *
 * L'interface existe pour que le service soit remplacable : DeepL a ete retenu
 * pour son offre gratuite et sa qualite sur le couple francais-anglais, mais
 * rien dans le formulaire n'en depend.
 */
interface Traducteur
{
    /**
     * Traduit une liste de textes, en preservant l'ordre.
     *
     * @param  list<string>  $textes
     * @return list<string>|null null si le service n'est pas configure ou n'a
     *                           pas repondu — jamais une traduction partielle,
     *                           qui laisserait un article a moitie traduit sans
     *                           que personne le remarque.
     */
    public function traduire(array $textes, string $vers, ?string $depuis = null): ?array;

    /** Le service est-il utilisable ? Faux si aucune cle n'est configuree. */
    public function disponible(): bool;
}
