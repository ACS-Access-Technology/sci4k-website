<?php

use App\Livewire\Admin\PageActualites;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\Commentaire;
use App\Models\ReglageDeSection;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les textes de la liste des actualites et de la page d'un article.
 *
 * Trente et une chaines etaient ecrites en dur dans ces deux vues et traduites
 * par __() : aucun ecran ne les exposait. Les CATEGORIES etaient bien
 * modifiables, et l'ecran des commentaires pilotait leur moderation — mais pas
 * un seul des mots que le LECTEUR voit autour d'eux.
 *
 * Ces tests mesurent au bout de la chaine : ce que le visiteur lit apres que
 * l'editeur a saisi.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->article = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'slug' => 'acd-securiser',
        'statut' => 'publie',
        'date_publication' => now()->subDay(),
        'commentaires_ouverts' => true,
    ]);
});

/** Saisit un texte depuis l'ecran, comme le ferait l'editeur. */
function saisirActualite(User $admin, string $module, string $cle, string $valeur): void
{
    Livewire::actingAs($admin)
        ->test(PageActualites::class)
        ->call('ouvrir', $module)
        ->set('textes.'.$cle.'_fr', $valeur)
        ->call('enregistrer')
        ->assertHasNoErrors();
}

it('rend les deux pages inchangees tant que rien n est saisi', function () {
    // Une base vierge doit rendre EXACTEMENT ce que ces pages rendaient avant.
    expect($this->get(route('actualites.index'))->assertOk()->getContent())
        ->toContain('Titre, mot-clé…')
        ->toContain('Contacter SCI4K');

    expect($this->get(route('actualites.detail', $this->article))->assertOk()->getContent())
        ->toContain('Retour aux actualités')
        ->toContain('Laisser un commentaire');
});

it('applique les libelles du formulaire de recherche', function () {
    saisirActualite($this->admin, 'filtres', 'libelle_recherche', 'Chercher un article');
    saisirActualite($this->admin, 'filtres', 'exemple_recherche', 'Un mot, un thème…');

    $corps = $this->get(route('actualites.index'))->assertOk()->getContent();

    expect($corps)->toContain('Chercher un article')
        ->and($corps)->toContain('Un mot, un thème…')
        ->and($corps)->not->toContain('Titre, mot-clé…');
});

it('applique les libelles de la page d un article', function () {
    saisirActualite($this->admin, 'articles', 'lien_retour', 'Revenir aux actualités');

    expect($this->get(route('actualites.detail', $this->article))->assertOk()->getContent())
        ->toContain('Revenir aux actualités');
});

it('applique les textes du bloc de commentaires', function () {
    saisirActualite($this->admin, 'commentaires', 'titre_formulaire', 'Votre avis nous intéresse');
    saisirActualite($this->admin, 'commentaires', 'libelle_bouton', 'Envoyer mon avis');

    $corps = $this->get(route('actualites.detail', $this->article))->assertOk()->getContent();

    expect($corps)->toContain('Votre avis nous intéresse')
        ->and($corps)->toContain('Envoyer mon avis');
});

it('applique le message affiche quand aucun commentaire n existe', function () {
    saisirActualite($this->admin, 'commentaires', 'aucun_commentaire', 'Personne ne s’est encore exprimé.');

    expect($this->get(route('actualites.detail', $this->article))->assertOk()->getContent())
        ->toContain('Personne ne s’est encore exprimé.');
});

it('applique le libelle du bouton « répondre »', function () {
    // Il n'apparait qu'avec un commentaire publie a repondre.
    Commentaire::factory()->create([
        'article_id' => $this->article->id,
        'statut' => Commentaire::PUBLIE,
    ]);

    saisirActualite($this->admin, 'commentaires', 'libelle_repondre', 'Réagir');

    expect($this->get(route('actualites.detail', $this->article))->assertOk()->getContent())
        ->toContain('Réagir');
});

/*
 * L'appel a l'action est le seul bloc present sur LES DEUX pages. Une saisie
 * unique doit valoir pour les deux : c'est le meme bloc, il n'a pas a etre
 * corrige a deux endroits.
 */
it('applique le bouton de l appel a l action sur les deux pages', function () {
    saisirActualite($this->admin, 'appel', 'libelle_bouton', 'Parler à un conseiller');

    // assertSee plutot que expect()->toContain() : le second argument de
    // toContain est une AUTRE chaine a chercher, et non un message d'echec.
    // Une assertion qui porte un message y perd son sens sans rien signaler.
    foreach ([route('actualites.index'), route('actualites.detail', $this->article)] as $adresse) {
        $this->get($adresse)->assertOk()->assertSee('Parler à un conseiller', false);
    }
});

it('garde chaque langue de son cote', function () {
    Livewire::actingAs($this->admin)
        ->test(PageActualites::class)
        ->call('ouvrir', 'filtres')
        ->set('textes.libelle_categorie_fr', 'Rubrique')
        ->set('textes.libelle_categorie_en', 'Topic')
        ->call('enregistrer');

    $section = ReglageDeSection::where('slug', 'news.filters')->first();

    expect($section->texteBilingue('libelle_categorie', 'fr'))->toBe('Rubrique')
        ->and($section->texteBilingue('libelle_categorie', 'en'))->toBe('Topic');
});

it("n'ecrit que les cles que le module declare", function () {
    // `$textes` est une propriete publique : le navigateur en fixe le contenu,
    // CLES COMPRISES.
    Livewire::actingAs($this->admin)
        ->test(PageActualites::class)
        ->call('ouvrir', 'filtres')
        ->set('textes.mise_en_page_fr', 'valeur injectée')
        ->call('enregistrer');

    expect(ReglageDeSection::where('slug', 'news.filters')->first()?->option('mise_en_page_fr'))
        ->toBeNull();
});
