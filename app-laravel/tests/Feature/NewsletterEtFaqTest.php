<?php

use App\Livewire\Admin\AbonneNewsletterListe;
use App\Models\AbonneNewsletter;
use App\Models\MessageDeContact;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les deux derniers formulaires publics qui perdaient tout.
 *
 * La question de FAQ partait sur WhatsApp et la newsletter ouvrait un lien
 * « mailto: » — c'est-a-dire rien du tout sur la plupart des telephones, ou
 * aucun compte de courrier n'est configure. Dans les deux cas l'agence perdait
 * des prospects sans jamais savoir combien.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->editeur = User::factory()->create(['statut' => User::ACTIF]);
    $this->editeur->assignRole('editeur');

    $this->lecteur = User::factory()->create(['statut' => User::ACTIF]);
    $this->lecteur->assignRole('lecteur');
});

/* ------------------------------------------------ question de FAQ */

it('enregistre une question posee depuis la FAQ', function () {
    Mail::fake();

    $this->postJson('/messages', [
        'nom' => 'Awa Sylla',
        'email' => 'awa@exemple.ci',
        'message' => 'Travaillez-vous avec des banques ?',
        'source' => 'faq',
    ])->assertCreated();

    $message = MessageDeContact::first();

    // Une question de FAQ EST un message de contact — meme expediteur, meme
    // reponse attendue, meme ecran. Seule son origine differe.
    expect($message->source)->toBe(MessageDeContact::DE_FAQ)
        ->and($message->nom)->toBe('Awa Sylla');
});

it('marque contact par defaut quand la source n est pas dite', function () {
    Mail::fake();

    $this->postJson('/messages', ['nom' => 'Léon', 'message' => 'Bonjour'])->assertCreated();

    expect(MessageDeContact::first()->source)->toBe(MessageDeContact::DE_CONTACT);
});

it('refuse une source inventee', function () {
    Mail::fake();

    $this->postJson('/messages', [
        'nom' => 'Intrus',
        'message' => 'Bonjour',
        'source' => 'formulaire-fantome',
    ])->assertStatus(422);

    expect(MessageDeContact::count())->toBe(0);
});

/* ------------------------------------------------ newsletter */

it('enregistre une inscription a la newsletter', function () {
    $this->postJson('/newsletter', ['email' => 'Leon@Exemple.CI'])->assertCreated();

    // L'adresse est rangee en minuscules : « Leon@ » et « leon@ » sont la meme
    // boite, et la contrainte d'unicite ne le sait pas toute seule.
    expect(AbonneNewsletter::first()->email)->toBe('leon@exemple.ci');
});

it('ne cree pas de doublon quand la meme adresse revient', function () {
    $this->postJson('/newsletter', ['email' => 'leon@exemple.ci'])->assertCreated();
    $this->postJson('/newsletter', ['email' => 'leon@exemple.ci'])->assertCreated();

    // La reponse est la MEME dans les deux cas : repondre « vous etes deja
    // inscrit » dirait a un inconnu quelles adresses figurent dans la liste.
    expect(AbonneNewsletter::count())->toBe(1);
});

it('reinscrit une adresse qui etait partie', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);
    $abonne->forceFill(['desinscrit_a' => now()->subDay()])->save();

    $this->postJson('/newsletter', ['email' => 'leon@exemple.ci'])->assertCreated();

    expect($abonne->fresh()->desinscrit_a)->toBeNull();
});

it('refuse une adresse mal formee', function () {
    $this->postJson('/newsletter', ['email' => 'pas-une-adresse'])->assertStatus(422);

    expect(AbonneNewsletter::count())->toBe(0);
});

it('refuse une inscription qui remplit le champ piege', function () {
    $this->postJson('/newsletter', [
        'email' => 'robot@spam.example',
        'site_web' => 'http://spam.example',
    ])->assertStatus(422);

    expect(AbonneNewsletter::count())->toBe(0);
});

it('ne renvoie pas l adresse recue', function () {
    $reponse = $this->postJson('/newsletter', ['email' => 'leon@exemple.ci']);

    expect($reponse->json())->toBe(['inscrit' => true]);
});

/* ------------------------------------------------ ecran des abonnes */

it('ouvre l ecran des abonnes', function () {
    $this->actingAs($this->editeur)->get('/admin/newsletter')->assertOk();
});

it('masque les desinscrits par defaut', function () {
    AbonneNewsletter::create(['email' => 'actif@exemple.ci']);
    $parti = AbonneNewsletter::create(['email' => 'parti@exemple.ci']);
    $parti->forceFill(['desinscrit_a' => now()])->save();

    $corps = Livewire::actingAs($this->editeur)->test(AbonneNewsletterListe::class)->html();

    expect($corps)->toContain('actif@exemple.ci')
        ->and($corps)->not->toContain('parti@exemple.ci');
});

it('desinscrit sans effacer la ligne', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    Livewire::actingAs($this->editeur)
        ->test(AbonneNewsletterListe::class)
        ->call('basculerLAbonnement', $abonne->id);

    // Garder la trace du retrait est ce qui empeche de reinscrire par erreur
    // quelqu'un qui est parti.
    expect($abonne->fresh())->not->toBeNull()
        ->and($abonne->fresh()->desinscrit_a)->not->toBeNull();
});

it('refuse a un lecteur de desinscrire', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    Livewire::actingAs($this->lecteur)
        ->test(AbonneNewsletterListe::class)
        ->call('basculerLAbonnement', $abonne->id)
        ->assertForbidden();

    expect($abonne->fresh()->desinscrit_a)->toBeNull();
});

it('n exporte que les adresses actives', function () {
    AbonneNewsletter::create(['email' => 'actif@exemple.ci']);
    $parti = AbonneNewsletter::create(['email' => 'parti@exemple.ci']);
    $parti->forceFill(['desinscrit_a' => now()])->save();

    $reponse = Livewire::actingAs($this->editeur)
        ->test(AbonneNewsletterListe::class)
        ->call('exporter');

    ob_start();
    $reponse->effects['download'] ?? null;
    ob_end_clean();

    // Exporter une adresse desinscrite reviendrait a lui reecrire, ce que la
    // desinscription interdit precisement.
    expect(AbonneNewsletter::actifs()->pluck('email')->all())->toBe(['actif@exemple.ci']);
});

/* ------------------------------------------------ retrait par l'abonne */

/*
 * On pouvait s'inscrire, et l'ecran du backoffice pouvait desinscrire
 * quelqu'un — mais l'abonne n'avait aucun moyen de partir sans le demander a
 * l'agence. C'est le sens meme du droit de retrait : il ne doit dependre de
 * personne.
 */
it('donne a chaque abonne son adresse de retrait', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    expect($abonne->jeton)->not->toBeEmpty()
        ->and(strlen($abonne->jeton))->toBeGreaterThanOrEqual(32)
        // Le jeton, et NON l'adresse : un lien construit sur l'adresse
        // permettrait de desinscrire n'importe qui en la devinant.
        ->and($abonne->lienDeDesinscription())->not->toContain('leon@exemple.ci')
        ->and($abonne->lienDeDesinscription())->toContain($abonne->jeton);
});

it('ne desinscrit pas au simple chargement du lien', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    // Le point sensible : les antivirus de messagerie et les apercus de lien
    // VISITENT les adresses d'un message pour les inspecter. Un retrait
    // declenche par un GET partirait au premier d'entre eux.
    $this->get($abonne->lienDeDesinscription())->assertOk();

    expect($abonne->fresh()->desinscrit_a)->toBeNull();
});

it('desinscrit quand l abonne confirme', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    $this->post(route('newsletter.desinscription.retirer', $abonne->jeton))
        ->assertRedirect($abonne->lienDeDesinscription());

    expect($abonne->fresh()->desinscrit_a)->not->toBeNull()
        ->and(AbonneNewsletter::actifs()->count())->toBe(0);
});

it('ne repousse pas la date d un retrait deja enregistre', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);
    $abonne->forceFill(['desinscrit_a' => now()->subMonth()])->save();
    $premier = $abonne->fresh()->desinscrit_a;

    $this->post(route('newsletter.desinscription.retirer', $abonne->jeton));

    // Le premier retrait est celui qui compte, et c'est lui qu'on doit pouvoir
    // montrer si l'interesse le demande.
    expect($abonne->fresh()->desinscrit_a->timestamp)->toBe($premier->timestamp);
});

it('repond pareil pour un jeton inconnu', function () {
    // Distinguer les deux cas dirait a qui essaie des jetons lesquels
    // correspondent a une adresse inscrite.
    $this->get(route('newsletter.desinscription', 'jeton-invente'))->assertOk();

    $this->post(route('newsletter.desinscription.retirer', 'jeton-invente'))
        ->assertRedirect(route('newsletter.desinscription', 'jeton-invente'));
});

it('porte le lien de retrait dans l export', function () {
    // Les lettres partent d'un outil externe alimente par cet export : c'est le
    // SEUL chemin par ou le lien peut atteindre le pied des messages.
    $abonne = AbonneNewsletter::create(['email' => 'actif@exemple.ci']);

    $flux = Livewire::actingAs($this->editeur)
        ->test(AbonneNewsletterListe::class)
        ->instance()
        ->exporter();

    ob_start();
    $flux->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('lien_desinscription')
        ->and($csv)->toContain($abonne->lienDeDesinscription());
});

it('propose la newsletter dans la barre laterale', function () {
    expect($this->actingAs($this->editeur)->get('/dashboard')->getContent())
        ->toContain('/admin/newsletter');
});

it('ne montre plus les liens du kit de demarrage', function () {
    // « Repository » et « Documentation » menaient au depot GitHub et a la
    // documentation de Laravel : aucun rapport avec l'administration d'une
    // agence immobiliere.
    $corps = $this->actingAs($this->editeur)->get('/dashboard')->getContent();

    expect($corps)->not->toContain('livewire-starter-kit')
        ->and($corps)->not->toContain('laravel.com/docs');
});

it('renvoie admin vers le tableau de bord qui montre quelque chose', function () {
    // /admin servait une page souche du lot 1 pendant que le vrai tableau de
    // bord vivait ailleurs.
    $this->actingAs($this->editeur)->get('/admin')->assertRedirect('/dashboard');
});
