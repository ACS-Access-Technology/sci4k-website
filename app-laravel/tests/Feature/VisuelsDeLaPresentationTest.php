<?php

use App\Livewire\Admin\ImageDeFondFormulaire;
use App\Models\ImageDeFond;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * Les deux illustrations de la page Presentation viennent desormais de la
 * base, et non plus d'un chemin ecrit en dur dans le gabarit.
 */
it('sert l illustration enregistree dans les images de fond', function () {
    ImageDeFond::where('slug', 'presentation-apercu')->update([
        'fichier' => 'images/fonds/nouvelle-photo.jpg',
        'texte_alternatif_fr' => 'Nouvelle photo du siège',
    ]);

    $reponse = $this->get(route('presentation.index'));

    $reponse->assertOk();
    $reponse->assertSee('images/fonds/nouvelle-photo.jpg', false);
    $reponse->assertSee('Nouvelle photo du siège', false);
    $reponse->assertDontSee('images/presentation/apercu.jpg', false);
});

/**
 * Masquer l'entree ne doit pas laisser une image cassee : le gabarit retombe
 * sur le fichier d'origine. Une section sans illustration se lit ; un carre
 * barre ne se lit pas.
 */
it('retombe sur le fichier d origine quand l entree est masquee', function () {
    ImageDeFond::whereIn('slug', ['presentation-apercu', 'presentation-directeur'])
        ->update(['visible' => false]);

    $reponse = $this->get(route('presentation.index'));

    $reponse->assertOk();
    $reponse->assertSee('images/presentation/apercu.jpg', false);
    $reponse->assertSee('images/presentation/silhouette.svg', false);
});

it('sert le portrait du directeur depuis la base', function () {
    ImageDeFond::where('slug', 'presentation-directeur')->update([
        'fichier' => 'images/fonds/portrait-dg.jpg',
    ]);

    $reponse = $this->get(route('presentation.index'));

    $reponse->assertSee('images/fonds/portrait-dg.jpg', false);
    $reponse->assertDontSee('images/presentation/silhouette.svg', false);
});

/**
 * Ces deux entrees sont des illustrations, pas des fonds de section : l'ecran
 * d'edition ne doit pas leur servir la consigne des fonds, qui parle d'un
 * voile sombre que rien ne leur applique.
 */
it('ne sert pas la consigne des fonds a une illustration', function () {
    Role::findOrCreate('administrateur');
    $admin = User::factory()->create();
    $admin->assignRole('administrateur');
    $this->actingAs($admin);

    $illustration = ImageDeFond::where('slug', 'presentation-apercu')->first();
    $fond = ImageDeFond::create(['slug' => 'section-essai', 'fichier' => 'images/essai.jpg']);

    Livewire::test(ImageDeFondFormulaire::class, ['element' => $illustration])
        ->assertSee('sans voile ni recadrage')
        ->assertDontSee('Un voile sombre est appliqué automatiquement');

    Livewire::test(ImageDeFondFormulaire::class, ['element' => $fond])
        ->assertSee('Un voile sombre est appliqué automatiquement')
        ->assertDontSee('sans voile ni recadrage');
});

it('distingue une illustration d un fond de section', function () {
    $illustration = ImageDeFond::where('slug', 'presentation-apercu')->first();
    $fond = ImageDeFond::create(['slug' => 'section-essai', 'fichier' => 'images/essai.jpg']);

    expect($illustration->estVisuelEnLigne())->toBeTrue();
    expect($fond->estVisuelEnLigne())->toBeFalse();
});

it('ignore une entree masquee', function () {
    ImageDeFond::where('slug', 'presentation-apercu')->update(['visible' => false]);

    expect(ImageDeFond::parSlugs(['presentation-apercu']))->toBeEmpty();
});

/**
 * La colonne « fichier » est NOT NULL, mais rien n'interdit une chaine vide.
 * C'est le gabarit qui rattrape ce cas, pas la requete.
 */
it('retombe sur le fichier d origine quand le chemin est vide', function () {
    ImageDeFond::where('slug', 'presentation-apercu')->update(['fichier' => '']);

    $this->get(route('presentation.index'))
        ->assertOk()
        ->assertSee('images/presentation/apercu.jpg', false);
});
