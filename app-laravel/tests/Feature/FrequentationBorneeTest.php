<?php

use App\Livewire\Admin\Frequentation;
use App\Models\PageQuotidienne;
use App\Models\User;
use App\Models\Visite;
use App\Models\VisiteurQuotidien;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * La table des visites recevait une ligne par page vue, sans rien pour en
 * borner la croissance. Ces tests decrivent ce qui la borne : une agregation
 * quotidienne, puis la purge du detail au-dela de quatre-vingt-dix jours.
 *
 * La garde qui compte est la derniere : un jour dont l'agregat n'existe pas ne
 * doit JAMAIS etre purge. Sans elle, un cron en panne ne se verrait pas — il
 * detruirait simplement les visites au fil des jours, en silence.
 */

/** Depose une visite a une date donnee, sans passer par le middleware. */
function visite(string $chemin, string $empreinte, string $quand): Visite
{
    return Visite::create([
        'chemin' => $chemin,
        'session_hash' => str_pad($empreinte, 64, '0'),
        'visitee_le' => $quand,
    ]);
}

it('agrege les pages vues d un jour clos', function () {
    $hier = now()->subDay()->format('Y-m-d');

    visite('/biens', 'a', $hier.' 10:00:00');
    visite('/biens', 'b', $hier.' 11:00:00');
    visite('/faq', 'a', $hier.' 12:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(PageQuotidienne::where('jour', $hier)->where('chemin', '/biens')->value('pages_vues'))->toBe(2)
        ->and(PageQuotidienne::where('jour', $hier)->where('chemin', '/faq')->value('pages_vues'))->toBe(1);
});

it('compte un visiteur une seule fois, quel que soit le nombre de pages vues', function () {
    $hier = now()->subDay()->format('Y-m-d');

    // C'est la raison d'etre de la seconde table : additionner les visiteurs de
    // chaque page compterait celui-ci trois fois.
    visite('/', 'a', $hier.' 09:00:00');
    visite('/biens', 'a', $hier.' 09:05:00');
    visite('/faq', 'a', $hier.' 09:10:00');
    visite('/', 'b', $hier.' 14:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(VisiteurQuotidien::where('jour', $hier)->value('visiteurs'))->toBe(2);
});

it('purge le detail au-dela de quatre-vingt-dix jours, en gardant le comptage', function () {
    $vieux = now()->subDays(120)->format('Y-m-d');
    $recent = now()->subDays(10)->format('Y-m-d');

    visite('/biens', 'a', $vieux.' 10:00:00');
    visite('/biens', 'b', $vieux.' 11:00:00');
    visite('/faq', 'a', $recent.' 10:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(Visite::whereDate('visitee_le', $vieux)->count())->toBe(0)
        ->and(Visite::whereDate('visitee_le', $recent)->count())->toBe(1)
        // Ce que la purge a jete reste lisible dans l'agregat : c'est tout
        // l'objet de l'operation.
        ->and(PageQuotidienne::where('jour', $vieux)->where('chemin', '/biens')->value('pages_vues'))->toBe(2)
        ->and(VisiteurQuotidien::where('jour', $vieux)->value('visiteurs'))->toBe(2);
});

it('ne purge rien si l agregation echoue', function () {
    $vieux = now()->subDays(120)->format('Y-m-d');
    visite('/biens', 'a', $vieux.' 10:00:00');

    // Une agregation qui tombe en cours de route est le seul scenario ou la
    // purge deviendrait une perte de donnees. Elle doit donc s'arreter la, et
    // le dire par son code de retour — un cron muet qui detruit est pire qu'un
    // cron qui echoue bruyamment.
    Schema::drop('pages_quotidiennes');

    $this->artisan('frequentation:agreger')->assertFailed();

    expect(Visite::count())->toBe(1);
});

it('ne double rien quand on la rejoue', function () {
    $hier = now()->subDay()->format('Y-m-d');
    visite('/biens', 'a', $hier.' 10:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();
    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(PageQuotidienne::where('jour', $hier)->count())->toBe(1)
        ->and(PageQuotidienne::where('jour', $hier)->value('pages_vues'))->toBe(1);
});

it('rattrape plusieurs jours d un coup', function () {
    // Un cron muet pendant une semaine ne doit pas laisser un trou definitif :
    // l'execution suivante traite tous les jours clos, pas seulement hier.
    foreach ([5, 4, 3, 2, 1] as $recul) {
        visite('/biens', 'a', now()->subDays($recul)->format('Y-m-d').' 10:00:00');
    }

    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(VisiteurQuotidien::count())->toBe(5);
});

it("n'efface pas l'historique quand le detail a disparu", function () {
    // Le piege : apres la purge, une seconde execution recompte a partir d'un
    // detail devenu vide. Si elle ecrivait ce comptage, elle remettrait a zero
    // l'histoire qu'elle venait de sauver.
    $vieux = now()->subDays(120)->format('Y-m-d');
    visite('/biens', 'a', $vieux.' 10:00:00');
    visite('/biens', 'b', $vieux.' 11:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();
    expect(Visite::count())->toBe(0);

    $this->artisan('frequentation:agreger')->assertSuccessful();

    expect(PageQuotidienne::where('jour', $vieux)->value('pages_vues'))->toBe(2)
        ->and(VisiteurQuotidien::where('jour', $vieux)->value('visiteurs'))->toBe(2);
});

it('laisse intact le detail du jour en cours', function () {
    visite('/biens', 'a', now()->format('Y-m-d').' 10:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();

    // Agreger un jour encore ouvert donnerait un comptage partiel, que la
    // prochaine execution devrait corriger. Son detail est de toute facon la.
    expect(Visite::count())->toBe(1)
        ->and(VisiteurQuotidien::count())->toBe(0);
});

it('montre encore l historique une fois le detail purge', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    $vieux = now()->subDays(200)->format('Y-m-d');
    visite('/biens', 'a', $vieux.' 10:00:00');
    visite('/biens', 'b', $vieux.' 11:00:00');

    $this->artisan('frequentation:agreger')->assertSuccessful();
    expect(Visite::count())->toBe(0);

    // Sans lecture des agregats, l'ecran ne verrait plus que les quatre-vingt-dix
    // derniers jours : la purge lui aurait pris son histoire au lieu de la lui
    // resumer.
    $ecran = Livewire::actingAs($admin)->test(Frequentation::class)->set('periode', 365);

    expect($ecran->viewData('total'))->toBe(2)
        ->and($ecran->viewData('visiteurs'))->toBe(2)
        ->and($ecran->viewData('pages')->firstWhere('chemin', '/biens')->total)->toBe(2);
});

it('annonce que le chiffre des visiteurs devient un cumul, et seulement alors', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    // Sur la longue periode le chiffre cesse d'etre un nombre de personnes.
    // L'afficher sans le dire serait montrer le nombre le plus flatteur.
    Livewire::actingAs($admin)->test(Frequentation::class)
        ->set('periode', 365)
        ->assertSee('Cumul des visiteurs de chaque jour')
        ->set('periode', 90)
        ->assertDontSee('Cumul des visiteurs de chaque jour');
});

it('est planifiee chaque jour', function () {
    // Une commande d'entretien que personne ne lance n'entretient rien : sans
    // cette declaration, la table recommencerait a croitre sans borne.
    $planifiee = collect(app(Schedule::class)->events())
        ->first(fn ($evenement) => str_contains((string) $evenement->command, 'frequentation:agreger'));

    expect($planifiee)->not->toBeNull()
        ->and($planifiee->expression)->toBe('10 3 * * *');
});
