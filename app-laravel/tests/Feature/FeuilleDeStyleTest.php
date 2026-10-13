<?php

/**
 * La feuille de style du site public (source : maquettes-frontoffice/assets).
 * Ces regles ont ete mesurees dans un navigateur le 12 octobre 2026 ; le test
 * garde ce qui a ete corrige, sans pretendre remplacer la mesure.
 */
function feuilleDeStyle(): string
{
    return (string) file_get_contents(base_path('../maquettes-frontoffice/assets/style.css'));
}

it('laisse les trois chiffres cles se partager la largeur', function () {
    // « minmax(220px, 1fr) » forcait 3 x 220 + 2 x 40 = 740 px : plus que la boite
    // a 768 px, le troisieme chiffre depassait.
    expect(feuilleDeStyle())->toMatch('/\.hero-stats\{display:grid;grid-template-columns:repeat\(3,minmax\(0,1fr\)\);gap:40px;/')
        ->not->toContain('repeat(3,minmax(220px,1fr))');
});

it('donne aux liens du pied de page au moins 24 px de haut', function () {
    // WCAG 2.2, critere 2.5.8. Mesure avant : 23 px (pied de page), 16 px
    // (mentions legales), 19 px (retour au catalogue).
    expect(feuilleDeStyle())->toContain('.footer-top a{padding-block:2px;}')
        ->toContain('.legal-links a,.fiche-bien .lien-retour a{display:inline-block;padding-block:5px;}');
});
