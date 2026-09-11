<?php

/*
 * Saisie bilingue quand AUCUN traducteur n'est configure.
 *
 * Les formulaires de blocs declarent leurs champs bilingues une fois, et la
 * validation en derivait DEUX regles identiques : titre_fr required ET
 * titre_en required. Le formulaire compte sur completerCouple() pour remplir
 * la langue absente — mais celui-ci rend le couple inchange quand le
 * traducteur manque (RemplitParTraduction, ligne 81).
 *
 * Sans cle DeepL, l'editeur qui remplissait l'onglet francais voyait donc son
 * enregistrement refuse sur un champ range dans l'onglet anglais, lequel est
 * rendu avec la classe « hidden » : le message d'erreur existait dans la page
 * et restait invisible. Le bouton « Enregistrer » paraissait mort.
 *
 * Constate en production : l'encart « accueil.annonce » n'a pas bouge d'un
 * caractere depuis le peuplement initial, malgre plusieurs tentatives.
 *
 * La regle retenue suit ce que le site fait DEJA de ses donnees :
 * TraduitParColonnes replie sur le francais des que la colonne anglaise est
 * vide. Le francais est donc obligatoire, l'anglais facultatif — exiger les
 * deux contredisait la lecture meme du modele.
 */

use App\Livewire\Admin\EncartFormulaire;
use App\Models\Encart;
use App\Models\User;
use App\Services\Traduction\Traducteur;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    // Aucun traducteur : c'est la situation de l'hebergement d'essai, ou
    // aucune cle DeepL n'est posee.
    app()->bind(Traducteur::class, fn () => new class implements Traducteur
    {
        public function disponible(): bool
        {
            return false;
        }

        public function traduire(array $textes, string $vers, ?string $depuis = null): ?array
        {
            return null;
        }
    });

    $this->annonce = Encart::firstOrCreate(['slug' => 'accueil.annonce'], [
        'visible' => true,
        'titre_fr' => '', 'titre_en' => '',
        'texte_fr' => '', 'texte_en' => '',
    ]);
});

it('enregistre un encart dont seul le francais est rempli', function () {
    Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $this->annonce, 'embarque' => true])
        ->set('valeurs.titre_fr', 'Terrains viabilisés à Bingerville')
        ->set('valeurs.texte_fr', 'Lots de 500 m² disponibles.')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->annonce->fresh()->titre_fr)->toBe('Terrains viabilisés à Bingerville');

    $this->get('/')->assertOk()->assertSee('Terrains viabilisés à Bingerville', false);
});

/*
 * L'anglais laisse vide n'est pas un trou sur le site anglais : le modele
 * replie sur le francais. C'est ce repli qui rend la regle « anglais
 * facultatif » sure, et non un simple assouplissement de confort.
 */
it('sert le francais sur la page anglaise quand l anglais est vide', function () {
    Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $this->annonce, 'embarque' => true])
        ->set('valeurs.titre_fr', 'Terrains viabilisés à Bingerville')
        ->call('enregistrer')
        ->assertHasNoErrors();

    $this->get('/en')->assertOk()->assertSee('Terrains viabilisés à Bingerville', false);
});

/*
 * Le repli va du francais vers l'anglais, JAMAIS l'inverse : un titre saisi en
 * anglais seul laisserait la page francaise vide. Le francais reste donc exige.
 */
it('refuse un encart dont le francais est vide', function () {
    Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $this->annonce, 'embarque' => true])
        ->set('valeurs.titre_en', 'Serviced plots in Bingerville')
        ->call('enregistrer')
        ->assertHasErrors(['valeurs.titre_fr']);
});

/*
 * Et ce refus doit se VOIR. L'onglet de la langue fautive passe au premier
 * plan : sans cela l'erreur reste rendue dans le bloc masque, exactement le
 * defaut que ce fichier corrige.
 */
it('bascule sur l onglet de la langue fautive', function () {
    Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $this->annonce, 'embarque' => true])
        ->set('langueActive', 'en')
        ->set('valeurs.titre_en', 'Serviced plots in Bingerville')
        ->call('enregistrer')
        ->assertHasErrors(['valeurs.titre_fr'])
        ->assertSet('langueActive', 'fr');
});

/*
 * La regle doit se LIRE dans le formulaire. Sans traducteur, l'encadre bleu
 * qui promet la traduction automatique disparait, et plus rien ne disait a
 * l'editeur ce qu'il advient d'un onglet anglais laisse vide.
 */
it('dit que l anglais est facultatif quand aucun traducteur n est configure', function () {
    $rendu = Livewire::actingAs($this->admin)
        ->test(EncartFormulaire::class, ['element' => $this->annonce, 'embarque' => true])
        ->html();

    expect($rendu)
        ->toContain('facultatif')
        ->not->toContain('sera traduite à l’enregistrement');
});
