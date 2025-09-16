<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\VehiclePrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Disable all middleware for tests
    $this->withoutMiddleware();

    // Set up test environment variables
    config([
        'services.vehicle_prices_api.client_id' => 'test-client-id',
        'services.vehicle_prices_api.client_secret' => 'test-client-secret',
        'services.vehicle_prices_api.token_url' => 'https://test-api.com/oauth/token',
        'services.vehicle_prices_api.base_url' => 'https://test-api.com',
        'services.vehicle_prices_api.timeout' => 30,
        'services.vehicle_prices_api.cache_ttl' => 3500,
    ]);
});

describe('fetchPrices', function (): void {
    it('validates required data field', function (): void {
        $response = $this->postJson('/price-updater/fetch-prices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    });

    it('validates data is valid JSON', function (): void {
        $response = $this->postJson('/price-updater/fetch-prices', [
            'data' => 'invalid-json',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    });

    it('validates required fields in analyzed data', function (): void {
        $response = $this->postJson('/price-updater/fetch-prices', [
            'data' => json_encode(['missing_fields' => true]),
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Missing required field: location_id']);
    });

    it('handles authentication failure gracefully', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $response = $this->postJson('/price-updater/fetch-prices', [
            'data' => json_encode([
                'location_id' => '123',
                'location_level' => 'LOCATION_LEVEL_BRANCH',
                'steer_from' => '2025-01-01 00:00',
                'steer_to' => '2025-01-31 23:59',
            ]),
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Authentication failed with external API']);
    });

    it('successfully fetches prices', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/GetSteerings' => Http::response(['steerings' => []]),
        ]);

        $response = $this->postJson('/price-updater/fetch-prices', [
            'data' => json_encode([
                'location_id' => '123',
                'location_level' => 'LOCATION_LEVEL_BRANCH',
                'steer_from' => '2025-01-01 00:00',
                'steer_to' => '2025-01-31 23:59',
            ]),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['prices']);
    });
});

describe('publishPrices', function (): void {
    it('validates required data field', function (): void {
        $response = $this->postJson('/price-updater/publish-prices', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data']);
    });

    it('validates steerings array exists and is not empty', function (): void {
        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode(['invalid' => 'data']),
        ]);

        $response->assertStatus(400)
            ->assertJson(['error' => 'Missing or empty steerings array in payload']);
    });

    it('successfully publishes steering records without price data', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/PublishSteerings' => Http::response(['success' => true]),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Prices published successfully']);
    });

    it('successfully publishes steering records and stores price data', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/PublishSteerings' => Http::response(['success' => true]),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
                'price_data' => [
                    [
                        'yielding_date' => '2025-01-01',
                        'car_group' => 'MDMR',
                        'type' => 'UDA',
                        'start_date' => '2025-01-01',
                        'end_date' => '2025-01-31',
                        'yield' => '49',
                        'yield_code' => 'P51',
                        'price' => '205.4703',
                        'pool' => '123',
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Prices published successfully'])
            ->assertJsonStructure(['price_data_stored']);

        // Verify price data was stored in database
        expect(VehiclePrice::count())->toBe(1);
        expect(VehiclePrice::first())
            ->car_group->toBe('MDMR')
            ->type->toBe('UDA')
            ->price->toBe('205.4703');
    });

    it('handles price data with comma decimal separator', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/PublishSteerings' => Http::response(['success' => true]),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
                'price_data' => [
                    [
                        'yielding_date' => '2025-01-01',
                        'car_group' => 'MDMR',
                        'type' => 'UDA',
                        'start_date' => '2025-01-01',
                        'end_date' => '2025-01-31',
                        'yield' => '49',
                        'yield_code' => 'P51',
                        'price' => '205,4703',
                        'pool' => '123',
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(200);

        // Verify price was correctly converted from comma to decimal
        expect(VehiclePrice::first()->price)->toBe('205.4703');
    });

    it('handles missing required fields in price data gracefully', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/PublishSteerings' => Http::response(['success' => true]),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
                'price_data' => [
                    [
                        'yielding_date' => '2025-01-01',
                        'car_group' => 'MDMR',
                        // Missing required fields
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['price_data_stored']);

        // Verify no records were stored due to missing fields
        expect(VehiclePrice::count())->toBe(0);
    });

    it('handles authentication failure gracefully', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Authentication failed with external API']);
    });

    it('handles API request failure gracefully', function (): void {
        Http::fake([
            'https://test-api.com/oauth/token' => Http::response(['access_token' => 'test-token']),
            'https://test-api.com/PublishSteerings' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $response = $this->postJson('/price-updater/publish-prices', [
            'data' => json_encode([
                'steerings' => [
                    [
                        'location_level' => 'LOCATION_LEVEL_BRANCH',
                        'location_id' => '123',
                        'steer_type' => 'STEER_TYPE_UDA',
                        'length_of_rent_from' => 1,
                        'length_of_rent_to' => 1,
                        'vehicle_type' => 'VEHICLE_TYPE_P',
                        'vehicle_group' => 'MDMR',
                        'yield_type' => 'TYPE_LEVEL_PLAIN',
                        'value_type' => 'VALUE_TYPE_RATE_P',
                        'value' => 49,
                        'steer_from' => '2025-01-01 00:00',
                        'steer_to' => '2025-01-31 23:59',
                        'identity' => 'franchise',
                        'channel' => 'GIVO',
                        'available_type' => 'AVAILABLE_TYPE_CONDITIONAL',
                        'remark' => 'Steering MDMR',
                        'operation' => 'UPSERT_WITH_STEER_PERIOD_SPLIT',
                    ],
                ],
            ]),
        ]);

        $response->assertStatus(500)
            ->assertJson(['error' => 'Failed to publish prices to external API']);
    });
});

describe('storePriceData private method', function (): void {
    it('stores valid price data correctly', function (): void {
        $controller = new \App\Http\Controllers\VehiclePrices\VehicleAPIController(
            app(\App\Services\VehiclePrices\VehiclePricesService::class)
        );

        $priceData = [
            [
                'yielding_date' => '2025-01-01',
                'car_group' => 'MDMR',
                'type' => 'UDA',
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'yield' => '49',
                'yield_code' => 'P51',
                'price' => '205.4703',
                'pool' => '123',
            ],
            [
                'yielding_date' => '2025-01-01',
                'car_group' => 'EDMR',
                'type' => 'PEAK',
                'start_date' => '2025-01-15',
                'end_date' => '2025-01-20',
                'yield' => '55',
                'yield_code' => 'P45',
                'price' => '275.3507',
                'pool' => '123',
            ],
        ];

        $reflection = new ReflectionClass($controller);
        $reflectionMethod = $reflection->getMethod('storePriceData');

        $result = $reflectionMethod->invoke($controller, $priceData);

        expect($result)->toBe([
            'stored_count' => 2,
            'total_count' => 2,
            'errors' => [],
        ]);

        expect(VehiclePrice::count())->toBe(2);
        expect(VehiclePrice::where('car_group', 'MDMR')->first()->type)->toBe('UDA');
        expect(VehiclePrice::where('car_group', 'EDMR')->first()->type)->toBe('PEAK');
    });

    it('handles invalid price data gracefully', function (): void {
        $controller = new \App\Http\Controllers\VehiclePrices\VehicleAPIController(
            app(\App\Services\VehiclePrices\VehiclePricesService::class)
        );

        $priceData = [
            [
                'yielding_date' => '2025-01-01',
                'car_group' => 'MDMR',
                'type' => 'UDA',
                'start_date' => '2025-01-01',
                'end_date' => '2025-01-31',
                'yield' => '49',
                'yield_code' => 'P51',
                'price' => '205.4703',
                'pool' => '123',
            ],
            [
                // Missing required fields
                'yielding_date' => '2025-01-01',
            ],
        ];

        $reflection = new ReflectionClass($controller);
        $reflectionMethod = $reflection->getMethod('storePriceData');

        $result = $reflectionMethod->invoke($controller, $priceData);

        expect($result['stored_count'])->toBe(1);
        expect($result['total_count'])->toBe(2);
        expect($result['errors'])->toHaveCount(1);
        expect($result['errors'][0])->toContain('Missing required field');

        expect(VehiclePrice::count())->toBe(1);
    });
});
