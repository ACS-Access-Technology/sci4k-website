<?php

use App\Livewire\Admin\AbonneNewsletterListe;
use App\Livewire\Admin\Configuration;
use App\Livewire\Admin\PagesStatiques;
use App\Models\AbonneNewsletter;
use App\Models\Parametre;
use App\Models\ReglageDeSection;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Les trois pages que le site sert par lui-meme.
 *
 * Elles ne figurent dans aucun des sept ecrans de page : la page d'attente du
 * mode maintenance, celle ou l'abonne se retire de la lettre d'information, et
 * la ligne de date qui coiffe chaque page legale.
 *
 * Leurs textes sont editables la ou l'editeur les cherchera — sous la case qui
 * ferme le site, sur l'ecran qui gouverne la lettre, et sur celui des pages
 * editables. Trois endroits, et non une liste generale : « ou se change ce
 * texte » doit avoir une reponse evidente.
 */
beforeEach(function () {
    Role::findOrCreate('administrateur');

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

/* ------------------------------------------------ page d'attente */

it('applique les textes de la page d attente', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('textes.titre_fr', 'Nous préparons quelque chose')
        ->set('textes.introduction_coordonnees_fr', 'Joignez-nous en attendant :')
        ->call('enregistrer')
        ->assertHasNoErrors();

    Parametre::poser('mode_maintenance', '1', 'general');

    // Il faut se DECONNECTER pour voir la page d'attente : un compte connecte
    // continue de lire le site pendant les travaux, et c'est voulu. Livewire
    // laisse l'administrateur authentifie apres son enregistrement.
    Auth::logout();

    $this->get('/')->assertStatus(503)
        ->assertSee('Nous préparons quelque chose', false)
        ->assertSee('Joignez-nous en attendant :', false);
});

it('garde la page d attente inchangee tant que rien n est saisi', function () {
    Parametre::poser('mode_maintenance', '1', 'general');

    $this->get('/')->assertStatus(503)->assertSee('Nous revenons très vite', false);
});

/* ------------------------------------------------ desinscription */

it('applique les textes de la page de desinscription', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    Livewire::actingAs($this->admin)
        ->test(AbonneNewsletterListe::class)
        ->set('textes.titre_confirmation_fr', 'Êtes-vous sûr ?')
        ->set('textes.libelle_bouton_fr', 'Oui, me retirer')
        ->call('enregistrerLesTextes')
        ->assertHasNoErrors();

    $this->get($abonne->lienDeDesinscription())->assertOk()
        ->assertSee('Êtes-vous sûr ?', false)
        ->assertSee('Oui, me retirer', false);
});

it('applique aussi les textes affiches apres le retrait', function () {
    $abonne = AbonneNewsletter::create(['email' => 'leon@exemple.ci']);

    Livewire::actingAs($this->admin)
        ->test(AbonneNewsletterListe::class)
        ->set('textes.titre_fait_fr', 'Voilà, c’est réglé')
        ->call('enregistrerLesTextes');

    // Le message de confirmation passe par un flash de session : il faut donc
    // SUIVRE la redirection pour le lire. S'arreter au 302 aurait produit un
    // test qui passe sans jamais regarder le texte.
    $this->followingRedirects()
        ->post(route('newsletter.desinscription.retirer', $abonne->jeton))
        ->assertOk()
        ->assertSee('Voilà, c’est réglé', false);
});

it('reserve ces textes a qui peut ecrire', function () {
    Role::findOrCreate('lecteur');
    $lecteur = User::factory()->create();
    $lecteur->assignRole('lecteur');

    Livewire::actingAs($lecteur)
        ->test(AbonneNewsletterListe::class)
        ->call('enregistrerLesTextes')
        ->assertForbidden();
});

/* ------------------------------------------------ pages legales */

it('applique la ligne de date des pages legales', function () {
    Livewire::actingAs($this->admin)
        ->test(PagesStatiques::class)
        ->set('titreFr', 'Politique de confidentialité')
        ->set('contenuFr', '<p>Texte.</p>')
        ->set('textes.mention_mise_a_jour_fr', 'Révisé en :date')
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Le :date est remplace a l'affichage : un texte modifiable qui perdrait
    // son marqueur afficherait « Révisé en :date ».
    $corps = $this->get(route('politique-confidentialite.index'))->assertOk()->getContent();

    expect($corps)->toContain('Révisé en ')
        ->and($corps)->not->toContain('Révisé en :date');
});

it('garde la ligne de date d origine tant que rien n est saisi', function () {
    expect($this->get(route('politique-confidentialite.index'))->assertOk()->getContent())
        ->toContain('Dernière mise à jour');
});

/* ------------------------------------------------ le filtre, partout */

it("n'ecrit que les cles declarees, sur les trois ecrans", function () {
    // `$textes` est une propriete publique : le navigateur en fixe le contenu,
    // CLES COMPRISES.
    $ecrans = [
        [Configuration::class, 'enregistrer', Configuration::SECTION_MAINTENANCE],
        [AbonneNewsletterListe::class, 'enregistrerLesTextes', AbonneNewsletterListe::SECTION],
        [PagesStatiques::class, 'enregistrer', PagesStatiques::SECTION],
    ];

    foreach ($ecrans as [$ecran, $action, $slug]) {
        $composant = Livewire::actingAs($this->admin)->test($ecran)
            ->set('textes.mise_en_page_fr', 'valeur injectée');

        if ($ecran === PagesStatiques::class) {
            $composant->set('titreFr', 'Titre');
        }

        $composant->call($action);

        expect(ReglageDeSection::where('slug', $slug)->first()?->option('mise_en_page_fr'))
            ->toBeNull("La cle a ete ecrite depuis $ecran");
    }
});
