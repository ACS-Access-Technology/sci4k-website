<?php

/*
 * Le catalogue n'annonce plus une recherche qu'il ne fait pas.
 *
 * Un bouton « Rechercher le bien idéal → » coiffait les filtres. Il ne
 * cherchait rien : chaque liste relance la recherche d'elle-meme, et le
 * bouton appelait reinitialiser() — il VIDAIT donc les criteres que le
 * visiteur venait de poser.
 *
 * Le commentaire du gabarit l'assumait — « il ramene a la liste complete » —
 * mais le libelle, lui, promettait l'inverse, et c'est le libelle que le
 * visiteur lit. L'onglet « Tous », juste en dessous, offre deja cette remise a
 * zero sous un nom qui la dit.
 */

it('n affiche plus de bouton de recherche au-dessus des filtres', function () {
    $this->get('/biens')
        ->assertOk()
        ->assertDontSee('search-submit', false)
        ->assertDontSee('Rechercher le bien idéal', false);
});

/** La remise a zero reste atteignable, sous le nom qui la decrit. */
it('garde l onglet qui ramene a la liste complete', function () {
    $this->get('/biens')
        ->assertOk()
        ->assertSee('reinitialiser', false);
});
