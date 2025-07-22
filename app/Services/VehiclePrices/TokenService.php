<?php

namespace App\Services\VehiclePrices;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TokenService {
    private const CACHE_KEY = 'vehicle_prices_access_token';

    private string $clientId;

    private string $clientSecret;

    private string $tokenUrl;

    private int $timeout;

    private int $cacheTtl;

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
     */
    public function getAccessToken(): ?string {
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
     */
    private function fetchNewToken(): ?string {
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

                return null;
            }

            $accessToken = $response->json('access_token');
            if (! $accessToken) {
                Log::error('No access token in response', [
                    'response' => $response->json(),
                ]);

                return null;
            }

            // Cache the token
            Cache::put(self::CACHE_KEY, $accessToken, $this->cacheTtl);

            Log::info('Successfully fetched and cached new access token');

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Exception while fetching access token', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
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
     * @return array<string, mixed>|null
     */
    public function makeAuthenticatedRequest(string $url, array $data = [], string $method = 'POST'): ?array {
        $maxRetries = 2;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            $accessToken = $this->getAccessToken();

            if (! $accessToken) {
                Log::error('Failed to get access token for authenticated request');

                return null;
            }

            try {
                $response = Http::withToken($accessToken)
                    ->timeout($this->timeout)
                    ->$method($url, $data);

                if ($response->ok()) {
                    return $response->json();
                }

                // If we get a 401, the token might be expired
                if ($response->status() === 401 && $attempt < $maxRetries) {
                    Log::info('Received 401, clearing cached token and retrying');
                    $this->clearCachedToken();

                    continue;
                }

                // For other errors, log and return null
                Log::error('API request failed', [
                    'url' => $url,
                    'method' => $method,
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'attempt' => $attempt + 1,
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('Exception during authenticated request', [
                    'url' => $url,
                    'method' => $method,
                    'message' => $e->getMessage(),
                    'attempt' => $attempt + 1,
                ]);

                if ($attempt < $maxRetries) {
                    // Try clearing cache and retrying
                    $this->clearCachedToken();

                    continue;
                }

                return null;
            }
        }

        return null;
    }
}
