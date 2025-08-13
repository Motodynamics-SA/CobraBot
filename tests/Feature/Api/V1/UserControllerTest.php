<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('unauthenticated users cannot access user info', function (): void {
    $this->getJson('/api/v1/user/info')
        ->assertStatus(401)
        ->assertJson(['error' => 'Unauthenticated.']);
});

test('authenticated users can access their info', function (): void {
    $user = User::factory()->create([
        'name' => 'Test User',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/user/info')
        ->assertSuccessful()
        ->assertJson([

            'user' => [
                'name' => 'Test User',
            ],
            'permissions' => [],

        ]);
});

test('admin users have dashboard permission', function (): void {
    $user = User::factory()->create([
        'name' => 'Admin User',
    ])->assignRole(RolesEnum::ADMINISTRATOR->value);

    $this->actingAs($user)
        ->getJson('/api/v1/user/info')
        ->assertSuccessful()
        ->assertJson([

            'user' => [
                'name' => 'Admin User',
            ],
            'permissions' => [
                'dashboard' => true,
            ],

        ]);
});

test('user managers have dashboard permission', function (): void {
    $user = User::factory()->create([
        'name' => 'Manager User',
    ])->assignRole(RolesEnum::USER_MANAGER->value);

    $this->actingAs($user)
        ->getJson('/api/v1/user/info')
        ->assertSuccessful()
        ->assertJson([

            'user' => [
                'name' => 'Manager User',
            ],
            'permissions' => [
                'dashboard' => true,
            ],

        ]);
});

test('regular users do not have dashboard permission', function (): void {
    $user = User::factory()->create([
        'name' => 'Regular User',
    ])->assignRole(RolesEnum::REGISTERED_USER->value);

    $this->actingAs($user)
        ->getJson('/api/v1/user/info')
        ->assertSuccessful()
        ->assertJson([

            'user' => [
                'name' => 'Regular User',
            ],
            'permissions' => [],

        ]);
});
