<?php

use App\Livewire\Admin\ArticleFormulaire;
use App\Livewire\Admin\CommentaireListe;
use App\Livewire\Admin\PageActualites;
use App\Mail\NouveauCommentaire;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\Commentaire;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Les commentaires d'articles.
 *
 * Le client a choisi la publication IMMEDIATE, avec une moderation
 * independante. « Independante » a deux sens, et les deux sont testes ici : un
 * filtre ecarte le courrier indesirable sans qu'un humain soit devant l'ecran,
 * et un ecran dedie permet de trancher ensuite.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->article = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'publie',
    ]);
});

/** @return array<string, string> */
function unCommentaire(array $champs = []): array
{
    return $champs + [
        'auteur' => 'Awa Koné',
        'email' => 'awa@example.com',
        'message' => 'Article très clair, merci pour les précisions sur l’ACD.',
    ];
}

/* ------------------------------------------------------------------ */
/* Le depot */
/* ------------------------------------------------------------------ */

it('publie immediatement un commentaire ordinaire', function () {
    $this->post(route('commentaires.depot', $this->article), unCommentaire())
        ->assertRedirect(route('actualites.detail', $this->article).'#commentaires');

    $commentaire = Commentaire::first();

    expect($commentaire->statut)->toBe(Commentaire::PUBLIE)
        ->and($commentaire->motif_de_mise_en_attente)->toBeNull();

    $this->get(route('actualites.detail', $this->article))
        ->assertSee('Awa Koné', false);
});

/**
 * Le coeur de la moderation independante : ce filtre travaille sans qu'un
 * humain soit devant l'ecran. Sans lui, « publication immediate » voudrait
 * dire « publicite immediate ».
 */
it('met de cote un commentaire qui ressemble a du courrier indesirable', function (array $champs, string $motif) {
    $this->post(route('commentaires.depot', $this->article), unCommentaire($champs));

    $commentaire = Commentaire::first();

    expect($commentaire->statut)->toBe(Commentaire::EN_ATTENTE)
        ->and($commentaire->motif_de_mise_en_attente)->toBe($motif);

    // Et surtout : il ne s'affiche PAS.
    $this->get(route('actualites.detail', $this->article))
        ->assertDontSee($commentaire->message, false);
})->with([
    'un lien' => [['message' => 'Super article, voir aussi https://pas-cher.example'], 'Contient un lien'],
    'du www' => [['message' => 'Allez sur www.pas-cher.example pour mieux'], 'Contient un lien'],
    'tout en majuscules' => [['message' => 'ACHETEZ MAINTENANT DES TERRAINS PAS CHERS ICI'], 'Écrit tout en majuscules'],
    'une adresse dans le nom' => [['auteur' => 'https://pas-cher.example'], 'Adresse dans le nom'],
]);

/** Le champ piege : un robot remplit tout ce qu'il trouve. */
it('refuse un depot qui remplit le champ piege', function () {
    $this->post(route('commentaires.depot', $this->article), unCommentaire(['site_web' => 'http://robot.example']))
        ->assertSessionHasErrors('site_web');

    expect(Commentaire::count())->toBe(0);
});

it('exige un nom, une adresse et un message', function () {
    $this->post(route('commentaires.depot', $this->article), [])
        ->assertSessionHasErrors(['auteur', 'email', 'message']);
});

/** Un brouillon n'existe pas pour le public : 404, comme sa page. */
it('refuse un commentaire sous un brouillon', function () {
    $brouillon = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'brouillon',
    ]);

    $this->post(route('commentaires.depot', $brouillon), unCommentaire())->assertNotFound();
});

/**
 * Le refus est pose dans le CONTROLEUR et pas seulement dans le gabarit : un
 * formulaire absent n'empeche personne de poster a la main.
 */
it('refuse un commentaire quand ils sont fermes sur l article', function () {
    $this->article->update(['commentaires_ouverts' => false]);

    $this->post(route('commentaires.depot', $this->article), unCommentaire())->assertForbidden();

    expect(Commentaire::count())->toBe(0);
});

it('ne montre plus le formulaire quand les commentaires sont fermes', function () {
    $this->article->update(['commentaires_ouverts' => false]);

    $this->get(route('actualites.detail', $this->article))
        ->assertSee('Les commentaires sont fermés sur cet article.', false)
        ->assertDontSee('name="auteur"', false);
});

/* ------------------------------------------------------------------ */
/* Les reponses */
/* ------------------------------------------------------------------ */

it('rattache une reponse a son commentaire', function () {
    $parent = Commentaire::factory()->create(['article_id' => $this->article->id]);

    $this->post(route('commentaires.depot', $this->article), unCommentaire(['parent_id' => $parent->id]));

    expect(Commentaire::latest('id')->first()->parent_id)->toBe($parent->id);

    $this->get(route('actualites.detail', $this->article))->assertSee('comment-reply', false);
});

/**
 * L'identifiant du parent vient du navigateur : sans cette verification, une
 * reponse forgee s'accrocherait sous un AUTRE article.
 */
it('refuse une reponse a un commentaire d un autre article', function () {
    $autre = Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
        'statut' => 'publie',
    ]);
    $ailleurs = Commentaire::factory()->create(['article_id' => $autre->id]);

    $this->post(route('commentaires.depot', $this->article), unCommentaire(['parent_id' => $ailleurs->id]))
        ->assertSessionHasErrors('parent_id');
});

/** Un seul niveau : au-dela, un fil devient illisible sur telephone. */
it('refuse de repondre a une reponse', function () {
    $parent = Commentaire::factory()->create(['article_id' => $this->article->id]);
    $reponse = Commentaire::factory()->create([
        'article_id' => $this->article->id,
        'parent_id' => $parent->id,
    ]);

    $this->post(route('commentaires.depot', $this->article), unCommentaire(['parent_id' => $reponse->id]))
        ->assertSessionHasErrors('parent_id');
});

/** Supprimer un commentaire emporte ses reponses : elles n'ont plus de question. */
it('supprime les reponses avec leur commentaire', function () {
    $parent = Commentaire::factory()->create(['article_id' => $this->article->id]);
    Commentaire::factory()->create(['article_id' => $this->article->id, 'parent_id' => $parent->id]);

    $parent->delete();

    expect(Commentaire::count())->toBe(0);
});

/* ------------------------------------------------------------------ */
/* La notification */
/* ------------------------------------------------------------------ */

it('previent l agence a chaque depot', function () {
    Mail::fake();
    Parametre::poser('destinataire_formulaire', 'agence@sci4k.com', 'contact');

    $this->post(route('commentaires.depot', $this->article), unCommentaire());

    Mail::assertSent(NouveauCommentaire::class);
});

/** Sans destinataire configure, aucun envoi — et le commentaire reste depose. */
it('n envoie rien quand aucun destinataire n est configure', function () {
    Mail::fake();

    $this->post(route('commentaires.depot', $this->article), unCommentaire());

    Mail::assertNothingSent();
    expect(Commentaire::count())->toBe(1);
});

/* ------------------------------------------------------------------ */
/* L'ecran de moderation */
/* ------------------------------------------------------------------ */

it('ouvre sur les commentaires mis de cote', function () {
    Commentaire::factory()->create(['article_id' => $this->article->id, 'message' => 'Déjà en ligne.']);
    Commentaire::factory()->enAttente()->create(['article_id' => $this->article->id, 'message' => 'À vérifier.']);

    Livewire::actingAs($this->admin)->test(CommentaireListe::class)
        ->assertSet('statut', Commentaire::EN_ATTENTE)
        ->assertSee('À vérifier.')
        ->assertDontSee('Déjà en ligne.');
});

it('publie un commentaire mis de cote', function () {
    $commentaire = Commentaire::factory()->enAttente()->create(['article_id' => $this->article->id]);

    Livewire::actingAs($this->admin)->test(CommentaireListe::class)
        ->call('changerLeStatut', $commentaire->id, Commentaire::PUBLIE);

    expect($commentaire->fresh()->statut)->toBe(Commentaire::PUBLIE);
});

it('retire du site un commentaire deja publie', function () {
    $commentaire = Commentaire::factory()->create(['article_id' => $this->article->id]);

    Livewire::actingAs($this->admin)->test(CommentaireListe::class)
        ->call('changerLeStatut', $commentaire->id, Commentaire::REJETE);

    expect($commentaire->fresh()->statut)->toBe(Commentaire::REJETE);

    $this->get(route('actualites.detail', $this->article))
        ->assertDontSee($commentaire->message, false);
});

it('refuse un statut inconnu', function () {
    $commentaire = Commentaire::factory()->create(['article_id' => $this->article->id]);

    Livewire::actingAs($this->admin)->test(CommentaireListe::class)
        ->call('changerLeStatut', $commentaire->id, 'invente')
        ->assertNotFound();
});

it('supprime definitivement un commentaire', function () {
    $commentaire = Commentaire::factory()->create(['article_id' => $this->article->id]);

    Livewire::actingAs($this->admin)->test(CommentaireListe::class)
        ->call('supprimer', $commentaire->id);

    expect(Commentaire::count())->toBe(0);
});

/**
 * La route protege l'ecran, pas l'action : Livewire ne rejoue pas ses
 * middlewares sur /livewire/update.
 */
it('interdit la moderation a un lecteur', function () {
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');
    $commentaire = Commentaire::factory()->create(['article_id' => $this->article->id]);

    Livewire::actingAs($lecteur)->test(CommentaireListe::class)
        ->call('changerLeStatut', $commentaire->id, Commentaire::REJETE)
        ->assertForbidden();

    Livewire::actingAs($lecteur)->test(CommentaireListe::class)
        ->call('supprimer', $commentaire->id)
        ->assertForbidden();

    expect($commentaire->fresh()->statut)->toBe(Commentaire::PUBLIE);
});

it('embarque la moderation dans l ecran de la page Actualites', function () {
    Livewire::actingAs($this->admin)->test(PageActualites::class)
        ->call('ouvrir', 'commentaires')
        ->assertSee('wire:name="admin.commentaire-liste"', false);
});

/* ------------------------------------------------------------------ */
/* L'interrupteur par article */
/* ------------------------------------------------------------------ */

it('ferme les commentaires depuis la fiche de l article', function () {
    Livewire::actingAs($this->admin)
        ->test(ArticleFormulaire::class, ['article' => $this->article])
        ->assertSet('commentairesOuverts', true)
        ->set('commentairesOuverts', false)
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect($this->article->fresh()->commentaires_ouverts)->toBeFalse();
});

/** Un article cree aujourd'hui se comporte comme le reste du site. */
it('ouvre les commentaires par defaut', function () {
    expect(Article::factory()->create([
        'categorie_id' => Categorie::factory()->create()->id,
    ])->commentaires_ouverts)->toBeTrue();
});
