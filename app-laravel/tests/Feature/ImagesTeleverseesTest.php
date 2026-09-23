<?php

use App\Livewire\Admin\BienFormulaire;
use App\Models\Bien;
use App\Models\PhotoDeBien;
use App\Models\User;
use App\Support\ImageTeleversee;
use Database\Seeders\ReferentielsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ce qui arrive a une image entre le formulaire et le disque.
 *
 * Les cinq ecrans qui acceptent un visuel deposaient le fichier TEL QUEL. Un
 * bien accepte dix photos de 2 Mo : une fiche pouvait servir vingt megaoctets,
 * et ce sont les photos de l'agence, encore attendues, qui les auraient
 * apportes.
 *
 * Les tests portent sur les trois decisions du traitement — reduire, convertir,
 * et ne JAMAIS perdre un televersement quand on ne sait pas le traiter.
 */
beforeEach(function () {
    Storage::fake('public');
});

/**
 * Une image de la largeur demandee, assez bruitee pour ne pas se compresser a rien.
 *
 * Le POIDS du resultat depend de la bibliotheque JPEG du systeme : a dessin
 * egal, la photo 2400x1600 faisait moins de 2 Mo sous Windows et plus sous
 * Linux, ou la CI la voyait refusee par la limite du formulaire. Le pas et la
 * qualite se reglent donc par appel, pour garder chaque test loin des seuils
 * qu'il ne cherche pas a eprouver.
 */
function imageDeLargeur(int $largeur, int $hauteur, int $pas = 4, int $qualite = 92): string
{
    $image = imagecreatetruecolor($largeur, $hauteur);

    for ($x = 0; $x < $largeur; $x += $pas) {
        for ($y = 0; $y < $hauteur; $y += $pas) {
            $couleur = imagecolorallocate($image, ($x * 7) % 255, ($y * 13) % 255, ($x + $y) % 255);
            imagefilledrectangle($image, $x, $y, $x + $pas - 1, $y + $pas - 1, $couleur);
        }
    }

    ob_start();
    imagejpeg($image, null, $qualite);
    $contenu = (string) ob_get_clean();
    imagedestroy($image);

    return $contenu;
}

it('ramene une image trop large sous la limite d\'affichage', function () {
    // Au-dela de 1600 pixels, aucun ecran du site n'affiche un pixel de plus :
    // ce qui depasse se telecharge pour etre jete.
    $fichier = UploadedFile::fake()->createWithContent('villa.jpg', imageDeLargeur(3200, 2400));

    $chemin = ImageTeleversee::deposer($fichier, 'biens');

    $contenu = Storage::disk('public')->get($chemin);
    [$largeur] = getimagesizefromstring($contenu);

    expect($largeur)->toBe(ImageTeleversee::LARGEUR_MAX)
        ->and($chemin)->toEndWith('.webp');
});

it('ne retaille pas une image deja sous la limite', function () {
    $fichier = UploadedFile::fake()->createWithContent('petite.jpg', imageDeLargeur(800, 600));

    $chemin = ImageTeleversee::deposer($fichier, 'biens');

    [$largeur, $hauteur] = getimagesizefromstring(Storage::disk('public')->get($chemin));

    expect($largeur)->toBe(800)->and($hauteur)->toBe(600);
});

it('allege le fichier depose', function () {
    $source = imageDeLargeur(3200, 2400);
    $fichier = UploadedFile::fake()->createWithContent('lourde.jpg', $source);

    $chemin = ImageTeleversee::deposer($fichier, 'biens');

    expect(strlen(Storage::disk('public')->get($chemin)))->toBeLessThan(strlen($source));
});

it('depose sans traitement ce qu\'il ne sait pas convertir', function () {
    // Un SVG n'a ni pixels ni orientation. Le refuser ou le perdre serait pire
    // que de le laisser passer : l'editeur ne saurait pas pourquoi son logo a
    // disparu.
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10"/></svg>';
    $fichier = UploadedFile::fake()->createWithContent('logo.svg', $svg);

    $chemin = ImageTeleversee::deposer($fichier, 'services');

    expect($chemin)->toEndWith('.svg')
        ->and(Storage::disk('public')->get($chemin))->toBe($svg);
});

it('ne perd pas un fichier illisible', function () {
    // Ni exception, ni fichier evapore : le depot brut reste le filet.
    $fichier = UploadedFile::fake()->createWithContent('cassee.jpg', 'ceci n’est pas une image');

    $chemin = ImageTeleversee::deposer($fichier, 'biens');

    expect(Storage::disk('public')->exists($chemin))->toBeTrue();
});

it('traite les photos deposees depuis la fiche d\'un bien', function () {
    // Le bout par lequel le defaut serait revenu : l'ecran appelait store()
    // directement. Dix photos de 2 Mo faisaient vingt megaoctets sur une page.
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    $this->seed(ReferentielsSeeder::class);

    $editeur = User::factory()->create(['statut' => User::ACTIF]);
    $editeur->assignRole('editeur');

    $bien = Bien::factory()->create(['slug' => 'villa-photo', 'statut' => Bien::PUBLIE]);

    // Plus large que la limite d'affichage, mais LEGERE : ce test porte sur
    // le traitement, pas sur la limite de poids du formulaire. La condition
    // est ecrite ici pour qu'un changement de plateforme qui la violerait
    // echoue en le disant, au lieu d'un « Component has errors » a dechiffrer.
    $source = imageDeLargeur(2400, 1600, pas: 16, qualite: 75);

    expect(strlen($source))->toBeLessThan(1024 * 1024, 'La photo de test doit rester sous le Mo, loin de la limite du formulaire.');

    Livewire::actingAs($editeur)
        ->test(BienFormulaire::class, ['bien' => $bien])
        // La fabrique ne pose pas cette valeur, que le formulaire exige.
        // Sans rapport avec les images : on la renseigne pour atteindre
        // l'enregistrement.
        ->set('prixUnite', 'total')
        ->set('nouvellesPhotos', [
            UploadedFile::fake()->createWithContent('photo.jpg', $source),
        ])
        ->call('enregistrer')
        ->assertHasNoErrors();

    $photo = PhotoDeBien::where('bien_id', $bien->id)->firstOrFail();

    expect($photo->fichier)->toEndWith('.webp');

    // En base le chemin porte le prefixe « storage/ », qui designe l'adresse
    // publique et non l'emplacement sur le disque.
    $surLeDisque = Str::after($photo->fichier, 'storage/');

    [$largeur] = getimagesizefromstring(Storage::disk('public')->get($surLeDisque));

    expect($largeur)->toBe(ImageTeleversee::LARGEUR_MAX);
});
