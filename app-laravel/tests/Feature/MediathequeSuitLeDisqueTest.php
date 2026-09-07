<?php

use App\Livewire\Admin\Mediatheque;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * La mediatheque doit inventorier LE DISQUE CONFIGURE, et non un chemin fige.
 *
 * Elle parcourait storage_path('app/public') directement. Sur un hebergement
 * ordinaire cela revient au meme ; sur une plateforme sans systeme de fichiers
 * persistant, ou le disque public pointe vers un stockage objet, elle
 * n'inventorie plus rien — l'ecran reste vide alors que les images existent.
 *
 * C'etait le seul endroit du projet qui court-circuitait Storage::disk().
 */

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('voit un fichier depose sur le disque public, ou qu il soit', function () {
    Storage::fake('public');
    Storage::disk('public')->put('actualites/une-couverture.jpg', 'contenu');

    $images = Livewire::actingAs($this->admin)->test(Mediatheque::class)->viewData('images');

    expect(collect($images)->pluck('nom'))->toContain('une-couverture.jpg');
});
