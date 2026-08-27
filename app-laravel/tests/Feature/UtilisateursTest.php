<?php

use App\Livewire\Admin\UtilisateurListe;
use App\Mail\InvitationAuBackoffice;
use App\Models\Article;
use App\Models\Categorie;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Comptes du backoffice.
 *
 * Le seul ecran du projet qui donne ou retire un acces. Les tests portent
 * d'abord sur les facons de se fermer la porte a soi-meme, puis sur le fait
 * qu'aucun mot de passe ne transite par ici.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create(['name' => 'Admin Un', 'statut' => User::ACTIF]);
    $this->admin->assignRole('administrateur');

    $this->secondAdmin = User::factory()->create(['name' => 'Admin Deux', 'statut' => User::ACTIF]);
    $this->secondAdmin->assignRole('administrateur');

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');
});

it('reserve l ecran aux administrateurs', function () {
    $this->actingAs($this->editeur)->get('/admin/utilisateurs')->assertForbidden();
    $this->actingAs($this->admin)->get('/admin/utilisateurs')->assertOk();

    Livewire::actingAs($this->editeur)->test(UtilisateurListe::class)->assertForbidden();
});

/* ------------------------------------------------------- invitation */

it('invite sans jamais fixer de mot de passe connu', function () {
    Mail::fake();

    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Awa Sylla')
        ->set('emailInvite', 'awa@sci4k.test')
        ->set('roleInvite', 'redacteur')
        ->call('inviter')
        ->assertHasNoErrors();

    $compte = User::where('email', 'awa@sci4k.test')->first();

    expect($compte)->not->toBeNull()
        ->and($compte->statut)->toBe(User::INVITE)
        ->and($compte->hasRole('redacteur'))->toBeTrue()
        // Le point sensible : le compte existe, mais personne — pas meme
        // l'administrateur qui invite — ne connait son mot de passe.
        ->and($compte->derniere_connexion_a)->toBeNull();

    Mail::assertSent(InvitationAuBackoffice::class, fn ($m) => $m->hasTo('awa@sci4k.test'));
});

it('refuse d inviter une adresse deja connue', function () {
    Mail::fake();

    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Doublon')
        ->set('emailInvite', $this->editeur->email)
        ->call('inviter')
        ->assertHasErrors('emailInvite');

    Mail::assertNothingSent();
});

it('refuse un role invente a l invitation', function () {
    Mail::fake();

    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('ouvrirInvitation')
        ->set('nomInvite', 'Intrus')
        ->set('emailInvite', 'intrus@sci4k.test')
        ->set('roleInvite', 'super-administrateur')
        ->call('inviter')
        ->assertHasErrors('roleInvite');

    expect(User::where('email', 'intrus@sci4k.test')->exists())->toBeFalse();
});

/* -------------------------------------------- se fermer la porte */

it('refuse a un administrateur de changer son propre role', function () {
    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('changerLeRole', $this->admin->id, 'lecteur');

    expect($this->admin->fresh()->hasRole('administrateur'))->toBeTrue();
});

it('refuse a un administrateur de se desactiver', function () {
    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('basculerLActivation', $this->admin->id);

    expect($this->admin->fresh()->statut)->toBe(User::ACTIF);
});

it('refuse a un administrateur de se supprimer', function () {
    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('supprimer', $this->admin->id);

    expect(User::find($this->admin->id))->not->toBeNull();
});

it('refuse de retrograder le dernier administrateur actif', function () {
    // Le second administrateur est desactive : il n'en reste qu'un en etat de
    // se connecter. Le retrograder ne laisserait personne pour gerer les
    // comptes ni la configuration.
    $this->secondAdmin->forceFill(['statut' => User::INACTIF])->save();

    Livewire::actingAs($this->secondAdmin)
        ->test(UtilisateurListe::class)
        ->call('changerLeRole', $this->admin->id, 'editeur');

    expect($this->admin->fresh()->hasRole('administrateur'))->toBeTrue();
});

it('accepte de retrograder un administrateur quand il en reste un autre', function () {
    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('changerLeRole', $this->secondAdmin->id, 'editeur');

    expect($this->secondAdmin->fresh()->hasRole('editeur'))->toBeTrue()
        ->and($this->secondAdmin->fresh()->hasRole('administrateur'))->toBeFalse();
});

/* ------------------------------------------------ activation */

it('desactive puis reactive un compte', function () {
    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('basculerLActivation', $this->editeur->id);

    expect($this->editeur->fresh()->statut)->toBe(User::INACTIF);

    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('basculerLActivation', $this->editeur->id);

    // Il ne s'est jamais connecte : il redevient « invite » et non « actif »,
    // sans quoi l'ecran laisserait croire qu'il a deja ouvert une session.
    expect($this->editeur->fresh()->statut)->toBe(User::INVITE);
});

it('rend un compte reactive a son etat actif s il s etait deja connecte', function () {
    // forceFill : `derniere_connexion_a` n'est volontairement PAS assignable
    // en masse — c'est une date que le serveur pose, jamais un formulaire. Le
    // premier jet de ce test employait update() et la colonne restait nulle.
    $this->editeur->forceFill(['derniere_connexion_a' => now()->subDay()])->save();

    $composant = Livewire::actingAs($this->admin)->test(UtilisateurListe::class);
    $composant->call('basculerLActivation', $this->editeur->id);
    $composant->call('basculerLActivation', $this->editeur->id);

    expect($this->editeur->fresh()->statut)->toBe(User::ACTIF);
});

/* ------------------------------------------------ connexion */

it('empeche un compte desactive de se connecter', function () {
    $this->editeur->forceFill(['statut' => User::INACTIF, 'password' => bcrypt('motdepasse-connu')])->save();

    // Le point sensible : la porte, pas les meubles. Un compte desactive doit
    // echouer a l'AUTHENTIFICATION, et non simplement voir moins d'ecrans.
    $this->post('/login', [
        'email' => $this->editeur->email,
        'password' => 'motdepasse-connu',
    ]);

    $this->assertGuest();
});

it('laisse un compte actif se connecter et note la date', function () {
    $this->editeur->forceFill(['password' => bcrypt('motdepasse-connu'), 'derniere_connexion_a' => null])->save();

    $this->post('/login', [
        'email' => $this->editeur->email,
        'password' => 'motdepasse-connu',
    ]);

    $this->assertAuthenticatedAs($this->editeur->fresh());
    expect($this->editeur->fresh()->derniere_connexion_a)->not->toBeNull();
});

it('fait passer une invitation en compte actif a la premiere connexion', function () {
    $invite = User::factory()->create([
        'statut' => User::INVITE,
        'password' => bcrypt('motdepasse-choisi'),
    ]);
    $invite->assignRole('redacteur');

    $this->post('/login', ['email' => $invite->email, 'password' => 'motdepasse-choisi']);

    // C'est la connexion qui prouve l'acceptation, pas l'envoi du courriel.
    expect($invite->fresh()->statut)->toBe(User::ACTIF);
});

/* ------------------------------------------------ suppression */

it('supprime un compte sans emporter ses articles', function () {
    $article = Article::factory()
        ->for(Categorie::factory())
        ->create(['auteur_id' => $this->editeur->id]);

    Livewire::actingAs($this->admin)
        ->test(UtilisateurListe::class)
        ->call('supprimer', $this->editeur->id);

    expect(User::find($this->editeur->id))->toBeNull()
        // Le site ne perd pas de contenu en ligne parce qu'un employe est parti.
        ->and($article->fresh())->not->toBeNull()
        ->and($article->fresh()->auteur_id)->toBeNull();
});

it('renvoie une invitation, mais pas a un compte deja connecte', function () {
    Mail::fake();

    $invite = User::factory()->create(['statut' => User::INVITE]);
    $invite->assignRole('lecteur');

    $composant = Livewire::actingAs($this->admin)->test(UtilisateurListe::class);
    $composant->call('renvoyerLInvitation', $invite->id);
    Mail::assertSent(InvitationAuBackoffice::class, 1);

    $composant->call('renvoyerLInvitation', $this->editeur->id);
    Mail::assertSent(InvitationAuBackoffice::class, 1);
});

it('propose l ecran des utilisateurs dans la barre laterale', function () {
    expect($this->actingAs($this->admin)->get('/dashboard')->getContent())
        ->toContain('/admin/utilisateurs');
});

it('n annonce aucun role pour un compte qui n en a pas', function () {
    // Un « select » sans option correspondante retombe sur la PREMIERE, ici
    // « Administrateur » : l'ecran des droits annoncait le droit le plus large
    // a un compte qui n'en avait aucun. Constate a l'ecran, pas par un test.
    $sansRole = User::factory()->create(['name' => 'Sans Role', 'statut' => User::ACTIF]);

    $corps = Livewire::actingAs($this->admin)->test(UtilisateurListe::class)->html();

    $ligne = mb_substr($corps, mb_strpos($corps, 'compte-'.$sansRole->id));
    $ligne = mb_substr($ligne, 0, mb_strpos($ligne, '</tr>'));

    expect($ligne)->toContain(__('Aucun rôle'))
        ->and($ligne)->not->toContain('value="administrateur" selected');
});
