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

    // /admin renvoie vers /dashboard depuis qu'il ne sert plus une page
    // souche. Le controle de role reste pose sur le groupe, donc sur cette
    // redirection : un role refuse ne l'obtient pas.
    $this->actingAs($user)->get('/admin')->assertRedirect('/dashboard');
});

it('laisse entrer un administrateur', function () {
    $user = User::factory()->create();
    $user->assignRole('administrateur');

    // /admin renvoie vers /dashboard depuis qu'il ne sert plus une page
    // souche. Le controle de role reste pose sur le groupe, donc sur cette
    // redirection : un role refuse ne l'obtient pas.
    $this->actingAs($user)->get('/admin')->assertRedirect('/dashboard');
});

it('refuse un utilisateur sans role', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});
