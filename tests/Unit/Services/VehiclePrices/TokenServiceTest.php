<?php

namespace Tests\Unit\Services\VehiclePrices;

use App\Services\VehiclePrices\TokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TokenServiceTest extends TestCase {
    use RefreshDatabase;

    private TokenService $tokenService;

    protected function setUp(): void {
        parent::setUp();

        // Set up test configuration
        config([
            'services.vehicle_prices_api' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'token_url' => 'https://identity-stage.goorange.sixt.com/auth/realms/External/protocol/openid-connect/token',
                'base_url' => 'https://api.example.com',
                'timeout' => 30,
                'cache_ttl' => 3500,
            ],
        ]);

        $this->tokenService = new TokenService;
    }

    public function test_get_access_token_fetches_new_token_when_cache_empty() {
        // Mock the HTTP response
        Http::fake([
            'identity-stage.goorange.sixt.com/*' => Http::response([
                'access_token' => 'test_token_123',
                'expires_in' => 3600,
            ], 200),
        ]);

        // Clear any existing cache
        Cache::forget('vehicle_prices_access_token');

        // Get token
        $token = $this->tokenService->getAccessToken();

        // Assert token was fetched and cached
        $this->assertEquals('test_token_123', $token);
        $this->assertEquals('test_token_123', Cache::get('vehicle_prices_access_token'));
    }

    public function test_get_access_token_returns_cached_token_when_available() {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'cached_token_456', 3500);

        // Get token
        $token = $this->tokenService->getAccessToken();

        // Assert cached token was returned
        $this->assertEquals('cached_token_456', $token);

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    public function test_clear_cached_token_removes_token_from_cache() {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Clear the token
        $this->tokenService->clearCachedToken();

        // Assert token was removed
        $this->assertNull(Cache::get('vehicle_prices_access_token'));
    }

    public function test_make_authenticated_request_uses_cached_token() {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'cached_token', 3500);

        // Mock the API response
        Http::fake([
            'api.example.com/*' => Http::response(['prices' => [100, 200, 300]], 200),
        ]);

        // Make request
        $response = $this->tokenService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Assert response was successful
        $this->assertEquals(['prices' => [100, 200, 300]], $response);

        // Verify request was made with cached token
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.example.com/prices' &&
                   $request->header('Authorization')[0] === 'Bearer cached_token';
        });
    }

    public function test_make_authenticated_request_refreshes_token_on_401() {
        // Set an expired cached token
        Cache::put('vehicle_prices_access_token', 'expired_token', 3500);

        // Mock responses: first 401, then successful token fetch, then successful API call
        Http::fake([
            'identity-stage.goorange.sixt.com/*' => Http::response([
                'access_token' => 'new_token_789',
                'expires_in' => 3600,
            ], 200),
            'api.example.com/*' => Http::sequence()
                ->push(['error' => 'Unauthorized'], 401)
                ->push(['prices' => [100, 200, 300]], 200),
        ]);

        // Make request
        $response = $this->tokenService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Assert response was successful after retry
        $this->assertEquals(['prices' => [100, 200, 300]], $response);

        // Verify new token was cached
        $this->assertEquals('new_token_789', Cache::get('vehicle_prices_access_token'));
    }

    public function test_make_authenticated_request_returns_null_on_final_failure() {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Mock API to always return 500
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        // Make request
        $response = $this->tokenService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Assert response is null
        $this->assertNull($response);
    }
}
