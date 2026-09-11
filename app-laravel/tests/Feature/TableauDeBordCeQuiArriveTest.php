<?php

/*
 * Le tableau de bord montre ce qui ARRIVE, pas seulement ce qui manque.
 *
 * Signale par le client : « les demandes de visite en attente et autres ne sont
 * pas affichees dans le tableau de bord ».
 *
 * Le panneau « À traiter » suivait la completude editoriale — brouillons,
 * elements masques, textes sans version anglaise, membres sans photo. Tout cela
 * peut attendre. Ce qui ne peut pas attendre, ce sont les trois choses qu'un
 * visiteur vient de deposer : une demande de visite a confirmer, un message de
 * contact non lu, un commentaire en attente de relecture.
 *
 * C'est la difference entre un ecran qui dit « voici ce que votre site n'a pas
 * fini » et un ecran qui dit « voici ce qu'on attend de vous aujourd'hui ». Ces
 * trois-la passent donc EN TETE : une demande de visite qui dort trois jours
 * derriere quatre rappels de traduction est une affaire perdue.
 */

use App\Livewire\Admin\TableauDeBord;
use App\Models\Article;
use App\Models\Bien;
use App\Models\Categorie;
use App\Models\Commentaire;
use App\Models\DemandeDeVisite;
use App\Models\MessageDeContact;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('annonce les demandes de visite a confirmer', function () {
    DemandeDeVisite::factory()->count(2)->create(['statut' => DemandeDeVisite::A_CONFIRMER]);
    DemandeDeVisite::factory()->create(['statut' => DemandeDeVisite::REALISEE]);

    Livewire::actingAs($this->admin)
        ->test(TableauDeBord::class)
        ->assertSee('2 demandes de visite à confirmer', false);
});

it('annonce les messages de contact non lus', function () {
    MessageDeContact::factory()->count(3)->create(['statut' => MessageDeContact::NOUVEAU]);
    MessageDeContact::factory()->create(['statut' => MessageDeContact::TRAITE]);

    Livewire::actingAs($this->admin)
        ->test(TableauDeBord::class)
        ->assertSee('3 messages de contact non lus', false);
});

it('annonce les commentaires en attente de relecture', function () {
    $categorie = Categorie::create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1]);
    $article = Article::factory()->create(['categorie_id' => $categorie->id, 'statut' => 'publie']);

    Commentaire::factory()->create(['article_id' => $article->id, 'statut' => Commentaire::EN_ATTENTE]);
    Commentaire::factory()->create(['article_id' => $article->id, 'statut' => Commentaire::PUBLIE]);

    Livewire::actingAs($this->admin)
        ->test(TableauDeBord::class)
        ->assertSee('1 commentaire en attente de relecture', false);
});

/*
 * L'ordre porte le sens : ce qu'un visiteur attend passe avant ce que le site
 * n'a pas fini d'ecrire.
 */
it('place ce qui arrive avant les rappels editoriaux', function () {
    Bien::factory()->create(['statut' => 'publie']);
    $categorie = Categorie::create(['slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land', 'ordre' => 1]);
    Article::factory()->create(['categorie_id' => $categorie->id, 'statut' => 'brouillon']);
    DemandeDeVisite::factory()->create(['statut' => DemandeDeVisite::A_CONFIRMER]);

    $rendu = Livewire::actingAs($this->admin)->test(TableauDeBord::class)->html();

    expect(strpos($rendu, 'demande de visite à confirmer'))
        ->toBeLessThan(strpos($rendu, 'article en brouillon'));
});
