<?php

use App\Livewire\Admin\DemandeDeVisiteListe;
use App\Models\ActiviteJournalisee;
use App\Models\Bien;
use App\Models\DemandeDeVisite;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Demandes de visite.
 *
 * Seule page de maquette reportee lors du cadrage : chaque ligne se rattache a
 * une fiche de bien, qui n'existait pas. Les tests portent d'abord sur
 * l'entree PUBLIQUE — sans elle, l'ecran aurait ete vide par construction,
 * exactement le defaut releve sur les messages.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->lecteur->assignRole('lecteur');

    $this->bien = Bien::factory()->create(['slug' => 'villa-les-palmiers', 'titre_fr' => 'Villa Les Palmiers']);

    RateLimiter::clear('');
});

/* ------------------------------------------------ reception publique */

it('enregistre une demande venue de la fiche d un bien', function () {
    $this->postJson('/visites', [
        'nom' => 'Léon Kouassi',
        'telephone' => '+225 07 08 11 22 33',
        'email' => 'leon@exemple.ci',
        'bien' => 'villa-les-palmiers',
        'creneau_souhaite' => now()->addWeek()->toDateString(),
    ])->assertCreated();

    $demande = DemandeDeVisite::first();

    expect($demande->bien_id)->toBe($this->bien->id)
        // Le titre est RECOPIE : la ligne doit rester lisible quand le bien
        // sera vendu puis retire du catalogue.
        ->and($demande->bien_intitule)->toBe('Villa Les Palmiers')
        ->and($demande->statut)->toBe(DemandeDeVisite::A_CONFIRMER);
});

it('designe le bien par son adresse et jamais par son numero', function () {
    // Un identifiant numerique viendrait du navigateur et pourrait pointer
    // n'importe quelle ligne, y compris un brouillon.
    $brouillon = Bien::factory()->brouillon()->create(['slug' => 'secret', 'titre_fr' => 'Bien non publié']);

    $this->postJson('/visites', [
        'nom' => 'Curieux',
        'telephone' => '+225 01 02 03 04 05',
        'bien' => 'secret',
    ])->assertCreated();

    // Le brouillon n'est pas publie : la demande est conservee, mais sans s'y
    // rattacher.
    expect(DemandeDeVisite::first()->bien_id)->toBeNull()
        ->and(DemandeDeVisite::first()->bien_intitule)->toBe('secret');
});

it('conserve la demande meme si le bien est introuvable', function () {
    // Mieux vaut un rendez-vous sans bien qu'un prospect perdu.
    $this->postJson('/visites', [
        'nom' => 'Léon',
        'telephone' => '+225 07 00 00 00',
        'bien' => 'bien-qui-n-existe-pas',
    ])->assertCreated();

    expect(DemandeDeVisite::count())->toBe(1);
});

it('exige un nom et un telephone', function () {
    // Sans telephone, l'agence ne peut pas rappeler : la demande serait
    // enregistree et inutile.
    $this->postJson('/visites', ['nom' => 'Léon'])->assertStatus(422);

    expect(DemandeDeVisite::count())->toBe(0);
});

it('refuse un creneau deja passe', function () {
    $this->postJson('/visites', [
        'nom' => 'Léon',
        'telephone' => '+225 07 00 00 00',
        'creneau_souhaite' => now()->subWeek()->toDateString(),
    ])->assertStatus(422);
});

it('refuse un envoi qui remplit le champ piege', function () {
    $this->postJson('/visites', [
        'nom' => 'Robot',
        'telephone' => '+225 07 00 00 00',
        'site_web' => 'http://spam.example',
    ])->assertStatus(422);

    expect(DemandeDeVisite::count())->toBe(0);
});

it('limite le debit des demandes', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/visites', ['nom' => "Envoi $i", 'telephone' => '+225 07 00 00 00'])
            ->assertCreated();
    }

    $this->postJson('/visites', ['nom' => 'De trop', 'telephone' => '+225 07 00 00 00'])
        ->assertStatus(429);
});

/* ------------------------------------------------ apres la vente */

it('garde la demande lisible quand le bien quitte le catalogue', function () {
    $this->postJson('/visites', [
        'nom' => 'Léon',
        'telephone' => '+225 07 00 00 00',
        'bien' => 'villa-les-palmiers',
    ])->assertCreated();

    $this->bien->delete();

    $demande = DemandeDeVisite::first();

    // C'est meme la trace de ce qui a mene a la vente.
    expect($demande->fresh()->bien_id)->toBeNull()
        ->and($demande->fresh()->bienLisible())->toBe('Villa Les Palmiers');
});

/* ------------------------------------------------ ecran */

it('ouvre l ecran aux editeurs et aux lecteurs', function () {
    $this->actingAs($this->editeur)->get('/admin/visites')->assertOk();
    $this->actingAs($this->lecteur)->get('/admin/visites')->assertOk();
});

it('change le statut d une demande', function () {
    $demande = DemandeDeVisite::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(DemandeDeVisiteListe::class)
        ->call('changerLeStatut', $demande->id, DemandeDeVisite::CONFIRMEE);

    expect($demande->fresh()->statut)->toBe(DemandeDeVisite::CONFIRMEE);
});

it('refuse un statut invente', function () {
    $demande = DemandeDeVisite::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(DemandeDeVisiteListe::class)
        ->call('changerLeStatut', $demande->id, 'peut-etre')
        ->assertNotFound();
});

it('refuse a un lecteur de changer un statut', function () {
    $demande = DemandeDeVisite::factory()->create();

    Livewire::actingAs($this->lecteur)
        ->test(DemandeDeVisiteListe::class)
        ->call('changerLeStatut', $demande->id, DemandeDeVisite::REALISEE)
        ->assertForbidden();

    expect($demande->fresh()->statut)->toBe(DemandeDeVisite::A_CONFIRMER);
});

it('refuse d assigner une visite a un compte sans acces', function () {
    $demande = DemandeDeVisite::factory()->create();
    $etranger = User::factory()->create();

    Livewire::actingAs($this->editeur)
        ->test(DemandeDeVisiteListe::class)
        ->call('assigner', $demande->id, (string) $etranger->id)
        ->assertNotFound();

    expect($demande->fresh()->assigne_a)->toBeNull();
});

it('ne mesure pas de taux tant que rien n est conclu', function () {
    DemandeDeVisite::factory()->count(3)->create();

    // « 0 % » laisserait croire a un echec, la ou il n'y a rien a mesurer.
    expect(DemandeDeVisite::tauxDeConcretisation())->toBeNull();

    expect(Livewire::actingAs($this->editeur)->test(DemandeDeVisiteListe::class)->html())
        ->toContain('—');
});

it('mesure le taux sur les demandes conclues', function () {
    DemandeDeVisite::factory()->create(['statut' => DemandeDeVisite::REALISEE]);
    DemandeDeVisite::factory()->create(['statut' => DemandeDeVisite::REALISEE]);
    DemandeDeVisite::factory()->create(['statut' => DemandeDeVisite::ANNULEE]);
    // Celle-ci n'est pas encore conclue : elle ne compte pas.
    DemandeDeVisite::factory()->create();

    expect(DemandeDeVisite::tauxDeConcretisation())->toBe(67.0);
});

it('n inscrit pas les demandes au journal des activites', function () {
    // On mesure l'ECART et non le total : le bien cree par le beforeEach est
    // lui-meme journalise, a juste titre. Compter le total aurait fait passer
    // ce test au rouge pour la mauvaise raison.
    $avant = ActiviteJournalisee::count();

    $this->postJson('/visites', ['nom' => 'Léon', 'telephone' => '+225 07 00 00 00'])->assertCreated();

    // Le journal rend compte de ce que font les COMPTES du backoffice ; une
    // ligne par visiteur le remplirait de bruit.
    expect(ActiviteJournalisee::count())->toBe($avant);
});

it('propose les visites dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/visites');
});
