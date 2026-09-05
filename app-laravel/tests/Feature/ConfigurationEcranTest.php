<?php

use App\Livewire\Admin\Configuration;
use App\Mail\EssaiDeMessagerie;
use App\Models\Parametre;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/*
 * Ecran de configuration generale.
 *
 * Il se distingue des ecrans de contenu sur deux points, et les tests portent
 * d'abord sur eux : il est reserve aux administrateurs, et il manipule le seul
 * secret du projet.
 */
beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $role) {
        Role::findOrCreate($role);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');

    $this->editeur = User::factory()->create();
    $this->editeur->assignRole('editeur');
});

it('refuse l ecran a un editeur', function () {
    $this->actingAs($this->editeur)->get('/admin/configuration')->assertForbidden();
});

it('ouvre l ecran a un administrateur', function () {
    $this->actingAs($this->admin)->get('/admin/configuration')->assertOk();
});

/*
 * Le role est verifie a DEUX endroits, et le test porte sur les deux.
 *
 * D'abord au montage : un editeur n'obtient jamais de composant, donc jamais
 * l'instantane signe qu'exigerait un appel a /livewire/update. Ensuite dans
 * enregistrer() lui-meme, car Livewire ne rejoue pas le middleware de route
 * sur ses propres requetes — une route protegee ne protege que la page.
 */
it('refuse le composant a un editeur', function () {
    // Livewire n'AFFICHE pas le 403 en exception : il l'expose sur le
    // composant de test. Chercher une exception ici serait une mesure a cote
    // du point sensible — elle passerait au vert le jour ou la garde
    // disparaitrait.
    Livewire::actingAs($this->editeur)
        ->test(Configuration::class)
        ->assertForbidden();
});

it('verifie aussi le role dans l action d enregistrement', function () {
    // Mesure au point sensible : la garde doit exister DANS la methode, pas
    // seulement sur la route. On l'interroge donc sur le composant lui-meme.
    $methode = new ReflectionMethod(Configuration::class, 'enregistrer');
    $source = implode('', array_slice(
        file($methode->getFileName()),
        $methode->getStartLine() - 1,
        $methode->getEndLine() - $methode->getStartLine() + 1,
    ));

    expect($source)->toContain('abort_unless($this->peutEcrire(), 403)');
});

it('enregistre les reglages saisis', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.nom_du_site', 'SCI4K')
        ->set('valeurs.telephone', '+225 07 06 16 50 29')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('nom_du_site'))->toBe('SCI4K')
        ->and(Parametre::lire('telephone'))->toBe('+225 07 06 16 50 29');
});

it('range chaque reglage dans le groupe de son onglet', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.nom_du_site', 'SCI4K')
        ->set('valeurs.facebook', 'https://facebook.com/sci4k')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(DB::table('parametres')->where('cle', 'nom_du_site')->value('groupe'))->toBe('general')
        ->and(DB::table('parametres')->where('cle', 'facebook')->value('groupe'))->toBe('social');
});

it('refuse un nom de site vide et une adresse mal formee', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.nom_du_site', '')
        ->set('valeurs.email_public', 'pas-une-adresse')
        ->call('enregistrer')
        ->assertHasErrors(['valeurs.nom_du_site', 'valeurs.email_public']);
});

/*
 * Le mot de passe SMTP : trois garanties, testees chacune a son point sensible.
 */
it('ne renvoie jamais le mot de passe SMTP au navigateur', function () {
    Parametre::poser('smtp_mot_de_passe', 'secret-tres-devinable', 'messagerie');

    $composant = Livewire::actingAs($this->admin)->test(Configuration::class);

    // Deux mesures distinctes : la propriete exposee par Livewire, et le HTML
    // rendu. Verifier l'une sans l'autre laisserait passer le cas ou le champ
    // est vide mais la valeur reapparait dans un attribut « value ».
    $composant->assertSet('valeurs.smtp_mot_de_passe', '');
    expect($composant->html())->not->toContain('secret-tres-devinable');
});

it('conserve le mot de passe SMTP quand le champ est laisse vide', function () {
    Parametre::poser('smtp_mot_de_passe', 'secret-tres-devinable', 'messagerie');

    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.smtp_hote', 'smtp.sci4k.com')
        ->call('enregistrer')
        ->assertHasNoErrors();

    // Le formulaire ne peut pas reafficher le secret : un champ vide veut donc
    // dire « je n'y touche pas », et non « efface ».
    expect(Parametre::lire('smtp_mot_de_passe'))->toBe('secret-tres-devinable');
});

it('remplace le mot de passe SMTP quand un nouveau est saisi', function () {
    Parametre::poser('smtp_mot_de_passe', 'ancien', 'messagerie');

    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.smtp_mot_de_passe', 'nouveau')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('smtp_mot_de_passe'))->toBe('nouveau')
        ->and(DB::table('parametres')->where('cle', 'smtp_mot_de_passe')->value('valeur'))
        ->not->toContain('nouveau');
});

it('affiche toutes les erreurs, y compris celles d un onglet ferme', function () {
    // Le nom du site est dans l'onglet « general », l'adresse publique dans
    // « contact ». Une erreur sur un onglet ferme doit rester visible, sans
    // quoi le bouton parait mort.
    $composant = Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('onglet', 'social')
        ->set('valeurs.nom_du_site', '')
        ->call('enregistrer');

    $composant->assertHasErrors('valeurs.nom_du_site');
    expect($composant->html())->toContain('nom du site');
});

/*
 * L'essai d'envoi.
 *
 * Trois garanties : il refuse tant qu'aucun serveur n'est enregistre, il part
 * vers l'adresse du COMPTE et non vers une adresse saisie, et il rend la
 * raison de l'echec plutot qu'un message vague.
 */
it('refuse l essai tant qu aucun serveur SMTP n est enregistre', function () {
    Mail::fake();

    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->call('envoyerUnEssai')
        ->assertSet('resultatEssai', __('Renseignez puis enregistrez un serveur SMTP avant de tester.'));

    Mail::assertNothingSent();
});

it('envoie l essai a l adresse du compte connecte', function () {
    Mail::fake();
    Parametre::poser('smtp_hote', 'smtp.sci4k.com', 'messagerie');

    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->call('envoyerUnEssai');

    // Le point sensible n'est pas qu'un message parte, mais qu'il parte a
    // l'adresse du compte : un destinataire saisissable ferait de l'ecran un
    // relais d'envoi signe du domaine du site.
    Mail::assertSent(EssaiDeMessagerie::class, fn ($message) => $message->hasTo($this->admin->email));
});

it('refuse l essai a un editeur', function () {
    // Meme raison que pour l'enregistrement : Livewire ne rejoue pas le
    // middleware de route sur ses propres requetes.
    $methode = new ReflectionMethod(Configuration::class, 'envoyerUnEssai');
    $source = implode('', array_slice(
        file($methode->getFileName()),
        $methode->getStartLine() - 1,
        $methode->getEndLine() - $methode->getStartLine() + 1,
    ));

    expect($source)->toContain('abort_unless($this->peutEcrire(), 403)');
});

/*
 * Les cases a cocher.
 *
 * Une case decochee renvoie une chaine VIDE, et `Parametre::lire()` traite le
 * vide comme « rien d'enregistre » : la lecture retombait sur le defaut. Une
 * case dont le defaut est « oui » ne pouvait donc JAMAIS etre decochee.
 *
 * Le cas le plus couteux est « Autoriser l'indexation par les moteurs de
 * recherche » : c'est le reglage qui decide si le site est visible sur Google,
 * et son aide invite explicitement a le decocher tant que le site n'est pas
 * ouvert au public. Il ne se decochait pas.
 */
it('enregistre une case decochee comme decochee, et non comme absente', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.autoriser_indexation', '')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::lire('autoriser_indexation'))->toBe('0')
        // Le defaut « vrai » ne doit plus reprendre la main : c'est tout
        // l'objet du correctif.
        ->and(Parametre::actif('autoriser_indexation', true))->toBeFalse();
});

it('enregistre une case cochee', function () {
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.autoriser_indexation', '1')
        ->call('enregistrer')
        ->assertHasNoErrors();

    expect(Parametre::actif('autoriser_indexation', false))->toBeTrue();
});

it('coupe reellement l indexation du site quand la case est decochee', function () {
    // Le bout du fil : la case decochee doit se voir sur la page publique ET
    // dans robots.txt, sans quoi le reglage n'aura fait que changer une ligne
    // en base.
    Livewire::actingAs($this->admin)
        ->test(Configuration::class)
        ->set('valeurs.autoriser_indexation', '')
        ->call('enregistrer');

    $this->get('/')->assertOk()->assertSee('noindex', false);
    $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /\n", false);
});
