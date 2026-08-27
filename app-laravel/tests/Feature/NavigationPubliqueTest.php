<?php

use App\Models\EntreeDeMenu;
use Database\Seeders\MenusSeeder;

/*
 * La navigation servie au visiteur.
 *
 * Elle etait ecrite en dur DEUX FOIS dans l'en-tete — barre large et menu
 * telephone — et une troisieme fois dans le pied. Ces tests verifient qu'elle
 * vient bien de la base, et qu'un des defauts de la version en dur a disparu.
 */
beforeEach(function () {
    $this->seed(MenusSeeder::class);
});

it('sert la barre de navigation depuis la base', function () {
    $corps = $this->get('/presentation')->assertOk()->getContent();

    foreach (['Accueil', 'Présentation', 'Biens Immobiliers', 'Nos Services', 'Actualités', 'FAQ', 'Contact'] as $libelle) {
        expect($corps)->toContain($libelle);
    }
});

it('n affiche plus une entree retiree de la base', function () {
    EntreeDeMenu::where('menu', 'principal')->where('cible', 'faq.index')->delete();

    $entete = extraireEntete($this->get('/presentation')->getContent());

    // Mesure dans l'EN-TETE seul : « FAQ » apparait aussi dans le pied de page
    // et dans le corps de certaines pages. Chercher dans la page entiere aurait
    // rendu ce test toujours vert.
    expect($entete)->not->toContain('FAQ');
});

it('masque une entree rendue invisible', function () {
    EntreeDeMenu::where('menu', 'principal')->where('cible', 'faq.index')->update(['visible' => false]);

    expect(extraireEntete($this->get('/presentation')->getContent()))->not->toContain('FAQ');
});

/*
 * Le defaut de la version en dur : la classe « active » etait posee sur
 * Actualites dans le gabarit, donc ce lien s'affichait actif sur toutes les
 * pages du site.
 */
it('marque comme active la page reellement affichee', function () {
    $entete = extraireEntete($this->get('/services')->getContent());

    expect($entete)->toMatch('#<a href="[^"]*/services"[^>]*class="[^"]*active#');
});

it('ne marque plus les actualites comme actives sur une autre page', function () {
    $entete = extraireEntete($this->get('/services')->getContent());

    expect($entete)->not->toMatch('#<a href="[^"]*/actualites"[^>]*class="[^"]*active#');
});

it('sert les liens legaux du pied depuis la base', function () {
    $corps = $this->get('/presentation')->getContent();

    expect($corps)->toContain('/mentions-legales.html')
        ->and($corps)->toContain('/politique-confidentialite.html');
});

it('rend inerte une cible devenue invalide', function () {
    // Une donnee ancienne, entree avant que le controle n'existe. Le lien doit
    // degrader, jamais s'executer chez le visiteur.
    EntreeDeMenu::where('menu', 'principal')->where('cible', 'faq.index')
        ->update(['cible' => 'javascript:alert(1)']);

    $corps = $this->get('/presentation')->getContent();

    expect($corps)->not->toContain('javascript:alert(1)');
});

/** L'en-tete seule, du <header> a sa fermeture. */
function extraireEntete(string $html): string
{
    $debut = mb_strpos($html, '<header');
    $fin = mb_strpos($html, '</header>');

    return $debut === false || $fin === false ? '' : mb_substr($html, $debut, $fin - $debut);
}
