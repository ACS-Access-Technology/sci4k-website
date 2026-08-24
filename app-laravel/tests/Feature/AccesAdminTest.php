<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['administrateur', 'editeur', 'lecteur'] as $nom) {
        Role::findOrCreate($nom);
    }
});

it('renvoie un visiteur non connecte vers la connexion', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('laisse entrer un editeur', function () {
    $user = User::factory()->create();
    $user->assignRole('editeur');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('laisse entrer un administrateur', function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur');

    $this->actingAs($user)->get('/admin')->assertOk();
});

it('refuse un utilisateur sans role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
