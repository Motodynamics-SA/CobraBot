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
    $mock->shouldReceive('getEmail')->andReturn('john.doe@motodynamics.gr');
    $mock->shouldReceive('getName')->andReturn('John Doe');
    $mock->shouldReceive('getId')->andReturn('microsoft-123456');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    expect(User::where('email', 'john.doe@motodynamics.gr')->exists())->toBeFalse();

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@motodynamics.gr')->first();
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
        'email' => 'john.doe@sixt.gr',
        'name' => 'Old Name',
        'provider_id' => 'old-id',
        'provider' => 'microsoft',
    ]);
    $existingUser->assignRole(RolesEnum::REGISTERED_USER->value);

    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('john.doe@sixt.gr');
    $mock->shouldReceive('getName')->andReturn('John Doe Updated');
    $mock->shouldReceive('getId')->andReturn('microsoft-789012');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@sixt.gr')->first();
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
        'email' => 'john.doe@motodynamics.gr',
        'name' => 'John Doe',
        'provider_id' => 'microsoft-123456',
        'provider' => 'microsoft',
    ]);

    expect($existingUser->roles->isEmpty())->toBeTrue();

    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('john.doe@motodynamics.gr');
    $mock->shouldReceive('getName')->andReturn('John Doe');
    $mock->shouldReceive('getId')->andReturn('microsoft-123456');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));

    $user = User::where('email', 'john.doe@motodynamics.gr')->first();
    expect($user->hasRole(RolesEnum::USER_MANAGER->value))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback authenticates user after successful callback', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('auth.test@sixt.gr');
    $mock->shouldReceive('getName')->andReturn('Auth Test User');
    $mock->shouldReceive('getId')->andReturn('microsoft-auth-test');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $this->assertGuest();

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'auth.test@sixt.gr')->first();
    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback rejects user with invalid email domain', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('user@invalid-domain.com');
    $mock->shouldReceive('getName')->andReturn('Invalid User');
    $mock->shouldReceive('getId')->andReturn('microsoft-invalid');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);
    $response->assertSessionHas('errors', fn ($errors): bool => $errors->first('email') === 'Access denied. Only @motodynamics.gr and @sixt.gr email addresses are allowed. Your email was: user@invalid-domain.com');

    $this->assertGuest();
    expect(User::where('email', 'user@invalid-domain.com')->exists())->toBeFalse();
});

test('handleProviderCallback rejects user with gmail domain', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('user@gmail.com');
    $mock->shouldReceive('getName')->andReturn('Gmail User');
    $mock->shouldReceive('getId')->andReturn('microsoft-gmail');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);

    $this->assertGuest();
    expect(User::where('email', 'user@gmail.com')->exists())->toBeFalse();
});

test('handleProviderCallback accepts user with motodynamics.gr domain', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('employee@motodynamics.gr');
    $mock->shouldReceive('getName')->andReturn('Motodynamics Employee');
    $mock->shouldReceive('getId')->andReturn('microsoft-motodynamics');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'employee@motodynamics.gr')->first();
    expect($user)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback accepts user with sixt.gr domain', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('employee@sixt.gr');
    $mock->shouldReceive('getName')->andReturn('Sixt Employee');
    $mock->shouldReceive('getId')->andReturn('microsoft-sixt');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'employee@sixt.gr')->first();
    expect($user)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback accepts user with scify.org domain', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('employee@scify.org');
    $mock->shouldReceive('getName')->andReturn('Scify Employee');
    $mock->shouldReceive('getId')->andReturn('microsoft-scify');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('price-updater.data-entry.index'));
    $this->assertAuthenticated();

    $user = User::where('email', 'employee@scify.org')->first();
    expect($user)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

test('handleProviderCallback rejects user with invalid email format', function (): void {
    $mock = \Mockery::mock(SocialiteUser::class);
    $mock->shouldReceive('getEmail')->andReturn('invalid-email-without-at');
    $mock->shouldReceive('getName')->andReturn('Invalid Email User');
    $mock->shouldReceive('getId')->andReturn('microsoft-invalid-email');

    $driver = \Mockery::mock('Laravel\Socialite\Two\MicrosoftProvider');
    $driver->shouldReceive('user')->once()->andReturn($mock);

    Socialite::shouldReceive('driver')->with('microsoft')->once()->andReturn($driver);

    $response = $this->get('/login/microsoft/callback');

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors(['email']);
    $response->assertSessionHas('errors', fn ($errors): bool => $errors->first('email') === 'Invalid email format.');

    $this->assertGuest();
    expect(User::where('email', 'invalid-email-without-at')->exists())->toBeFalse();
});
