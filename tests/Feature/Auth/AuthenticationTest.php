<?php

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Run the role seeder first
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('login screen can be rendered', function (): void {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function (): void {
    $user = User::factory()->create()->assignRole(RolesEnum::REGISTERED_USER->value);

    // First get the login page to obtain CSRF token
    $response = $this->get('/login');

    // Then attempt login with the token
    $response = $this->withSession(['_token' => csrf_token()])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            '_token' => csrf_token(),
            'captcha' => 'test',
        ]);

    $this->assertAuthenticated();
    $response->assertStatus(302);
    $response->assertRedirect(route('price-updater.data-entry.index'));
    // assert the user is logged in
    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        '_token' => csrf_token(),
        'captcha' => 'test',
    ], [
        'Accept' => 'application/json',
    ]);

    $this->assertGuest();
    $response->assertStatus(422)
        ->assertJson([
            'message' => 'These credentials do not match our records.',
        ]);
});

test('users can logout', function (): void {
    $user = User::factory()->create();

    // First get a page to establish session and CSRF token
    $this->actingAs($user)->get('/');

    $response = $this->followingRedirects()
        ->post('/logout', [
            '_token' => session('_token'),
        ]);

    $response->assertStatus(200);
    $this->assertGuest();
});
