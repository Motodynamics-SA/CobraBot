<?php

declare(strict_types=1);

namespace Tests\Unit\Services\VehiclePrices;

use App\Exceptions\VehiclePrices\APIRequestException;
use App\Exceptions\VehiclePrices\AuthenticationException;
use App\Services\VehiclePrices\VehiclePricesService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class VehiclePricesAPIAccessTokenTest extends TestCase {
    private VehiclePricesService $vehiclePricesService;

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

        $this->vehiclePricesService = new VehiclePricesService;
    }

    public function test_get_access_token_fetches_new_token_when_cache_empty(): void {
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
        $token = $this->vehiclePricesService->getAccessToken();

        // Assert token was fetched and cached
        $this->assertEquals('test_token_123', $token);
        $this->assertEquals('test_token_123', Cache::get('vehicle_prices_access_token'));
    }

    public function test_get_access_token_returns_cached_token_when_available(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'cached_token_456', 3500);

        // Get token
        $token = $this->vehiclePricesService->getAccessToken();

        // Assert cached token was returned
        $this->assertEquals('cached_token_456', $token);

        // Verify no HTTP request was made
        Http::assertNothingSent();
    }

    public function test_clear_cached_token_removes_token_from_cache(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Clear the token
        $this->vehiclePricesService->clearCachedToken();

        // Assert token was removed
        $this->assertNull(Cache::get('vehicle_prices_access_token'));
    }

    public function test_make_authenticated_request_uses_cached_token(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'cached_token', 3500);

        // Mock the API response
        Http::fake([
            'api.example.com/*' => Http::response(['prices' => [100, 200, 300]], 200),
        ]);

        // Make request
        $response = $this->vehiclePricesService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Assert response was successful
        $this->assertEquals(['prices' => [100, 200, 300]], $response);

        // Verify request was made with cached token
        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.example.com/prices' &&
               $request->header('Authorization')[0] === 'Bearer cached_token');
    }

    public function test_make_authenticated_request_refreshes_token_on_401(): void {
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
        $response = $this->vehiclePricesService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Assert response was successful after retry
        $this->assertEquals(['prices' => [100, 200, 300]], $response);

        // Verify new token was cached
        $this->assertEquals('new_token_789', Cache::get('vehicle_prices_access_token'));
    }

    public function test_make_authenticated_request_throws_exception_immediately_for_non_401_errors(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Mock API to return 500 (should not retry)
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'Server Error'], 500),
        ]);

        // Make request and expect exception immediately
        $this->expectException(APIRequestException::class);
        $this->expectExceptionMessage('API request failed');

        $this->vehiclePricesService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);
    }

    public function test_make_authenticated_request_throws_exception_after_token_refresh_retry(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Mock responses: first 401, then successful token fetch, then 401 again
        Http::fake([
            'identity-stage.goorange.sixt.com/*' => Http::response([
                'access_token' => 'new_token_789',
                'expires_in' => 3600,
            ], 200),
            'api.example.com/*' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        // Make request and expect exception after retry
        $this->expectException(APIRequestException::class);
        $this->expectExceptionMessage('API request failed');

        $this->vehiclePricesService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);
    }

    public function test_get_access_token_throws_exception_when_token_fetch_fails(): void {
        // Mock the HTTP response to return an error
        Http::fake([
            'identity-stage.goorange.sixt.com/*' => Http::response(['error' => 'Invalid credentials'], 401),
        ]);

        // Clear any existing cache
        Cache::forget('vehicle_prices_access_token');

        // Expect authentication exception
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Failed to fetch access token from API');

        $this->vehiclePricesService->getAccessToken();
    }

    public function test_get_access_token_throws_exception_when_no_token_in_response(): void {
        // Mock the HTTP response to return success but no token
        Http::fake([
            'identity-stage.goorange.sixt.com/*' => Http::response(['expires_in' => 3600], 200),
        ]);

        // Clear any existing cache
        Cache::forget('vehicle_prices_access_token');

        // Expect authentication exception
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('No access token received from API');

        $this->vehiclePricesService->getAccessToken();
    }

    public function test_make_authenticated_request_only_retries_on_401_errors(): void {
        // Set a cached token
        Cache::put('vehicle_prices_access_token', 'test_token', 3500);

        // Mock API to return 403 (Forbidden) - should not retry
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'Forbidden'], 403),
        ]);

        // Make request and expect exception immediately (no retry)
        $this->expectException(APIRequestException::class);
        $this->expectExceptionMessage('API request failed');

        $this->vehiclePricesService->makeAuthenticatedRequest('https://api.example.com/prices', ['test' => 'data']);

        // Verify only one request was made (no retry)
        Http::assertSentCount(1);
    }
}
