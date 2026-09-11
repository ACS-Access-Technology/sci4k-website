<?php

/*
 * Les en-tetes de securite que le site doit poser sur chaque reponse.
 *
 * Releves absents par un audit externe. Chacun ferme une porte precise, et
 * aucun ne depend d'un reglage : ils valent pour toutes les pages, publiques
 * comme administratives.
 *
 * La politique de securite du contenu n'en fait PAS partie. Elle demande
 * « unsafe-inline » tant que les gabarits portent des gestionnaires en ligne,
 * et elle casserait le chat ou les statistiques le jour ou on les active. Elle
 * fera l'objet d'un lot a elle, en mode observation d'abord.
 */

it('pose les en-tetes sur une page publique', function () {
    $reponse = $this->get('/');

    $reponse->assertOk()
        // Empeche un navigateur de deviner le type d'un fichier : une image
        // televersee qui contiendrait du script ne sera pas executee.
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        // Personne ne peut afficher ce site dans son propre cadre pour y faire
        // cliquer un visiteur a son insu.
        ->assertHeader('X-Frame-Options', 'DENY')
        // L'adresse complete d'une page ne suit pas le visiteur vers un autre
        // domaine — seule l'origine part.
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    expect($reponse->headers->get('Permissions-Policy'))
        ->toContain('camera=()')
        ->toContain('microphone=()')
        ->toContain('geolocation=()');
});

it('pose les memes en-tetes sur le backoffice', function () {
    // Meme non authentifie : la redirection vers la connexion les porte aussi.
    $this->get('/dashboard')->assertHeader('X-Content-Type-Options', 'nosniff');
});

/*
 * HSTS ne se pose QUE sur une reponse servie en HTTPS.
 *
 * Envoye en clair, il est ignore par les navigateurs — et l'annoncer sur un
 * poste de developpement en http:// forcerait ce poste a exiger un HTTPS qu'il
 * n'a pas, pendant une annee, sans moyen simple de revenir en arriere.
 */
it('n annonce pas HSTS sur une requete en clair', function () {
    expect($this->get('http://localhost/')->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('annonce HSTS sur une requete chiffree', function () {
    $entete = (string) $this->get('https://localhost/')->headers->get('Strict-Transport-Security');

    expect($entete)->toContain('max-age=')
        // Ni includeSubDomains ni preload : le premier engage des
        // sous-domaines qui ne nous appartiennent pas, le second est
        // irreversible. Voir le middleware.
        ->not->toContain('includeSubDomains')
        ->not->toContain('preload');
});
