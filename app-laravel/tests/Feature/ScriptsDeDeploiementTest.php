<?php

use Symfony\Component\Process\Process;

/**
 * Le flux des branches et la verification avant « railway up ».
 *
 * Les deux scripts ont leurs essais en bash, dans tools/tests/ : de vrais
 * depots git temporaires, un faux curl pour l'API GitHub. Ce fichier les fait
 * tourner avec la suite — donc dans la CI — et verifie que les noms des checks
 * restent les memes partout : un job renomme dans un workflow ferait refuser
 * chaque deploiement, ou laisserait une protection GitHub attendre un check qui
 * n'existe plus.
 */
function racineDuDepot(string $chemin = ''): string
{
    return base_path('..'.($chemin === '' ? '' : '/'.$chemin));
}

function lancerLesEssais(string $script): Process
{
    $essais = new Process(['bash', racineDuDepot('tools/tests/'.$script)], racineDuDepot(), null, null, 300);
    $essais->run();

    return $essais;
}

/** Les noms des jobs d'un workflow (« name: » sous chaque job). */
function nomsDesJobs(string $workflow): array
{
    preg_match_all('/^    name: (.+)$/m', (string) file_get_contents(racineDuDepot('.github/workflows/'.$workflow)), $noms);

    return array_map('trim', $noms[1]);
}

/** Les checks exiges par le script de deploiement. */
function checksExigesAvantDeploiement(): array
{
    $script = (string) file_get_contents(racineDuDepot('tools/verifier-avant-deploiement.sh'));
    preg_match('/readonly CHECKS_REQUIS=\((.*?)\n\)/s', $script, $bloc);
    preg_match_all('/"([^"]+)"/', $bloc[1] ?? '', $noms);

    return $noms[1];
}

it('refuse toute demande de fusion hors du flux dev, preprod, master', function () {
    $essais = lancerLesEssais('verifier-flux-des-branches.test.sh');

    expect($essais->getExitCode())->toBe(0, $essais->getOutput().$essais->getErrorOutput())
        ->and($essais->getOutput())->toContain('OK     dev -> master : refuse')
        ->toContain('OK     dev -> preprod : admis')
        ->toContain('OK     preprod -> master : admis');
});

it('refuse de deployer un depot qui n est pas master tel que la CI l a valide', function () {
    $essais = lancerLesEssais('verifier-avant-deploiement.test.sh');

    expect($essais->getExitCode())->toBe(0, $essais->getOutput().$essais->getErrorOutput());

    foreach ([
        'branche differente de master', 'fichier suivi modifie', 'master en avance sur origin',
        'master en retard sur origin', 'aucun check pour ce commit', 'un check en echec',
        'API GitHub injoignable', 'hors d\'un depot git', 'outil requis absent (node)',
    ] as $essai) {
        expect($essais->getOutput())->toContain('OK     '.$essai);
    }
});

it('exige avant deploiement des checks qui existent dans les workflows', function () {
    $jobs = array_merge(nomsDesJobs('verification.yml'), nomsDesJobs('audit-dependances.yml'));

    expect(checksExigesAvantDeploiement())->toBe([
        'Contrôles de non-régression', 'Tests Laravel', 'Paquets PHP', 'Paquets npm',
    ]);

    foreach (checksExigesAvantDeploiement() as $check) {
        expect($jobs)->toContain($check);
    }
});

/*
 * La liste que l'administrateur recopie dans GitHub (§ 13) : exactement les
 * cinq checks, sous leur nom exact, et jamais SonarCloud. Un nom faux ou
 * oublie la, c'est une protection qui attend un check qui n'existe pas, ou qui
 * n'exige pas celui qui compte.
 */
it('documente les checks obligatoires sous leur nom exact', function () {
    $documentation = (string) file_get_contents(racineDuDepot('docs/BRANCHES_ET_DEPLOIEMENT.md'));

    preg_match('/checks à ajouter, en tapant leur nom : (.*?) ;/s', $documentation, $liste);
    preg_match_all('/`([^`]+)`/', $liste[1] ?? '', $noms);

    expect($noms[1])->toEqualCanonicalizing([...checksExigesAvantDeploiement(), ...nomsDesJobs('flux-des-branches.yml')])
        ->not->toContain('SonarCloud Code Analysis');
});

/*
 * Le workflow du flux : sur les demandes de fusion vers les deux branches
 * protegees, relance si la cible change, et les noms de branche jamais
 * interpoles dans le script.
 */
it('controle chaque demande de fusion vers preprod et master', function () {
    $workflow = (string) file_get_contents(racineDuDepot('.github/workflows/flux-des-branches.yml'));

    expect(nomsDesJobs('flux-des-branches.yml'))->toBe(['Flux des branches'])
        ->and($workflow)->toContain('types: [opened, synchronize, reopened, edited]')
        ->toContain('branches: [preprod, master]')
        ->toContain('SOURCE: ${{ github.head_ref }}')
        ->toContain('DEPOT_SOURCE: ${{ github.event.pull_request.head.repo.full_name }}')
        ->toContain('bash tools/verifier-flux-des-branches.sh "$SOURCE" "$CIBLE" "$DEPOT_SOURCE" "$DEPOT_CIBLE"');

    // Aucune expression ${{ }} dans la ligne executee : ce serait une injection.
    preg_match('/run: (.+)$/m', $workflow, $commande);
    expect($commande[1])->not->toContain('${{');
});

/*
 * Dependabot vise la branche par defaut, master, si rien ne lui dit le
 * contraire : ses demandes sauteraient dev et preprod, et le check « Flux des
 * branches » les refuserait toutes. La demande #7 l'a montre.
 */
it('fait passer les mises a jour de Dependabot par dev', function () {
    $configuration = (string) file_get_contents(racineDuDepot('.github/dependabot.yml'));

    preg_match_all('/^\s+- package-ecosystem:/m', $configuration, $ecosystemes);
    preg_match_all('/^\s+target-branch: "dev"\r?$/m', $configuration, $cibles);

    expect(count($ecosystemes[0]))->toBeGreaterThan(0)
        ->and(count($cibles[0]))->toBe(count($ecosystemes[0]));
});
