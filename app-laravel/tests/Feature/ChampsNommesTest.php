<?php

use App\Livewire\Admin\BienListe;
use App\Livewire\Admin\CommentaireListe;
use App\Livewire\Admin\PagePresentation;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Tout champ de saisie doit porter un nom accessible.
 *
 * Sans lui, un lecteur d'ecran annonce « zone de recherche » sans dire ce qu'on
 * cherche, et « liste deroulante » sans dire de quoi. Les filtres du catalogue
 * de biens en comptaient quatre dans ce cas.
 *
 * UN PLACEHOLDER NE SUFFIT PAS : il disparait des la premiere frappe, et les
 * lecteurs d'ecran ne l'annoncent pas de facon fiable. UN ID NON PLUS : il ne
 * nomme rien tant qu'aucun label ne le reference. C'est le cas que les deux
 * champs de la liste des commentaires presentaient — ils avaient un id, et
 * personne pour s'en servir.
 */

beforeEach(function () {
    foreach (['administrateur', 'editeur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/**
 * Rend les champs de saisie du HTML qui n'ont aucun nom accessible.
 *
 * Quatre facons d'en donner un, et le test les accepte toutes : aria-label,
 * aria-labelledby, un label qui reference l'id, ou un label qui englobe le
 * champ.
 *
 * @return list<string>
 */
function champsSansNomAccessible(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
    $chemin = new DOMXPath($document);

    $etiquetes = [];
    foreach ($chemin->query('//label[@for]') as $label) {
        $etiquetes[$label->getAttribute('for')] = true;
    }

    $anonymes = [];

    foreach ($chemin->query('//input|//select|//textarea') as $champ) {
        if (in_array($champ->getAttribute('type'), ['hidden', 'submit', 'button'], true)) {
            continue;
        }

        if ($champ->getAttribute('aria-label') !== '' || $champ->getAttribute('aria-labelledby') !== '') {
            continue;
        }

        $identifiant = $champ->getAttribute('id');
        if ($identifiant !== '' && isset($etiquetes[$identifiant])) {
            continue;
        }

        // Un label qui englobe le champ le nomme aussi, sans attribut « for ».
        $parent = $champ->parentNode;
        $englobe = false;
        while ($parent instanceof DOMElement) {
            if ($parent->nodeName === 'label') {
                $englobe = true;
                break;
            }
            $parent = $parent->parentNode;
        }

        if (! $englobe) {
            $anonymes[] = $champ->nodeName.'#'.($identifiant ?: '(sans id)');
        }
    }

    return $anonymes;
}

it('nomme les filtres du catalogue de biens', function () {
    $html = Livewire::actingAs($this->admin)->test(BienListe::class)->html();

    expect(champsSansNomAccessible($html))->toBe([]);
});

it('nomme les filtres de la liste des commentaires', function () {
    $html = Livewire::actingAs($this->admin)->test(CommentaireListe::class)->html();

    expect(champsSansNomAccessible($html))->toBe([]);
});

it('nomme les champs de l ecran de la page presentation', function () {
    // Cet ecran porte les deux cas que l'analyse statique ne voit pas de la
    // meme facon : un textarea pose HORS du label qui englobe son voisin, et un
    // champ de fichier qui n'a jamais eu d'etiquette.
    $html = Livewire::actingAs($this->admin)->test(PagePresentation::class)
        ->set('module', 'atouts')
        ->html();

    expect(champsSansNomAccessible($html))->toBe([]);
});
