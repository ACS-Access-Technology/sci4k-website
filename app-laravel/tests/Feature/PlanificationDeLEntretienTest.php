<?php

use App\Livewire\Admin\TableauDeBord;
use App\Models\ExecutionDEntretien;
use App\Models\User;
use App\Support\TachesDEntretien;
use Carbon\CarbonInterface;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Scheduling\Event as TachePlanifiee;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Sentry\CheckInStatus;
use Sentry\Event;
use Sentry\Laravel\Features\ConsoleSchedulingIntegration;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Spatie\Permission\Models\Role;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * L'entretien de nuit : qu'il soit planifie, qu'il ne tourne qu'une fois, et
 * qu'on sache s'il a tourne.
 *
 * Le planificateur tournait sans laisser de trace. S'il s'arretait, la
 * frequentation cessait d'etre agregee et le journal d'etre purge, et rien ne
 * le montrait.
 */

/** La tache planifiee d'une commande d'entretien. */
function tacheDe(string $commande): TachePlanifiee
{
    $tache = collect(app(Schedule::class)->events())
        ->first(fn (TachePlanifiee $evenement) => str_ends_with((string) $evenement->command, $commande));

    expect($tache)->not->toBeNull("« $commande » n'est plus planifiee");

    return $tache;
}

/* ------------------------------------------------ planification */

it('planifie chaque commande d entretien a son heure', function (string $commande, string $expression) {
    expect(tacheDe($commande)->getExpression())->toBe($expression);
})->with([
    ['frequentation:agreger', '10 3 * * *'],
    ['journal:purger', '40 3 * * *'],
]);

it('ne planifie que les commandes de la liste qui fait foi', function () {
    $planifiees = collect(app(Schedule::class)->events())
        ->map(fn (TachePlanifiee $evenement) => preg_replace('/^.*artisan.? /', '', (string) $evenement->command))
        ->sort()->values()->all();

    expect($planifiees)->toBe(collect(TachesDEntretien::COMMANDES)->sort()->values()->all());
});

/*
 * Le verrou de chevauchement expire apres une heure. Les 24 h par defaut
 * feraient sauter la passe du lendemain si un conteneur etait arrete en pleine
 * execution, le verrou n'ayant alors jamais ete rendu.
 */
it('empeche deux passes simultanees, sur une ou plusieurs instances', function (string $commande) {
    $tache = tacheDe($commande);

    expect($tache->withoutOverlapping)->toBeTrue()
        ->and($tache->expiresAt)->toBe(60)
        ->and($tache->onOneServer)->toBeTrue();
})->with(TachesDEntretien::COMMANDES);

it('fait tourner chaque commande exactement une fois par jour', function () {
    $passes = array_fill_keys(TachesDEntretien::COMMANDES, 0);
    $debut = Carbon::parse('2026-10-05 00:00:00', 'UTC');

    try {
        for ($minute = 0; $minute < 24 * 60; $minute++) {
            Carbon::setTestNow($debut->copy()->addMinutes($minute));

            foreach (app(Schedule::class)->dueEvents(app()) as $tache) {
                $passes[preg_replace('/^.*artisan.? /', '', (string) $tache->command)]++;
            }
        }
    } finally {
        Carbon::setTestNow();
    }

    expect($passes)->toBe(array_fill_keys(TachesDEntretien::COMMANDES, 1));
});

/* ------------------------------------------------ Sentry Crons */

/*
 * Sentry doit recevoir un signal au debut et a la fin de chaque passe : c'est
 * ce qui lui permet d'alerter quand une passe manque. Le signal est intercepte
 * ici, tel que le SDK l'enverrait.
 *
 * Sans DSN, le paquet desactive cette integration — c'est l'etat actuel du
 * depot. Le test l'active comme le ferait un DSN present au demarrage.
 */
it('signale chaque passe a Sentry, au debut et a la fin', function (string $commande) {
    $transport = new class implements TransportInterface
    {
        /**
         * L'etat de chaque signal AU MOMENT DE L'ENVOI : le SDK reutilise le
         * meme objet pour le signal de fin, garder l'objet ne montrerait que
         * son dernier etat.
         *
         * @var list<array{moniteur: string, statut: string, planification: ?string}>
         */
        public array $signaux = [];

        public function send(Event $event): Result
        {
            $signal = $event->getCheckIn();
            $this->signaux[] = [
                'moniteur' => $signal->getMonitorSlug(),
                'statut' => (string) $signal->getStatus(),
                'planification' => $signal->getMonitorConfig()?->getSchedule()->getValue(),
            ];

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };

    config(['sentry.dsn' => 'https://cle-factice@sentry.exemple.invalid/1', 'sentry.transport' => $transport]);
    app()->forgetInstance(HubInterface::class);
    SentrySdk::setCurrentHub(app(HubInterface::class));
    app(ConsoleSchedulingIntegration::class)->onBoot(app('events'));

    try {
        $tache = tacheDe($commande);
        $tache->callBeforeCallbacks(app());
        $tache->exitCode = 0;
        $tache->callAfterCallbacks(app());
    } finally {
        app(ConsoleSchedulingIntegration::class)->onBootInactive();
        app()->forgetInstance(HubInterface::class);
        SentrySdk::setCurrentHub(app(HubInterface::class));
    }

    expect($transport->signaux)->toBe([
        ['moniteur' => TachesDEntretien::moniteur($commande), 'statut' => (string) CheckInStatus::inProgress(), 'planification' => tacheDe($commande)->getExpression()],
        ['moniteur' => TachesDEntretien::moniteur($commande), 'statut' => (string) CheckInStatus::ok(), 'planification' => tacheDe($commande)->getExpression()],
    ]);
})->with(TachesDEntretien::COMMANDES);

it('reste muet vers Sentry sans DSN', function () {
    // L'etat du depot : aucun DSN. Un transport est pose quand meme, pour
    // intercepter ce qui partirait — rien ne doit partir, et rien ne doit
    // lever d'erreur.
    expect(config('sentry.dsn'))->toBeEmpty();

    $transport = new class implements TransportInterface
    {
        public int $envois = 0;

        public function send(Event $event): Result
        {
            $this->envois++;

            return new Result(ResultStatus::success(), $event);
        }

        public function close(?int $timeout = null): Result
        {
            return new Result(ResultStatus::success());
        }
    };
    config(['sentry.transport' => $transport]);
    app()->forgetInstance(HubInterface::class);
    SentrySdk::setCurrentHub(app(HubInterface::class));

    try {
        $tache = tacheDe('journal:purger');
        $tache->callBeforeCallbacks(app());
        $tache->exitCode = 0;
        $tache->callAfterCallbacks(app());
    } finally {
        app()->forgetInstance(HubInterface::class);
        SentrySdk::setCurrentHub(app(HubInterface::class));
    }

    expect($transport->envois)->toBe(0);
});

/* ------------------------------------------------ trace de chaque passe */

/*
 * La trace s'ecrit a la fin de chaque commande, sur l'evenement CommandFinished
 * de Laravel. Laravel ne l'emet qu'EN DEHORS des tests unitaires
 * (runningUnitTests()) : en production, pour une commande lancee par le
 * planificateur, la route ou une console. Ces tests le branchent comme la
 * production le fait ; le conteneur reel l'a verifie de bout en bout.
 */
beforeEach(function () {
    app(Kernel::class)->rerouteSymfonyCommandEvents();
});

it('note la reussite d une passe, quel que soit le declencheur', function () {
    Artisan::call('journal:purger');

    $execution = ExecutionDEntretien::where('commande', 'journal:purger')->first();

    expect($execution?->derniere_reussite)->not->toBeNull()
        ->and($execution->dernier_echec)->toBeNull();
});

it('note l echec d une passe', function () {
    event(new CommandFinished('frequentation:agreger', new ArrayInput([]), new NullOutput, 1));

    expect(ExecutionDEntretien::where('commande', 'frequentation:agreger')->first()?->dernier_echec)->not->toBeNull();
});

it('ignore les commandes qui ne sont pas de l entretien', function () {
    event(new CommandFinished('route:list', new ArrayInput([]), new NullOutput, 0));

    expect(ExecutionDEntretien::where('commande', 'route:list')->exists())->toBeFalse();
});

/* ------------------------------------------------ tableau de bord */

function ouvrirLeTableauDeBord()
{
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');

    return Livewire::actingAs($admin)->test(TableauDeBord::class);
}

function marquer(string $commande, ?CarbonInterface $reussite, ?CarbonInterface $echec = null, ?CarbonInterface $suiviDepuis = null): void
{
    ExecutionDEntretien::updateOrCreate(['commande' => $commande], [
        'derniere_reussite' => $reussite,
        'dernier_echec' => $echec,
        'suivi_depuis' => $suiviDepuis ?? now()->subDays(30),
    ]);
}

it('montre le dernier entretien sans alerte quand tout a tourne', function () {
    foreach (TachesDEntretien::COMMANDES as $commande) {
        marquer($commande, now()->subHours(5));
    }

    ouvrirLeTableauDeBord()
        ->assertSee('Entretien automatique')
        ->assertSee('Dernier entretien')
        ->assertSeeHtml('data-alerte="non"')
        ->assertDontSeeHtml('data-alerte="oui"')
        ->assertDontSee('L’entretien automatique ne tourne plus normalement');
});

it('alerte au-dela de 26 heures sans passe reussie', function () {
    marquer('frequentation:agreger', now()->subHours(5));
    marquer('journal:purger', now()->subHours(TachesDEntretien::RETARD_ADMIS_EN_HEURES + 1));

    ouvrirLeTableauDeBord()
        ->assertSeeHtml('data-tache="journal:purger" data-alerte="oui"')
        ->assertSeeHtml('data-tache="frequentation:agreger" data-alerte="non"')
        ->assertSee('L’entretien automatique ne tourne plus normalement');
});

it('alerte quand la derniere tentative a echoue', function () {
    marquer('frequentation:agreger', now()->subHours(20), now()->subHours(1));
    marquer('journal:purger', now()->subHours(1));

    ouvrirLeTableauDeBord()
        ->assertSeeHtml('data-tache="frequentation:agreger" data-alerte="oui"')
        ->assertSee('Dernière tentative en échec');
});

/*
 * Le suivi commence avec la migration qui cree la table. Une installation
 * neuve n'a encore jamais vu passer l'entretien : ce n'est pas une panne
 * avant la premiere nuit.
 */
it('laisse une nuit a une installation neuve avant d alerter', function () {
    ouvrirLeTableauDeBord()
        ->assertSee('Aucune exécution enregistrée')
        ->assertDontSeeHtml('data-alerte="oui"')
        ->assertDontSee('L’entretien automatique ne tourne plus normalement');
});

it('alerte si rien n a tourne depuis le debut du suivi', function () {
    foreach (TachesDEntretien::COMMANDES as $commande) {
        marquer($commande, null, null, now()->subHours(TachesDEntretien::RETARD_ADMIS_EN_HEURES + 1));
    }

    ouvrirLeTableauDeBord()
        ->assertSee('Aucune exécution enregistrée')
        ->assertSeeHtml('data-alerte="oui"')
        ->assertSee('L’entretien automatique ne tourne plus normalement');
});

/* ------------------------------------------------ script du conteneur */

/** Les lignes executees du script de demarrage, sans ses commentaires. */
function scriptDeDemarrage(): string
{
    return collect(preg_split('/\R/', (string) file_get_contents(base_path('../tools/demarrer-conteneur.sh'))))
        ->reject(fn ($ligne) => str_starts_with(ltrim($ligne), '#'))
        ->implode("\n");
}

/*
 * Le script tourne sous « set -e », dont la boucle herite : sans la capture
 * « || code=$? », le premier arret du planificateur tuait la boucle au lieu de
 * le relancer. Defaut constate en conteneur reel, ou le test precedent de ce
 * fichier ne le voyait pas.
 */
it('relance le planificateur s il s arrete', function () {
    expect(scriptDeDemarrage())->toMatch('/while true; do\s+code=0\s+php artisan schedule:work --whisper \|\| code=\$\?\s+echo "AVERTISSEMENT[^\n]*\$code[^\n]*Relance[^\n]*" >&2\s+sleep \d+\s+done/');
});

it('laisse la sortie du planificateur dans les journaux', function () {
    $lignes = collect(explode("\n", scriptDeDemarrage()))->filter(fn ($ligne) => str_contains($ligne, 'schedule:work'));

    expect($lignes)->not->toBeEmpty();

    foreach ($lignes as $ligne) {
        expect($ligne)->not->toContain('/dev/null');
    }
});
