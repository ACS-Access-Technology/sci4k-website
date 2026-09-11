<?php

/*
 * L'image doit accepter ce que l'application declare accepter.
 *
 * Constate en ligne : le conteneur ne chargeait AUCUN php.ini, donc les
 * valeurs d'usine — upload_max_filesize a 2 Mo — tandis que les ecrans
 * d'administration annoncent accepter jusqu'a 4 Mo. Entre les deux, une image
 * de 3 Mo etait transferee en entier par le navigateur puis jetee par PHP
 * avant d'atteindre Laravel : ni validation, ni exception, ni journal. Le
 * backoffice affichait « Envoi en cours… » un long moment, puis « le fichier
 * n'a pas pu etre envoye ».
 *
 * Le defaut est INVISIBLE en developpement, ou php.ini est genereux, et ne se
 * revele que sur l'image de deploiement. D'ou ce test, qui lit le php.ini
 * reellement embarque et le confronte aux regles du code.
 */

use Illuminate\Support\Facades\File;

/** Les kilo-octets d'un reglage php.ini ecrit « 12M », « 512K » ou « 1G ». */
function enKilooctets(string $valeur): int
{
    $valeur = trim($valeur);
    $unite = strtoupper(substr($valeur, -1));
    $nombre = (int) $valeur;

    return match ($unite) {
        'G' => $nombre * 1024 * 1024,
        'M' => $nombre * 1024,
        'K' => $nombre,
        default => (int) ($nombre / 1024),
    };
}

/** @return array<string,string> */
function reglagesEmbarques(): array
{
    $chemin = base_path('../docker/php.ini');

    expect(File::exists($chemin))->toBeTrue(
        "docker/php.ini est absent : l'image repartirait sur les valeurs d'usine de PHP."
    );

    $reglages = parse_ini_file($chemin);

    return $reglages === false ? [] : $reglages;
}

/** La plus grande taille qu'un ecran d'administration declare accepter. */
function plusGrandeRegleDeTeleversement(): int
{
    $plafonds = [];

    foreach (File::allFiles(app_path('Livewire')) as $fichier) {
        preg_match_all("/'image',\s*'max:([0-9]+)'/", $fichier->getContents(), $trouves);
        $plafonds = [...$plafonds, ...array_map('intval', $trouves[1])];
    }

    expect($plafonds)->not->toBeEmpty('Aucune regle de televersement trouvee — le test ne verifie plus rien.');

    return max($plafonds);
}

it('accepte au moins ce que les ecrans declarent accepter', function () {
    $reglages = reglagesEmbarques();
    $annonce = plusGrandeRegleDeTeleversement();

    expect(enKilooctets($reglages['upload_max_filesize'] ?? '2M'))
        ->toBeGreaterThanOrEqual($annonce,
            "upload_max_filesize est sous la plus grande regle du code ({$annonce} Ko) : ".
            'PHP jetterait le fichier avant que la validation ne puisse le refuser proprement.');
});

/*
 * post_max_size borne la requete ENTIERE, pas seulement le fichier. Reglee a
 * l'identique d'upload_max_filesize, elle refuse un fichier pourtant admis,
 * parce que les champs qui l'accompagnent debordent les derniers octets — et
 * ce refus-la est pire : PHP vide $_POST, et la requete echoue sur un jeton
 * CSRF manquant, ce qui ne designe rien.
 */
it('laisse de la marge a la requete au-dela du fichier', function () {
    $reglages = reglagesEmbarques();

    expect(enKilooctets($reglages['post_max_size'] ?? '8M'))
        ->toBeGreaterThan(enKilooctets($reglages['upload_max_filesize'] ?? '2M'));
});

/*
 * Le fichier peut exister dans le depot sans etre copie dans l'image : PHP
 * repartirait alors sur ses valeurs d'usine, et les deux tests ci-dessus
 * passeraient en vert tout en decrivant une image qui n'existe pas.
 */
it('embarque le php.ini dans l image', function () {
    expect(File::get(base_path('../Dockerfile')))->toContain('docker/php.ini');
});
