<?php

declare(strict_types=1);

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
});

test('redirectToProvider redirects to Microsoft OAuth', function (): void {
    $mock = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $mock->shouldReceive('redirect')->once()->andReturn(redirect('https://login.microsoftonline.com/oauth2/v2.0/authorize'));

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($mock);

    $response = $this->get('/login/microsoft');

    $response->assertRedirect();
});

test('handleProviderCallback creates new user and assigns role', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('john.doe@example.com');
    $mock->shouldReceive('getName')->andReturn('John Doe');
    $mock->shouldReceive('getId')->andReturn('microsoft-123456');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    expect(User::where('email', 'john.doe@example.com')->exists())->toBeFalse();

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Doe');
    expect($user->provider_id)->toBe('microsoft-123456');
    expect($user->provider)->toBe('microsoft');
    expect($user->password)->toBeNull();
    expect($user->hasRole(RolesEnum::USER_MANAGER->value))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback updates existing user without changing role', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'john.doe@example.com',
        'name' => 'Old Name',
        'provider_id' => 'old-id',
        'provider' => 'microsoft',
    ]);
    $existingUser->assignRole(RolesEnum::REGISTERED_USER->value);

    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('john.doe@example.com');
    $mock->shouldReceive('getName')->andReturn('John Doe Updated');
    $mock->shouldReceive('getId')->andReturn('microsoft-789012');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@example.com')->first();
    expect($user->id)->toBe($existingUser->id);
    expect($user->name)->toBe('John Doe Updated');
    expect($user->provider_id)->toBe('microsoft-789012');
    expect($user->provider)->toBe('microsoft');
    expect($user->hasRole(RolesEnum::REGISTERED_USER->value))->toBeTrue();
    expect($user->hasRole(RolesEnum::USER_MANAGER->value))->toBeFalse();

    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback handles user with no roles by assigning user manager role', function (): void {
    $existingUser = User::factory()->create([
        'email' => 'john.doe@example.com',
        'name' => 'John Doe',
        'provider_id' => 'microsoft-123456',
        'provider' => 'microsoft',
    ]);

    expect($existingUser->roles->isEmpty())->toBeTrue();

    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('john.doe@example.com');
    $mock->shouldReceive('getName')->andReturn('John Doe');
    $mock->shouldReceive('getId')->andReturn('microsoft-123456');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@example.com')->first();
    expect($user->hasRole(RolesEnum::USER_MANAGER->value))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback authenticates user after successful callback', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('auth.test@example.com');
    $mock->shouldReceive('getName')->andReturn('Auth Test User');
    $mock->shouldReceive('getId')->andReturn('microsoft-auth-test');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $this->assertGuest();

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'auth.test@example.com')->first();
    $this->assertAuthenticatedAs($user);
});
