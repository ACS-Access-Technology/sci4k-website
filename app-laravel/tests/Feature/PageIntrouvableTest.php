<?php

/*
 * La page « introuvable » appartient au site, pas au framework.
 *
 * Constate en recette : une adresse inexistante rendait la page d'erreur par
 * defaut de Laravel — titre « Not Found », fond blanc, aucun menu, aucun lien
 * de retour, et le nom SCI4K nulle part. Le visiteur qui suit un vieux lien se
 * retrouvait devant un ecran technique, sans autre issue que le bouton
 * « precedent ».
 *
 * Un 404.html aux couleurs du site existe pourtant dans public/. Il n'etait
 * jamais servi, et ne pouvait pas l'etre : le serveur ne va chercher un
 * fichier statique que si aucune route ne repond, or c'est Laravel qui traite
 * l'adresse et leve l'exception bien avant.
 */

it('rend une page introuvable aux couleurs du site', function () {
    $reponse = $this->get('/adresse-qui-nexiste-pas');

    $reponse->assertNotFound();

    // Le nom du site, donc le gabarit public : l'ecran du framework ne le
    // porte nulle part.
    $reponse->assertSee('SCI4K', false);
});

/*
 * Le point qui compte pour le visiteur : une sortie. La page d'origine n'en
 * offrait aucune.
 */
it('offre un retour a l accueil', function () {
    $this->get('/adresse-qui-nexiste-pas')
        ->assertNotFound()
        ->assertSee('href="'.url('/').'"', false);
});

/*
 * Le moteur de recherche qui tombe sur cette page ne doit pas l'indexer : elle
 * n'a pas de contenu propre et se retrouverait dans les resultats a la place
 * de la page cherchee.
 */
it('demande aux moteurs de ne pas l indexer', function () {
    $this->get('/adresse-qui-nexiste-pas')
        ->assertNotFound()
        ->assertSee('noindex', false);
});

/*
 * Une adresse anglaise inexistante doit repondre en anglais. Aucune route ne
 * correspondant, le middleware de langue ne tourne pas : la langue se lit donc
 * sur l'adresse elle-meme, ou nulle part.
 */
it('repond en anglais sous le prefixe anglais', function () {
    $this->get('/en/address-that-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page not found', false);
});
