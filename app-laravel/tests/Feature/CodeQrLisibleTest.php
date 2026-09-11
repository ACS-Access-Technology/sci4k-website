<?php

/*
 * Le QR code de la double authentification reste lisible en theme sombre.
 *
 * Signale par le client : « je n'arrive pas a scanner le code QR ». Observe sur
 * son ecran, en theme sombre : le code s'affichait EN NEGATIF — modules clairs
 * sur fond noir.
 *
 * Le gabarit herite du starter kit posait « filter: invert(1) brightness(1.5) »
 * sur le conteneur du QR des que le theme sombre etait actif. L'intention etait
 * esthetique : accorder le code a l'habillage.
 *
 * Mais la norme QR suppose des modules SOMBRES sur fond CLAIR. Un code inverse
 * est refuse par la plupart des lecteurs, dont les applications
 * d'authentification les plus repandues. L'habillage passait donc avant la
 * fonction, et la fonction ne marchait plus.
 *
 * Le conteneur porte deja « bg-white » : le code garde son fond blanc dans les
 * deux themes, ce qui lui fournit du meme coup la marge tranquille que la norme
 * exige autour du motif.
 */

use Illuminate\Support\Facades\File;

/** Le gabarit, commentaires retires : ils expliquent le defaut et le nomment. */
function gabaritDuModaleDeuxFacteurs(): string
{
    $gabarit = File::get(resource_path('views/pages/settings/⚡two-factor-setup-modal.blade.php'));

    return preg_replace('/\{\{--.*?--\}\}/s', '', $gabarit) ?? $gabarit;
}

it('n inverse pas le code QR en theme sombre', function () {
    expect(gabaritDuModaleDeuxFacteurs())
        ->not->toContain('invert(')
        ->not->toContain('filter:');
});

/*
 * Et il garde son fond blanc : un QR sans fond clair, pose sur un panneau
 * sombre, est tout aussi illisible qu'un QR inverse.
 */
it('garde un fond blanc autour du code QR', function () {
    expect(gabaritDuModaleDeuxFacteurs())->toContain('bg-white');
});
