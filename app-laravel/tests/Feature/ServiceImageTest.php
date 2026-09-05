<?php

use App\Livewire\Admin\ServiceFormulaire;
use App\Models\Categorie;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Televersement de l'image d'un service, sur le modele du lot 1
 * (tests/Feature/ArticleCouvertureTest.php) : meme motif, memes garde-fous.
 *
 * Deux differences avec les articles :
 *   - le service existe deja (ServiceFormulaire ne cree jamais, cf. ServiceListe) ;
 *   - le dossier des fichiers televerses est storage/services, pas storage/actualites.
 */
beforeEach(function () {
    Storage::fake('public');
    Role::findOrCreate('editeur');

    $this->categorie = Categorie::create([
        'slug' => 'foncier', 'nom_fr' => 'Foncier', 'nom_en' => 'Land & Title', 'ordre' => 1,
    ]);

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');

    $this->service = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'slug' => 'foncier',
    ]);
});

it('rend null quand le service n a aucune image', function () {
    $this->service->image_source = null;

    expect($this->service->urlImage())->toBeNull();
});

it('sert l image du site statique, qui n a pas de fichier televerse', function () {
    $this->service->image_source = 'images/services/foncier.jpg';

    expect($this->service->urlImage())->toContain('images/services/foncier.jpg');
});

it('televerse une image depuis le formulaire', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('image', UploadedFile::fake()->image('photo.jpg', 1200, 800))
        ->call('enregistrer')
        ->assertHasNoErrors();

    $service = $this->service->fresh();

    expect($service->image_source)->toStartWith('storage/services/');
    Storage::disk('public')->assertExists(str_replace('storage/', '', $service->image_source));
});

it('remplace l image et efface l ancien fichier', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('image', UploadedFile::fake()->image('premiere.jpg'))
        ->call('enregistrer');

    $premier = $this->service->fresh()->image_source;

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service->fresh()])
        ->set('image', UploadedFile::fake()->image('seconde.jpg'))
        ->call('enregistrer');

    $second = $this->service->fresh()->image_source;

    expect($second)->not->toBe($premier);
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $premier));
    Storage::disk('public')->assertExists(str_replace('storage/', '', $second));
});

it('supprime l image a la demande', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('image', UploadedFile::fake()->image('photo.jpg'))
        ->call('enregistrer');

    $chemin = $this->service->fresh()->image_source;
    expect($chemin)->not->toBeNull();

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service->fresh()])
        ->call('supprimerImage')
        ->call('enregistrer');

    // Le fichier televerse part, et `image_source` retombe sur le visuel que le
    // site sert pour ce slug — pas sur null, qui ferait annoncer « aucune
    // image » a l'ecran alors que la page publique montre toujours la photo.
    Storage::disk('public')->assertMissing(str_replace('storage/', '', $chemin));
    expect($this->service->fresh()->image_source)->toBe('images/services/foncier.jpg');
});

it('retombe a rien quand le service n a aucun visuel sur le site', function () {
    // Un service cree depuis l'administration n'a pas de regle CSS : retirer
    // son image doit bien vider le champ, faute de repli.
    $cree = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'service-cree-ici',
    ]);

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $cree])
        ->set('image', UploadedFile::fake()->image('photo.jpg'))
        ->call('enregistrer');

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $cree->fresh()])
        ->call('supprimerImage')
        ->call('enregistrer');

    expect($cree->fresh()->image_source)->toBeNull();
});

it('resout le visuel du site depuis la feuille de style, pas depuis le slug', function () {
    // Le nom du fichier ne se deduit pas du slug : « gestion » s'appuie sur
    // gestion-location.jpg. C'est images.css qui fait foi.
    $gestion = Service::factory()->create([
        'categorie_id' => $this->categorie->id, 'slug' => 'gestion',
    ]);

    expect($gestion->imageDuSiteStatique())->toBe('images/services/gestion-location.jpg');
});

it('ne supprime jamais un fichier du site statique', function () {
    // Ce test verifiait auparavant `file_exists(public_path(...))`. Il ne
    // pouvait PAS echouer : Storage::fake('public') redirige le disque vers
    // storage/framework/testing, si bien que delete() n'atteint public/ dans
    // aucun scenario. Retirer entierement la garde l'aurait laisse vert — il
    // prouvait que le depot contient un fichier, pas que le code l'epargne.
    //
    // On pose donc le fichier sur le disque REELLEMENT utilise, et on verifie
    // qu'il y survit.
    Storage::disk('public')->put('images/services/foncier.jpg', 'visuel du site');

    $this->service->update(['image_source' => 'images/services/foncier.jpg']);

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->call('supprimerImage')
        ->call('enregistrer');

    // Le champ retombe sur le visuel du site, et le FICHIER n'a pas bouge :
    // c'est lui que la garde protege.
    expect($this->service->fresh()->image_source)->toBe('images/services/foncier.jpg');
    Storage::disk('public')->assertExists('images/services/foncier.jpg');
});

it('refuse un chemin qui remonte hors du dossier des services', function () {
    // Sans normalisation, `storage/services/../couvertures/x.jpg` commence bien
    // par le prefixe attendu et passerait la garde : un editeur pourrait
    // detruire la couverture d'un article depuis le formulaire d'un service.
    Storage::disk('public')->put('couvertures/article.jpg', 'couverture');

    $this->service->update(['image_source' => 'storage/services/../couvertures/article.jpg']);

    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->call('supprimerImage')
        ->call('enregistrer');

    Storage::disk('public')->assertExists('couvertures/article.jpg');
});

it('refuse un fichier qui n est pas une image', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('image', UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'))
        ->assertHasErrors(['image']);
});

it('refuse une image trop lourde', function () {
    Livewire::actingAs($this->editeur)
        ->test(ServiceFormulaire::class, ['service' => $this->service])
        ->set('image', UploadedFile::fake()->image('enorme.jpg')->size(5000))
        ->assertHasErrors(['image' => 'max']);
});

it('affiche l image televersee sur la page publique des services, en priorite sur la classe CSS', function () {
    Storage::disk('public')->put('services/foncier-personnalise.jpg', 'contenu');
    $this->service->update(['image_source' => 'storage/services/foncier-personnalise.jpg', 'visible' => true]);

    $reponse = $this->get('/services')->assertOk();

    $reponse->assertSee('storage/services/foncier-personnalise.jpg', false);
    // La classe CSS reste posee : c'est elle qui doit s'effacer devant le
    // style en ligne, pas l'inverse — la regle heritee de la page actualites.
    $reponse->assertSee('service-bg-foncier', false);
});

it('retombe sur la classe CSS de la tuile quand rien n a ete televerse', function () {
    expect($this->service->image_source)->toBeNull();

    $reponse = $this->get('/services')->assertOk();

    $reponse->assertSee('service-bg-foncier', false);
    $reponse->assertDontSee('background-image', false);
});

it('distingue une image televersee d une image du site statique', function () {
    $statique = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => 'images/services/foncier.jpg',
    ]);
    $televersee = Service::factory()->create([
        'categorie_id' => $this->categorie->id,
        'image_source' => 'storage/services/photo.jpg',
    ]);

    expect($statique->imageTeleversee())->toBeFalse();
    expect($televersee->imageTeleversee())->toBeTrue();
});

it('ne pose aucun style en ligne pour une image du site statique', function () {
    // La regle CSS sert deja cette image, et fournit en prime une variante
    // allegee sous 800 pixels. Un style en ligne l'ecraserait sans rien
    // apporter — la page perdrait sa version mobile.
    // Le service de beforeEach porte deja le slug « foncier » ; on le rend
    // simplement visible et on lui donne une image du site statique.
    $this->service->update([
        'visible' => true, 'ordre' => 1,
        'image_source' => 'images/services/foncier.jpg',
    ]);

    $corps = $this->get('/services')->assertOk()->getContent();

    expect($corps)->toContain('service-bg-foncier');
    expect(substr_count($corps, 'background-image:url'))->toBe(0);
});

it('renseigne l image des six services repris du site', function () {
    // Sans ce champ, l'ecran d'administration annoncerait « aucune image »
    // pour les six services alors que le site en affiche six.
    //
    // Le service pose par beforeEach porte deja le slug « foncier » et n'a pas
    // d'image : il faut le retirer avant de semer. Le seeder ne reecrit plus
    // `image_source` sur un service existant — c'est voulu, sans quoi un
    // `db:seed` de routine remplacerait une image televersee par le chemin
    // statique et laisserait le fichier orphelin sur le disque.
    $this->service->delete();

    Artisan::call('db:seed', ['--class' => 'CategorieSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'ServiceFaqSeeder', '--force' => true]);

    $sansImage = Service::whereNull('image_source')->orWhere('image_source', '')->pluck('slug')->all();

    expect($sansImage)->toBe([]);
    expect(Service::where('slug', 'gestion')->value('image_source'))
        ->toBe('images/services/gestion-location.jpg');
});
