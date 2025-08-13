<?php

declare(strict_types=1);

namespace App\Services\VehiclePrices;

use App\Exceptions\VehiclePrices\APIRequestException;
use App\Exceptions\VehiclePrices\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VehiclePricesService {
    private const CACHE_KEY = 'vehicle_prices_access_token';

    private readonly string $clientId;

    private readonly string $clientSecret;

    private readonly string $tokenUrl;

    private readonly int $timeout;

    private readonly int $cacheTtl;

    /**
     * @return void
     */
    public function __construct() {
        $config = config('services.vehicle_prices_api');
        $this->clientId = $config['client_id'] ?? '';
        $this->clientSecret = $config['client_secret'] ?? '';
        $this->tokenUrl = $config['token_url'] ?? '';
        $this->timeout = $config['timeout'] ?? 30;
        $this->cacheTtl = $config['cache_ttl'] ?? 3500;
    }

    /**
     * Get a valid access token, fetching a new one if necessary
     *
     * @throws AuthenticationException
     */
    public function getAccessToken(): string {
        // Try to get token from cache first
        $cachedToken = Cache::get(self::CACHE_KEY);
        if ($cachedToken) {
            return $cachedToken;
        }

        // If no cached token, fetch a new one
        return $this->fetchNewToken();
    }

    /**
     * Fetch a new access token from the API
     *
     * @throws AuthenticationException
     */
    private function fetchNewToken(): string {
        try {
            $response = Http::asForm()->post($this->tokenUrl, [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

            if (! $response->ok()) {
                Log::error('Failed to fetch access token', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                throw new AuthenticationException(
                    'Failed to fetch access token from API',
                    $response->status(),
                    null,
                    [
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]
                );
            }

            $accessToken = $response->json('access_token');
            if (! $accessToken) {
                Log::error('No access token in response', [
                    'response' => $response->json(),
                ]);

                throw new AuthenticationException(
                    'No access token received from API',
                    0,
                    null,
                    ['response' => $response->json()]
                );
            }

            // Cache the token
            Cache::put(self::CACHE_KEY, $accessToken, $this->cacheTtl);

            Log::info('Successfully fetched and cached new access token');

            return $accessToken;
        } catch (AuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Exception while fetching access token', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new AuthenticationException(
                'Unexpected error while fetching access token',
                0,
                $e,
                ['original_message' => $e->getMessage()]
            );
        }
    }

    /**
     * Clear the cached token
     */
    public function clearCachedToken(): void {
        Cache::forget(self::CACHE_KEY);
        Log::info('Cleared cached access token');
    }

    /**
     * Make an authenticated HTTP request with automatic token refresh
     *
     * @param  array<string, mixed>  $data
     *
     * @return array<string, mixed>
     *
     * @throws AuthenticationException
     * @throws APIRequestException
     */
    public function makeAuthenticatedRequest(string $url, array $data = [], string $method = 'POST'): array {
        $maxRetries = 1; // Only retry once for token expiration

        for ($attempt = 0; $attempt <= $maxRetries; ++$attempt) {
            try {
                $accessToken = $this->getAccessToken();

                // Save token to file for debugging
                $tokenData = [
                    'timestamp' => now()->toISOString(),
                    'token_length' => strlen($accessToken),
                    'token' => $accessToken,
                ];
                file_put_contents(
                    storage_path('logs/access_token_debug.log'),
                    json_encode($tokenData, JSON_PRETTY_PRINT) . "\n",
                    FILE_APPEND | LOCK_EX
                );

                $response = Http::withToken($accessToken)
                    ->timeout($this->timeout)
                    ->$method($url, $data);

                if ($response->ok()) {
                    return $response->json();
                }

                // If we get a 401, the token might be expired - retry once
                if ($response->status() === 401 && $attempt < $maxRetries) {
                    Log::info('Received 401, clearing cached token and retrying');
                    $this->clearCachedToken();

                    continue;
                }

                // For all other errors, throw exception immediately
                Log::error('API request failed', [
                    'url' => $url,
                    'method' => $method,
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'attempt' => $attempt + 1,
                ]);

                throw new APIRequestException(
                    'API request failed',
                    0,
                    null,
                    [
                        'url' => $url,
                        'method' => $method,
                        'status' => $response->status(),
                        'response' => $response->json(),
                        'attempt' => $attempt + 1,
                    ],
                    $response->status()
                );

            } catch (AuthenticationException $e) {
                // Authentication errors should be thrown immediately
                throw $e;
            } catch (APIRequestException $e) {
                // Re-throw API request exceptions
                throw $e;
            } catch (\Exception $e) {
                Log::error('Unexpected exception during authenticated request', [
                    'url' => $url,
                    'method' => $method,
                    'message' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                throw new APIRequestException(
                    'Unexpected error during API request',
                    0,
                    $e,
                    [
                        'url' => $url,
                        'method' => $method,
                        'original_message' => $e->getMessage(),
                        'attempt' => $attempt + 1,
                    ]
                );
            }
        }

        // This should never be reached, but just in case
        throw new APIRequestException(
            'Failed to make authenticated request after token refresh retry',
            0,
            null,
            ['url' => $url, 'method' => $method]
        );
    }
}
