<?php

use App\Livewire\Admin\JournalActivite;
use App\Models\ActiviteJournalisee;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\Temoignage;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Le journal des activites.
 *
 * Le tableau de bord DEDUISAIT auparavant son « activite recente » du champ
 * `updated_at` de chaque famille. Il ne pouvait donc dire ni ce qui s'etait
 * passe, ni qui l'avait fait, et il perdait toute trace d'un element supprime.
 * Ces tests portent sur ces trois manques.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create(['name' => 'Emma Diarra', 'statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');

    $this->categorie = Categorie::factory()->create();
});

it('inscrit une creation, avec son auteur', function () {
    $this->actingAs($this->editeur);

    $temoignage = Temoignage::factory()->create(['auteur' => 'Léon Kouassi']);

    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->action)->toBe(ActiviteJournalisee::CREATION)
        ->and($ligne->sujet_intitule)->toBe('Léon Kouassi')
        ->and($ligne->sujet_id)->toBe($temoignage->id)
        // Le point que l'ancienne version ne pouvait pas donner : QUI.
        ->and($ligne->user_id)->toBe($this->editeur->id)
        ->and($ligne->auteur_nom)->toBe('Emma Diarra');
});

it('inscrit une modification', function () {
    $temoignage = Temoignage::factory()->create();
    $this->actingAs($this->editeur);

    $temoignage->update(['auteur' => 'Nom corrigé']);

    expect(ActiviteJournalisee::recentes()->first()->action)
        ->toBe(ActiviteJournalisee::MODIFICATION);
});

it('n inscrit rien quand un enregistrement ne change rien', function () {
    $temoignage = Temoignage::factory()->create();
    $this->actingAs($this->editeur);

    $avant = ActiviteJournalisee::count();

    // Ouvrir une fiche et la refermer n'est pas une modification. L'ancienne
    // version, qui lisait `updated_at`, faisait pourtant remonter le contenu
    // en tete de liste.
    $temoignage->touch();
    $temoignage->update(['auteur' => $temoignage->auteur]);

    expect(ActiviteJournalisee::count())->toBe($avant);
});

it('distingue une publication d une simple modification', function () {
    $this->actingAs($this->editeur);

    $article = Article::factory()->create([
        'categorie_id' => $this->categorie->id,
        'statut' => 'brouillon',
    ]);

    $article->update(['statut' => 'publie']);

    // C'est le seul changement que le visiteur du site remarque : le journal
    // ne doit pas le noyer parmi les corrections de virgules.
    expect(ActiviteJournalisee::recentes()->first()->action)
        ->toBe(ActiviteJournalisee::PUBLICATION);
});

it('garde la trace d un element supprime, et son intitule', function () {
    $this->actingAs($this->editeur);

    $temoignage = Temoignage::factory()->create(['auteur' => 'Awa Sylla']);
    $temoignage->delete();

    $ligne = ActiviteJournalisee::recentes()->first();

    // L'intitule est RECOPIE : une ligne doit rester lisible apres la
    // disparition de ce qu'elle decrit. C'est meme le cas ou le journal sert
    // le plus, et celui que l'ancienne version perdait entierement.
    expect($ligne->action)->toBe(ActiviteJournalisee::SUPPRESSION)
        ->and($ligne->sujet_intitule)->toBe('Awa Sylla')
        ->and($ligne->lienDEdition())->toBeNull();
});

it('ne propose pas de lien vers un contenu disparu', function () {
    $this->actingAs($this->editeur);

    $temoignage = Temoignage::factory()->create();
    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->lienDEdition())->not->toBeNull();

    // Supprime sans passer par l'ecran — un import, une cascade. Le lien doit
    // s'effacer, sans quoi un clic rendrait une page d'erreur.
    $temoignage->forceDelete();

    expect($ligne->fresh()->lienDEdition())->toBeNull();
});

it('inscrit une action faite hors session sans auteur', function () {
    // Un import en ligne de commande n'a pas d'utilisateur connecte. La ligne
    // doit exister quand meme, et le dire.
    Temoignage::factory()->create(['auteur' => 'Import']);

    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->user_id)->toBeNull()
        ->and($ligne->auteur_nom)->toBeNull();
});

it('nomme la famille en clair, jamais par sa classe', function () {
    $this->actingAs($this->editeur);
    Temoignage::factory()->create();

    $ligne = ActiviteJournalisee::recentes()->first();

    // « App\Models\Temoignage » ne dit rien a un editeur.
    expect($ligne->famille())->toBe(__('Témoignage'))
        ->and($ligne->famille())->not->toContain('\\');
});

/* ------------------------------------------------------------ ecran */

it('affiche le journal a un lecteur', function () {
    $lecteur = User::factory()->create(['statut' => User::ACTIF]);
    $lecteur->assignRole('lecteur');

    // Savoir qui a touche a quoi n'est pas un privilege.
    $this->actingAs($lecteur)->get('/admin/journal')->assertOk();
});

it('montre qui a fait quoi', function () {
    $this->actingAs($this->editeur);
    Temoignage::factory()->create(['auteur' => 'Ibrahim Cissé']);

    $corps = Livewire::actingAs($this->editeur)->test(JournalActivite::class)->html();

    expect($corps)->toContain('Ibrahim Cissé')
        ->and($corps)->toContain('Emma Diarra')
        ->and($corps)->toContain(ucfirst(__('créé')));
});

it('filtre par action', function () {
    $this->actingAs($this->editeur);

    Temoignage::factory()->create(['auteur' => 'Celui cree']);
    $autre = Temoignage::factory()->create(['auteur' => 'Celui supprime']);
    $autre->delete();

    $corps = Livewire::actingAs($this->editeur)
        ->test(JournalActivite::class)
        ->set('action', ActiviteJournalisee::SUPPRESSION)
        ->html();

    expect($corps)->toContain('Celui supprime')
        ->and($corps)->not->toContain('Celui cree');
});

it('ne propose comme auteurs que les comptes ayant agi', function () {
    $jamaisAgi = User::factory()->create(['name' => 'Fantome Inactif', 'statut' => User::ACTIF]);
    $jamaisAgi->assignRole('lecteur');

    $this->actingAs($this->editeur);
    Temoignage::factory()->create();

    $corps = Livewire::actingAs($this->editeur)->test(JournalActivite::class)->html();

    // Lister tous les comptes remplirait le filtre de choix sans resultat.
    expect($corps)->toContain('Emma Diarra')
        ->and($corps)->not->toContain('Fantome Inactif');
});

it('mene le lien Tout afficher vers le journal et non vers les articles', function () {
    // Il menait a la liste des ARTICLES, alors que le panneau resume seize
    // familles : il montrait moins que ce qu'il annonçait.
    $corps = $this->actingAs($this->editeur)->get('/dashboard')->getContent();

    $panneau = mb_substr($corps, mb_strpos($corps, __('Activité récente')));
    $panneau = mb_substr($panneau, 0, mb_strpos($panneau, __('À traiter')) ?: 4000);

    expect($panneau)->toContain('/admin/journal');
});

it('propose le journal dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/journal');
});

/*
 * La hierarchie de lecture.
 *
 * Le premier jet mettait le CONTENU en avant et le compte en petit : on lisait
 * « Mireille K. » — l'auteur d'un temoignage, donc du contenu — comme si
 * c'etait elle qui avait agi. Signale par le client. Un journal d'activites se
 * lit par QUI agit.
 */
it('met le compte qui agit avant le contenu touche', function () {
    $this->actingAs($this->editeur);
    Temoignage::factory()->create(['auteur' => 'Mireille K.']);

    $corps = $this->actingAs($this->editeur)->get('/dashboard')->getContent();

    $panneau = mb_substr($corps, mb_strpos($corps, __('Activité récente')));
    $panneau = mb_substr($panneau, 0, mb_strpos($panneau, __('Tout afficher')));

    // Mesure au point sensible : la POSITION relative des deux noms dans le
    // panneau, et non leur simple presence.
    expect(mb_strpos($panneau, 'Emma Diarra'))->toBeLessThan(mb_strpos($panneau, 'Mireille K.'));
});

it('enonce l action en une phrase du point de vue du compte', function () {
    $this->actingAs($this->editeur);
    Temoignage::factory()->create(['auteur' => 'Mireille K.']);

    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->nomDeLAuteur())->toBe('Emma Diarra')
        ->and($ligne->initialesDeLAuteur())->toBe('ED')
        // L'article est porte par la famille : « a créé LE témoignage », et non
        // « a créé témoignage », qui ne se dit pas.
        ->and($ligne->phrase())->toBe(__('a créé :famille', ['famille' => __('le témoignage')]));
});

it('nomme clairement une action faite hors session', function () {
    // Sans compte connecte, la ligne ne doit pas paraitre anonyme ni vide :
    // elle dit que la machine a agi.
    Temoignage::factory()->create();

    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->nomDeLAuteur())->toBe(__('Import ou tâche automatique'))
        ->and($ligne->initialesDeLAuteur())->toBe('⚙');
});

it('garde le nom de l auteur apres la suppression de son compte', function () {
    $partant = User::factory()->create(['name' => 'Marc Touré', 'statut' => User::ACTIF]);
    $partant->assignRole('editeur');

    $this->actingAs($partant);
    Temoignage::factory()->create(['auteur' => 'Nadia Bamba']);

    $partant->delete();

    // La contrainte met `user_id` a nul, mais le nom recopie reste : la ligne
    // continue de dire qui avait agi. C'est pour cela qu'il est recopie.
    $ligne = ActiviteJournalisee::recentes()->first();

    expect($ligne->fresh()->user_id)->toBeNull()
        ->and($ligne->fresh()->nomDeLAuteur())->toBe('Marc Touré');
});
