<?php

use App\Models\Encart;

/**
 * Le visuel d'un encart doit arriver jusqu'a la page publique.
 *
 * Il n'y arrivait pas : le formulaire acceptait une image, la stockait et
 * l'affichait en apercu dans le backoffice, mais aucune vue publique ne lisait
 * `image_source`. L'accueil montrait un visuel FIGE — la classe CSS
 * `service-bg-foncier`, celle du service « foncier » — quoi qu'on televerse ;
 * la page Services n'en montrait aucun. Le champ mentait.
 */
it('ne renvoie aucune adresse quand l encart n a pas de visuel', function () {
    expect(Encart::factory()->create(['image_source' => null])->urlImage())->toBeNull();
});

it('renvoie l adresse du visuel televerse', function () {
    $encart = Encart::factory()->create(['image_source' => 'storage/encarts/promo.jpg']);

    expect($encart->urlImage())->toBe(asset('storage/encarts/promo.jpg'));
});

/* ------------------------------------------------------------------ */
/* L'accueil                                                           */
/* ------------------------------------------------------------------ */

it('affiche le visuel de l annonce sur l accueil', function () {
    Encart::updateOrCreate(['slug' => 'accueil.annonce'], [
        
        'visible' => true,
        'titre_fr' => 'Parcelles viabilisées',
        'image_source' => 'storage/encarts/promo.jpg',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee("background-image:url('".asset('storage/encarts/promo.jpg')."')", false);
});

/**
 * Sans visuel, l'accueil garde l'image d'origine de la maquette : retirer la
 * classe aurait laisse une colonne vide sur une grille a deux colonnes.
 */
it('retombe sur le visuel d origine quand l annonce n en a pas', function () {
    Encart::updateOrCreate(['slug' => 'accueil.annonce'], [
        
        'visible' => true,
        'titre_fr' => 'Parcelles viabilisées',
        'image_source' => null,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('ad-house-media service-bg-foncier', false);
});

/** Le visuel televerse remplace la classe figee, il ne s'y ajoute pas. */
it('n affiche plus le visuel fige quand l annonce a le sien', function () {
    Encart::updateOrCreate(['slug' => 'accueil.annonce'], [
        
        'visible' => true,
        'titre_fr' => 'Parcelles viabilisées',
        'image_source' => 'storage/encarts/promo.jpg',
    ]);

    $this->get('/')->assertDontSee('ad-house-media service-bg-foncier', false);
});

/* ------------------------------------------------------------------ */
/* La page Services                                                    */
/* ------------------------------------------------------------------ */

it('affiche le visuel de l annonce sur la page Services', function () {
    Encart::updateOrCreate(['slug' => 'services.annonce'], [
        'visible' => true,
        'titre_fr' => 'Réductions de fin d’année',
        'image_source' => 'storage/encarts/promo.jpg',
    ]);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee("background-image:url('".asset('storage/encarts/promo.jpg')."')", false);
});

/**
 * Meme presentation que l'accueil : la carte .ad-house, et non plus la
 * banderole .biens-cta-banner, qui n'avait aucun emplacement d'image et dont
 * le texte blanc se perdait sur le fond clair de la page.
 */
it('presente l annonce des services comme celle de l accueil', function () {
    Encart::updateOrCreate(['slug' => 'services.annonce'], [
        'visible' => true,
        'titre_fr' => 'Réductions de fin d’année',
        'image_source' => 'storage/encarts/promo.jpg',
    ]);

    $rendu = $this->get(route('services.index'))->assertOk()->getContent();

    expect($rendu)->toContain('class="ad-section"')
        ->toContain('class="ad-house"')
        ->and($rendu)->not->toContain('biens-cta-banner')
        ->and($rendu)->not->toContain('services-cta-section');
});

it('retombe sur le visuel d origine quand l annonce des services n en a pas', function () {
    Encart::updateOrCreate(['slug' => 'services.annonce'], [
        'visible' => true,
        'titre_fr' => 'Réductions de fin d’année',
        'image_source' => null,
    ]);

    $this->get(route('services.index'))
        ->assertOk()
        ->assertSee('ad-house-media service-bg-foncier', false);
});
