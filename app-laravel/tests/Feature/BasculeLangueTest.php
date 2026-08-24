<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('administrateur');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('administrateur');
});

it('sert l administration en francais par defaut', function () {
    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('bascule en anglais et le retient', function () {
    $this->actingAs($this->admin)->get('/langue/en')->assertRedirect();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('en');
});

it('revient au francais', function () {
    $this->actingAs($this->admin)->get('/langue/en');
    $this->actingAs($this->admin)->get('/langue/fr')->assertRedirect();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('refuse une langue inconnue et reste en francais', function () {
    $this->actingAs($this->admin)->get('/langue/de')->assertNotFound();

    $this->actingAs($this->admin)->get('/admin');

    expect(app()->getLocale())->toBe('fr');
});

it('affiche les libelles en anglais une fois bascule', function () {
    $this->actingAs($this->admin)->get('/langue/en');

    $this->actingAs($this->admin)->get('/admin')->assertSee('Dashboard');
});

it('affiche les libelles en francais par defaut', function () {
    $this->actingAs($this->admin)->get('/admin')->assertSee('Tableau de bord');
});
