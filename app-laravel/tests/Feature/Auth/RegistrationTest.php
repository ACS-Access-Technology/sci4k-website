<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * L'inscription publique est fermee, et ce fichier le verifie.
 *
 * Il testait l'inverse : qu'un inconnu pouvait creer un compte et se
 * retrouvait connecte. C'etait la description exacte de la faille — un
 * compte sans role ni statut, actif immediatement, qui atteignait le tableau
 * de bord et le journal des activites qu'il affiche.
 *
 * Un test qui se contenterait de se sauter quand la fonction est desactivee
 * ne prouverait rien : il resterait vert le jour ou quelqu'un remettrait
 * Features::registration() dans la configuration.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_fonction_d_inscription_est_desactivee(): void
    {
        $this->assertNotContains(
            Features::registration(),
            config('fortify.features'),
            "L'inscription publique doit rester fermee : les comptes du backoffice naissent d'une invitation.",
        );
    }

    public function test_les_adresses_d_inscription_n_existent_plus(): void
    {
        $this->assertFalse(app('router')->has('register'));
        $this->assertFalse(app('router')->has('register.store'));

        $this->get('/register')->assertNotFound();
        $this->post('/register', [
            'name' => 'Inconnu',
            'email' => 'inconnu@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'inconnu@example.com']);
    }

    public function test_la_page_de_connexion_ne_propose_plus_de_creer_un_compte(): void
    {
        $reponse = $this->get('/login');

        $reponse->assertOk();
        $reponse->assertDontSee('/register');
        // L'apostrophe sort echappee en &#039; : on n'assure que la partie
        // qui n'en porte pas, plutot que d'ecrire l'entite dans le test.
        $reponse->assertSee('accès au backoffice est délivré par un administrateur.', false);
    }

    /** Le losange du kit de demarrage Laravel a cede la place au logo du site. */
    public function test_la_page_de_connexion_porte_le_logo_du_site(): void
    {
        $reponse = $this->get('/login');

        $reponse->assertSee('images/image (3).png', false);
        // Le premier segment du trace SVG du losange Laravel.
        $reponse->assertDontSee('M17.2 5.633 8.6.855 0 5.633v26.51', false);
    }

    /**
     * Le tableau de bord exigeait un compte, pas un role. Depuis que
     * l'inscription est fermee, tout compte vient d'une invitation et porte
     * donc un role — mais la garde reste, sans quoi un compte cree a la main
     * en base rouvrirait la meme porte.
     */
    public function test_un_compte_sans_role_n_atteint_pas_le_tableau_de_bord(): void
    {
        Role::findOrCreate('administrateur');

        $sansRole = User::factory()->create();

        $this->actingAs($sansRole)->get('/dashboard')->assertForbidden();
        $this->actingAs($sansRole)->get('/admin/pages-editables')->assertForbidden();

        $administrateur = User::factory()->create();
        $administrateur->assignRole('administrateur');

        $this->actingAs($administrateur)->get('/dashboard')->assertOk();
    }
}
