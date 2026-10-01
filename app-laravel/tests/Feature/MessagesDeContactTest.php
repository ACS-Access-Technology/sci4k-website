<?php

use App\Livewire\Admin\MessageListe;
use App\Mail\NouveauMessageDeContact;
use App\Mail\ReponseAuMessage;
use App\Models\ActiviteJournalisee;
use App\Models\MessageDeContact;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Messages du formulaire de contact.
 *
 * Le point d'entree public est le SEUL point d'ecriture ouvert a tous de toute
 * l'application : les premiers tests portent sur ses protections, avant ceux
 * de l'ecran.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'redacteur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create(['name' => 'Emma Diarra', 'statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->lecteur->assignRole('lecteur');

    RateLimiter::clear('');
});

/* ------------------------------------------------- reception publique */

it('enregistre un message envoye depuis le site', function () {
    Mail::fake();

    $this->postJson('/messages', [
        'nom' => 'Léon Kouassi',
        'telephone' => '+225 07 08 11 22 33',
        'email' => 'leon@exemple.ci',
        'sujet' => 'Villa Cocody',
        'message' => 'Je souhaiterais visiter samedi matin.',
    ])->assertCreated();

    $message = MessageDeContact::first();

    expect($message->nom)->toBe('Léon Kouassi')
        ->and($message->statut)->toBe(MessageDeContact::NOUVEAU);
});

it('accepte un message sans adresse e-mail', function () {
    Mail::fake();

    // Le formulaire du site n'exige que le nom et le telephone : refuser ici
    // perdrait des demandes que le visiteur croit avoir envoyees.
    $this->postJson('/messages', [
        'nom' => 'Sans Courriel',
        'telephone' => '+225 01 02 03 04 05',
        'message' => 'Rappelez-moi.',
    ])->assertCreated();

    expect(MessageDeContact::first()->email)->toBeNull();
});

/* ------------------------------------------------- un moyen de repondre */

/**
 * Telephone OU e-mail : au moins l'un des deux.
 *
 * Les deux etaient facultatifs cote serveur — un message sans aucun moyen de
 * rappeler son auteur entrait dans la boite de l'agence — pendant que le
 * navigateur, lui, exigeait les deux. La regle est desormais la meme partout :
 * l'un ou l'autre suffit, aucun ne suffit pas.
 */
it('accepte un message des qu un moyen de repondre est donne', function (array $coordonnees) {
    Mail::fake();

    $this->postJson('/messages', ['nom' => 'Awa', 'message' => 'Rappelez-moi.'] + $coordonnees)
        ->assertCreated();

    $message = MessageDeContact::sole();
    expect($message->email)->toBe($coordonnees['email'] ?? null)
        ->and($message->telephone)->toBe($coordonnees['telephone'] ?? null);
})->with([
    'e-mail seul' => [['email' => 'awa@exemple.ci']],
    'telephone seul' => [['telephone' => '+225 07 08 11 22 33']],
    'les deux' => [['email' => 'awa@exemple.ci', 'telephone' => '+225 07 08 11 22 33']],
]);

it('refuse un message sans aucun moyen de repondre', function (array $coordonnees) {
    Mail::fake();

    $reponse = $this->postJson('/messages', ['nom' => 'Awa', 'message' => 'Rappelez-moi.'] + $coordonnees)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'telephone']);

    // Une phrase pour le visiteur, pas « The telephone field is required when
    // email is not present ».
    $phrase = 'Indiquez une adresse e-mail ou un numéro de téléphone pour que nous puissions vous répondre.';
    expect($reponse->json('errors.telephone.0'))->toBe($phrase)
        ->and($reponse->json('errors.email.0'))->toBe($phrase)
        ->and(MessageDeContact::count())->toBe(0);
    Mail::assertNothingSent();
})->with([
    'champs absents' => [[]],
    'champs vides' => [['email' => '', 'telephone' => '']],
    'espaces seulement' => [['email' => '   ', 'telephone' => '  ']],
]);

it('garde les regles propres a chaque champ', function (array $coordonnees, string $fautif) {
    $this->postJson('/messages', ['nom' => 'Awa', 'message' => 'Rappelez-moi.'] + $coordonnees)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor($fautif)
        ->assertJsonMissingValidationErrors([$fautif === 'email' ? 'telephone' : 'email']);

    expect(MessageDeContact::count())->toBe(0);
})->with([
    'e-mail invalide, telephone valide' => [['email' => 'pas-une-adresse', 'telephone' => '+225 07 08 11 22 33'], 'email'],
    'e-mail invalide, sans telephone' => [['email' => 'pas-une-adresse'], 'email'],
    'telephone trop long, e-mail valide' => [['email' => 'awa@exemple.ci', 'telephone' => str_repeat('0', 41)], 'telephone'],
]);

it('traduit le message pour un visiteur anglophone', function () {
    // Le formulaire poste sans prefixe de langue : c'est la session qui la
    // porte pour ce point d'entree, comme pour le backoffice.
    $this->withSession(['langue' => 'en'])
        ->postJson('/messages', ['nom' => 'Awa', 'message' => 'Call me back.'])
        ->assertStatus(422)
        ->assertJsonPath('errors.telephone.0', 'Please give an email address or a phone number so that we can reply to you.');
});

it('refuse un message sans nom ni contenu', function () {
    $this->postJson('/messages', ['nom' => '', 'message' => ''])
        ->assertStatus(422);

    expect(MessageDeContact::count())->toBe(0);
});

it('refuse un envoi qui remplit le champ piege', function () {
    Mail::fake();

    // Un robot remplit tous les champs qu'il trouve, y compris celui qu'un
    // humain ne voit pas.
    $this->postJson('/messages', [
        'nom' => 'Robot',
        'email' => 'robot@spam.example',
        'message' => 'Achetez des montres',
        'site_web' => 'http://spam.example',
    ])->assertStatus(422)->assertJsonValidationErrorFor('site_web');

    expect(MessageDeContact::count())->toBe(0);
    Mail::assertNothingSent();
});

it('borne la longueur du message', function () {
    $this->postJson('/messages', [
        'nom' => 'Trop long',
        'telephone' => '+225 01 02 03 04 05',
        'message' => str_repeat('a', 5001),
    ])->assertStatus(422)->assertJsonValidationErrorFor('message');

    expect(MessageDeContact::count())->toBe(0);
});

it('limite le debit des envois', function () {
    Mail::fake();

    // Cinq passent, le sixieme est refuse : sans cela, un envoi automatise
    // remplirait la table en quelques secondes.
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/messages', ['nom' => "Envoi $i", 'telephone' => '0102030405', 'message' => 'Bonjour'])
            ->assertCreated();
    }

    $this->postJson('/messages', ['nom' => 'De trop', 'telephone' => '0102030405', 'message' => 'Bonjour'])
        ->assertStatus(429);

    expect(MessageDeContact::count())->toBe(5);
});

it('ne renvoie ni le contenu recu ni l identifiant cree', function () {
    Mail::fake();

    $reponse = $this->postJson('/messages', [
        'nom' => 'Léon Kouassi',
        'email' => 'leon@exemple.ci',
        'message' => 'Un texte reconnaissable',
    ]);

    // Un identifiant sequentiel dirait a qui le demande combien de messages le
    // site reçoit.
    expect($reponse->json())->toBe(['enregistre' => true])
        ->and($reponse->getContent())->not->toContain('Un texte reconnaissable');
});

it('previent l agence quand un destinataire est configure', function () {
    Mail::fake();
    Parametre::poser('destinataire_formulaire', 'agence@sci4k.test', 'contact');

    $this->postJson('/messages', ['nom' => 'Léon', 'email' => 'leon@exemple.ci', 'message' => 'Bonjour'])->assertCreated();

    Mail::assertSent(NouveauMessageDeContact::class, fn ($m) => $m->hasTo('agence@sci4k.test'));
});

it('enregistre quand meme si aucun destinataire n est configure', function () {
    Mail::fake();

    // Le backoffice est la source de verite : un courriel non parti ne doit pas
    // faire perdre le message.
    $this->postJson('/messages', ['nom' => 'Léon', 'email' => 'leon@exemple.ci', 'message' => 'Bonjour'])->assertCreated();

    expect(MessageDeContact::count())->toBe(1);
    Mail::assertNothingSent();
});

/* ------------------------------------------------------------ ecran */

it('ouvre l ecran aux editeurs et aux lecteurs', function () {
    $this->actingAs($this->editeur)->get('/admin/messages')->assertOk();
    $this->actingAs($this->lecteur)->get('/admin/messages')->assertOk();
});

it('fait passer un message de nouveau a en cours quand on l ouvre', function () {
    $message = MessageDeContact::factory()->create(['statut' => MessageDeContact::NOUVEAU]);

    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('ouvrir', $message->id);

    expect($message->fresh()->statut)->toBe(MessageDeContact::EN_COURS);
});

it('laisse un lecteur consulter sans changer le statut', function () {
    $message = MessageDeContact::factory()->create(['statut' => MessageDeContact::NOUVEAU]);

    Livewire::actingAs($this->lecteur)
        ->test(MessageListe::class)
        ->call('ouvrir', $message->id);

    // Consulter n'est pas traiter : le compteur des non-lus doit refleter ce
    // que personne EN CHARGE n'a encore regarde.
    expect($message->fresh()->statut)->toBe(MessageDeContact::NOUVEAU);
});

it('refuse a un lecteur de changer un statut ou de supprimer', function () {
    $message = MessageDeContact::factory()->create();

    Livewire::actingAs($this->lecteur)
        ->test(MessageListe::class)
        ->call('changerLeStatut', $message->id, MessageDeContact::TRAITE)
        ->assertForbidden();

    Livewire::actingAs($this->lecteur)
        ->test(MessageListe::class)
        ->call('supprimer', $message->id)
        ->assertForbidden();

    expect(MessageDeContact::find($message->id))->not->toBeNull();
});

it('refuse un statut invente', function () {
    $message = MessageDeContact::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('changerLeStatut', $message->id, 'super-traite')
        ->assertNotFound();
});

it('envoie une reponse et note le moment', function () {
    Mail::fake();
    $message = MessageDeContact::factory()->create(['email' => 'leon@exemple.ci', 'repondu_a' => null]);

    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('ouvrir', $message->id)
        ->set('reponse', 'Bonjour, nous vous proposons samedi 10 h.')
        ->call('repondre')
        ->assertHasNoErrors();

    Mail::assertSent(ReponseAuMessage::class, fn ($m) => $m->hasTo('leon@exemple.ci'));

    expect($message->fresh()->statut)->toBe(MessageDeContact::TRAITE)
        ->and($message->fresh()->repondu_a)->not->toBeNull();
});

it('ne recule pas la date de premiere reponse quand on reecrit', function () {
    Mail::fake();
    $message = MessageDeContact::factory()->create(['email' => 'leon@exemple.ci']);

    $composant = Livewire::actingAs($this->editeur)->test(MessageListe::class)->call('ouvrir', $message->id);
    $composant->set('reponse', 'Première réponse.')->call('repondre');

    $premiere = $message->fresh()->repondu_a;

    $this->travel(2)->hours();
    $composant->set('reponse', 'Second message.')->call('repondre');

    // Le delai moyen mesure le temps d'ATTENTE du visiteur : il s'arrete a la
    // premiere reponse, pas a la derniere.
    expect($message->fresh()->repondu_a->timestamp)->toBe($premiere->timestamp);
});

it('ne tente pas d envoi sans adresse e-mail', function () {
    Mail::fake();
    $message = MessageDeContact::factory()->create(['email' => null]);

    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('ouvrir', $message->id)
        ->set('reponse', 'Bonjour')
        ->call('repondre');

    Mail::assertNothingSent();
    expect($message->fresh()->statut)->not->toBe(MessageDeContact::TRAITE);
});

it('refuse d assigner un message a un compte sans acces', function () {
    $message = MessageDeContact::factory()->create();
    $etranger = User::factory()->create();

    // Confier une demande a quelqu'un qui n'entre pas dans le backoffice
    // reviendrait a la perdre.
    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('assigner', $message->id, (string) $etranger->id)
        ->assertNotFound();

    expect($message->fresh()->assigne_a)->toBeNull();
});

it('assigne un message a un collaborateur', function () {
    $message = MessageDeContact::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('assigner', $message->id, (string) $this->editeur->id);

    expect($message->fresh()->assigne_a)->toBe($this->editeur->id);
});

it('echappe le message d un visiteur', function () {
    $message = MessageDeContact::factory()->create([
        'nom' => 'Robot',
        'message' => '<script>alert(1)</script>',
    ]);

    $corps = Livewire::actingAs($this->editeur)
        ->test(MessageListe::class)
        ->call('ouvrir', $message->id)
        ->html();

    // C'est le seul texte du backoffice qu'aucun membre de l'agence n'a ecrit.
    expect($corps)->not->toContain('<script>alert(1)</script>')
        ->and($corps)->toContain('&lt;script&gt;');
});

it('affiche un tiret tant qu aucune reponse n a ete envoyee', function () {
    MessageDeContact::factory()->count(3)->create(['repondu_a' => null]);

    // « 0 h » laisserait croire a une reponse instantanee, la ou il n'y a
    // simplement rien a mesurer.
    expect(MessageDeContact::delaiMoyenDeReponse())->toBeNull();

    $corps = Livewire::actingAs($this->editeur)->test(MessageListe::class)->html();
    expect($corps)->toContain('—');
});

it('propose les messages dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/messages');
});

it('n inscrit pas les messages au journal des activites', function () {
    Mail::fake();

    // On mesure l'ECART et non le total : des creations dans le beforeEach
    // sont elles-meme journalisees. Compter le total aurait fait passer ce
    // test au rouge pour la mauvaise raison.
    $avant = ActiviteJournalisee::count();

    // Le journal rend compte de ce que font les COMPTES du backoffice. Une
    // ligne par visiteur le remplirait de bruit.
    $this->postJson('/messages', ['nom' => 'Léon', 'email' => 'leon@exemple.ci', 'message' => 'Bonjour'])->assertCreated();

    expect(ActiviteJournalisee::count())->toBe($avant);
});
