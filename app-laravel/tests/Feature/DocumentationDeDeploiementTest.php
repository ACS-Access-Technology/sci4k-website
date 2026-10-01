<?php

/**
 * Une seule strategie de deploiement, et des variables de production qui ne
 * trahissent pas.
 *
 * Le site se met en ligne par « railway up » depuis master, apres le script de
 * verification. Une documentation qui ferait relier le service a GitHub
 * ouvrirait un deploiement a chaque poussee, sans ce controle ; un gabarit de
 * variables qui laisserait passer un reglage de developpement le recopierait
 * en production.
 */
function documentDuDepot(string $chemin): string
{
    return (string) file_get_contents(base_path('../'.$chemin));
}

it('decrit une seule voie de mise en ligne : railway up depuis master, apres verification', function () {
    $procedure = documentDuDepot('docs/PREMIER_DEPLOIEMENT.md');

    expect($procedure)->toContain('./tools/verifier-avant-deploiement.sh && railway up --service sci4k')
        ->and(documentDuDepot('docs/DEPLOIEMENT_RAILWAY.md'))
        ->toContain('./tools/verifier-avant-deploiement.sh && railway up --service sci4k')
        ->toContain("**Le service n'est relié à AUCUN dépôt GitHub.**")
        ->not->toContain('Deploy from GitHub')
        ->and(documentDuDepot('CLAUDE.md'))->toContain('docs/PREMIER_DEPLOIEMENT.md');

    // Le repli d'hebergement classique reste nomme comme tel.
    expect(documentDuDepot('docs/MISE_EN_LIGNE.md'))->toContain('plan de repli');
});

/*
 * Une etiquette flottante (mysql:9) fait monter la base de version au premier
 * redeploiement venu. Le 1er octobre 2026, c'est ce qu'une mise a jour
 * automatique de Railway allait faire, sans sauvegarde possible.
 */
it('epingle MySQL sur une version exacte', function () {
    $plateforme = documentDuDepot('docs/DEPLOIEMENT_RAILWAY.md');

    expect($plateforme)->toContain("**Épingler l'image sur une version exacte**, jamais sur `mysql:9`")
        ->toContain('railway service source connect --image mysql:9.4.0')
        ->and(documentDuDepot('docs/PREMIER_DEPLOIEMENT.md'))->toContain('suspendue jusqu\'au 15 octobre');
});

it('sauvegarde avant de mettre en ligne, hors du depot, sans mot de passe en clair', function () {
    $procedure = documentDuDepot('docs/PREMIER_DEPLOIEMENT.md');

    // La sauvegarde precede la mise en ligne dans la procedure. Les deux
    // positions d'abord : strpos rend false pour un titre disparu, que PHP
    // classerait avant tout le reste.
    $sauvegarde = strpos($procedure, '## 4. Sauvegarder, avant chaque mise en ligne');
    $miseEnLigne = strpos($procedure, '## 5. Mettre en ligne');

    expect($sauvegarde)->toBeInt()
        ->and($miseEnLigne)->toBeInt()
        ->and($sauvegarde)->toBeLessThan($miseEnLigne)
        ->and($procedure)->toContain('ne se rangent JAMAIS dans le dossier du dépôt')
        ->toContain('MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysqldump -uroot --single-transaction')
        ->not->toMatch('/mysqldump[^\n]*\s-p\S/');
});

it('documente un retour arriere sans raccourci hors du flux', function () {
    $procedure = documentDuDepot('docs/PREMIER_DEPLOIEMENT.md');

    // La commande, pas seulement le mot dans le texte. Et aucune commande qui
    // reecrit l'historique : le retour arriere est un commit de plus, qui
    // suit dev -> preprod -> master comme les autres.
    expect($procedure)->toContain('*Rollback*')
        ->toContain('git revert -m 1 <commit de fusion fautif>')
        ->not->toContain('git reset --hard')
        ->not->toContain('push --force')
        ->not->toContain('push -f')
        ->and(documentDuDepot('tools/verifier-avant-deploiement.sh'))->not->toContain('--retour-arriere');
});

it('ne laisse dans le gabarit de production aucun reglage de developpement', function () {
    $gabarit = collect(file(base_path('.env.production.example'), FILE_IGNORE_NEW_LINES))
        ->reject(fn ($ligne) => $ligne === '' || str_starts_with($ligne, '#'))
        ->mapWithKeys(fn ($ligne) => [strstr($ligne, '=', true) => substr((string) strstr($ligne, '='), 1)]);

    expect($gabarit['APP_DEBUG'])->toBe('false')
        ->and($gabarit['APP_ENV'])->toBe('production')
        ->and($gabarit['LOG_STACK'])->toBe('stderr')
        ->and($gabarit['LOG_LEVEL'])->toBe('warning')
        ->and($gabarit['MAIL_MAILER'])->toBe('smtp')
        ->and($gabarit)->toHaveKey('MAIL_FROM_ADDRESS')
        ->toHaveKey('PASSKEYS_USER_HANDLE_SECRET');

    // Aucun secret renseigne dans un fichier du depot.
    foreach (['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'SENTRY_LARAVEL_DSN', 'PASSKEYS_USER_HANDLE_SECRET'] as $secret) {
        expect($gabarit[$secret])->toBe('', "$secret est renseigne dans le gabarit");
    }
});
