<?php

/**
 * Releve, par un chemin INDEPENDANT de l'application, les objets de la derniere
 * sauvegarde deposee dans le bucket : liste, telecharge dans un dossier local.
 *
 *   php tools/essai-b2-lecture.php <dossier-de-sortie>
 *
 * Lit SAUVEGARDE_S3_ENDPOINT / _REGION / _BUCKET / _KEY / _SECRET et
 * SAUVEGARDE_PREFIXE dans l'environnement. Ne les affiche jamais.
 * Sert a l'essai isole de docs/SAUVEGARDES.md, §5 : si l'application et ce
 * script s'accordent sur le contenu du bucket, le depot est reel.
 */

require_once __DIR__.'/../app-laravel/vendor/autoload.php';

$sortie = $argv[1] ?? null;

if (! $sortie || ! is_dir($sortie)) {
    fwrite(STDERR, "Usage : php tools/essai-b2-lecture.php <dossier-existant>\n");
    exit(2);
}

$bucket = getenv('SAUVEGARDE_S3_BUCKET');
$prefixe = (getenv('SAUVEGARDE_PREFIXE') ?: 'sci4k').'/quotidien/';

$client = new Aws\S3\S3Client([
    'version' => 'latest',
    'region' => getenv('SAUVEGARDE_S3_REGION') ?: 'auto',
    'endpoint' => getenv('SAUVEGARDE_S3_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'credentials' => ['key' => getenv('SAUVEGARDE_S3_KEY'), 'secret' => getenv('SAUVEGARDE_S3_SECRET')],
    'request_checksum_calculation' => 'when_required',
    'response_checksum_validation' => 'when_required',
]);

$objets = [];
$jeton = null;

do {
    $reponse = $client->listObjectsV2(array_filter(['Bucket' => $bucket, 'Prefix' => $prefixe, 'ContinuationToken' => $jeton]));

    foreach ($reponse['Contents'] ?? [] as $o) {
        $objets[] = $o;
    }

    $jeton = $reponse['IsTruncated'] ? $reponse['NextContinuationToken'] : null;
} while ($jeton);

if (! $objets) {
    fwrite(STDERR, "Aucun objet sous $prefixe\n");
    exit(1);
}

// Le plus recent : le dossier du jour porte la date, le nom l'heure.
usort($objets, fn ($a, $b) => strcmp($b['Key'], $a['Key']));
$jour = dirname($objets[0]['Key']);

// Le manifeste dit QUELS objets forment la sauvegarde : plusieurs essais le
// meme jour partagent un dossier, et le manifeste le plus recent fait foi.
$nomManifeste = '/manifeste.json';
$client->getObject(['Bucket' => $bucket, 'Key' => $jour.$nomManifeste, 'SaveAs' => $sortie.$nomManifeste]);
$manifeste = json_decode(file_get_contents($sortie.$nomManifeste), true);
printf("  manifeste.json\n");

foreach ($manifeste['objets'] as $genre => $o) {
    $client->getObject(['Bucket' => $bucket, 'Key' => $jour.'/'.$o['nom'], 'SaveAs' => $sortie.'/'.$o['nom']]);
    printf("  %s (%d octets annonces, %s)\n", $o['nom'], $o['octets'], $genre);
}
