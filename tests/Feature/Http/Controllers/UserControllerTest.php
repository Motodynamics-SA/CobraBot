<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Run the role seeder first
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Create admin user
    $this->admin = User::factory()->create([
        'email' => 'admin@example.com',
        'name' => 'Admin User',
    ])->assignRole(RolesEnum::ADMINISTRATOR->value)->load('roles');

    // Create user manager
    $this->userManager = User::factory()->create([
        'email' => 'user_manager@example.com',
        'name' => 'User Manager',
    ])->assignRole(RolesEnum::USER_MANAGER->value)->load('roles');

    // Create registered user
    $this->regularUser = User::factory()->create([
        'email' => 'registered_user@example.com',
        'name' => 'Registered User',
    ])->assignRole(RolesEnum::REGISTERED_USER->value)->load('roles');
});

test('index shows users list to authorized users', function (): void {
    // Admin can view users
    $response = test()->actingAs($this->admin)->get(route('users.index'));
    $response->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('users/index')
                ->has('users')
        );

    // User manager can view users
    test()->actingAs($this->userManager)
        ->get(route('users.index'))
        ->assertStatus(200);

    // Regular user cannot view users
    test()->actingAs($this->regularUser)
        ->get(route('users.index'))
        ->assertStatus(403);
});

test('create shows form to authorized users', function (): void {
    // Admin can view create form
    $response = test()->actingAs($this->admin)->get(route('users.create'));
    $response->assertStatus(200)
        ->assertInertia(
            fn ($page) => $page
                ->component('users/create')
                ->has('roles')
        );

    // User manager can view create form
    test()->actingAs($this->userManager)
        ->get(route('users.create'))
        ->assertStatus(200);

    // Regular user cannot view create form
    test()->actingAs($this->regularUser)
        ->get(route('users.create'))
        ->assertStatus(403);
});

test('store creates new user', function (): void {
    test()->actingAs($this->admin)->get(route('users.create'));

    $userData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => RolesEnum::REGISTERED_USER->value,
        '_token' => session('_token'),
    ];

    $response = test()->post(route('users.store'), $userData);

    $response->assertRedirect(route('users.index'));

    $user = User::where('email', 'test@example.com')->first();

    expect($user)
        ->name->toBe('Test User')
        ->and($user->hasRole(RolesEnum::REGISTERED_USER->value))->toBeTrue();
});

test('user manager cannot modify admin', function (): void {
    // Create an admin user to be modified
    $adminToModify = User::factory()->create();
    $adminToModify->syncRoles([RolesEnum::ADMINISTRATOR->value]);

    test()->actingAs($this->userManager)
        ->get(route('users.edit', $adminToModify));

    $response = test()->put(route('users.update', $adminToModify), [
        'name' => 'Updated Name',
        'email' => $adminToModify->email,
        'role' => RolesEnum::ADMINISTRATOR->value,
        '_token' => session('_token'),
    ]);

    $response->assertStatus(403);

    expect(User::find($adminToModify->id))
        ->name->not->toBe('Updated Name');
});

test('soft deletes user', function (): void {
    $user = User::factory()->create();

    test()->actingAs($this->admin)
        ->get(route('users.index'));

    $response = test()->delete(route('users.destroy', $user), [
        '_token' => session('_token'),
    ]);

    $response->assertStatus(302);

    test()->assertSoftDeleted('users', ['id' => $user->id]);

    expect(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(User::find($user->id))->toBeNull();
});
