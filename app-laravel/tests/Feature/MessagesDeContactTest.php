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
        'message' => 'Achetez des montres',
        'site_web' => 'http://spam.example',
    ])->assertStatus(422);

    expect(MessageDeContact::count())->toBe(0);
    Mail::assertNothingSent();
});

it('borne la longueur du message', function () {
    $this->postJson('/messages', [
        'nom' => 'Trop long',
        'message' => str_repeat('a', 5001),
    ])->assertStatus(422);

    expect(MessageDeContact::count())->toBe(0);
});

it('limite le debit des envois', function () {
    Mail::fake();

    // Cinq passent, le sixieme est refuse : sans cela, un envoi automatise
    // remplirait la table en quelques secondes.
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/messages', ['nom' => "Envoi $i", 'message' => 'Bonjour'])
            ->assertCreated();
    }

    $this->postJson('/messages', ['nom' => 'De trop', 'message' => 'Bonjour'])
        ->assertStatus(429);

    expect(MessageDeContact::count())->toBe(5);
});

it('ne renvoie ni le contenu recu ni l identifiant cree', function () {
    Mail::fake();

    $reponse = $this->postJson('/messages', [
        'nom' => 'Léon Kouassi',
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

    $this->postJson('/messages', ['nom' => 'Léon', 'message' => 'Bonjour'])->assertCreated();

    Mail::assertSent(NouveauMessageDeContact::class, fn ($m) => $m->hasTo('agence@sci4k.test'));
});

it('enregistre quand meme si aucun destinataire n est configure', function () {
    Mail::fake();

    // Le backoffice est la source de verite : un courriel non parti ne doit pas
    // faire perdre le message.
    $this->postJson('/messages', ['nom' => 'Léon', 'message' => 'Bonjour'])->assertCreated();

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

    // Le journal rend compte de ce que font les COMPTES du backoffice. Une
    // ligne par visiteur le remplirait de bruit.
    $this->postJson('/messages', ['nom' => 'Léon', 'message' => 'Bonjour'])->assertCreated();

    expect(ActiviteJournalisee::count())->toBe(0);
});
