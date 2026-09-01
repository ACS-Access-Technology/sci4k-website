<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Un compte connecte ne suffit plus : il faut un role.
     *
     * Le tableau de bord montre le journal des activites — qui a modifie quoi,
     * et quand. Il ne s'ouvre donc qu'a un compte entre par la porte prevue,
     * c'est-a-dire par une invitation, qui pose toujours un role.
     */
    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        Role::findOrCreate('lecteur');

        $user = User::factory()->create();
        $user->assignRole('lecteur');
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}
