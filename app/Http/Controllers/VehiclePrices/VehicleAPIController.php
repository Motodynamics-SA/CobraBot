<?php

declare(strict_types=1);

namespace App\Http\Controllers\VehiclePrices;

use App\Exceptions\VehiclePrices\APIRequestException;
use App\Exceptions\VehiclePrices\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Models\VehiclePrice;
use App\Services\VehiclePrices\VehiclePricesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleAPIController extends Controller {
    public function __construct(
        private readonly VehiclePricesService $vehiclePricesService
    ) {}

    public function fetchPrices(Request $request): JsonResponse {
        Log::info('Fetch Prices Request: ' . json_encode($request->all()));
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode((string) $validated['data'], true);

        // Validate the required fields in the analyzed data
        $requiredFields = ['location_id', 'location_level', 'steer_from', 'steer_to'];
        foreach ($requiredFields as $requiredField) {
            if (! isset($inputData[$requiredField])) {
                return response()->json([
                    'error' => 'Missing required field: ' . $requiredField,
                ], 400);
            }
        }

        try {
            // Use the token service to make authenticated request
            $pricesUrl = config('services.vehicle_prices_api.base_url') . '/GetSteerings';
            $response = $this->vehiclePricesService->makeAuthenticatedRequest($pricesUrl, $inputData);

            return response()->json([
                'prices' => $response,
            ]);
        } catch (AuthenticationException $e) {
            Log::error('Authentication failed for vehicle prices API', [
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json([
                'error' => 'Authentication failed with external API',
            ], 401);
        } catch (APIRequestException $e) {
            Log::error('API request failed for vehicle prices', [
                'message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus(),
                'context' => $e->getContext(),
            ]);

            $statusCode = $e->getHttpStatus() >= 400 && $e->getHttpStatus() < 600
                ? $e->getHttpStatus()
                : 500;

            return response()->json([
                'error' => 'Failed to fetch prices from external API',
            ], $statusCode);
        }
    }

    public function publishPrices(Request $request): JsonResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode((string) $validated['data'], true);

        Log::info('Publish Prices Input Data: ' . json_encode($inputData));

        // Validate that steerings array exists and is not empty
        if (! isset($inputData['steerings']) || ! is_array($inputData['steerings']) || empty($inputData['steerings'])) {
            return response()->json([
                'error' => 'Missing or empty steerings array in payload',
            ], 400);
        }

        try {
            // Use the token service to make authenticated POST request
            $publishUrl = config('services.vehicle_prices_api.base_url') . '/PublishSteerings';
            $response = $this->vehiclePricesService->makeAuthenticatedRequest($publishUrl, $inputData, 'POST');

            Log::info('Publish Prices Response: ' . json_encode($response));

            // Check if we also have price data to store
            $priceDataStored = null;
            if (isset($inputData['price_data']) && is_array($inputData['price_data']) && (isset($inputData['price_data']) && $inputData['price_data'] !== [])) {
                $priceDataStored = $this->storePriceData($inputData['price_data']);
            }

            $responseData = [
                'message' => 'Prices published successfully',
                'response' => $response,
            ];

            if ($priceDataStored !== null) {
                $responseData['price_data_stored'] = $priceDataStored;
            }

            return response()->json($responseData);
        } catch (AuthenticationException $e) {
            Log::error('Authentication failed for vehicle prices API', [
                'message' => $e->getMessage(),
                'context' => $e->getContext(),
            ]);

            return response()->json([
                'error' => 'Authentication failed with external API',
            ], 401);
        } catch (APIRequestException $e) {
            Log::error('API request failed for vehicle prices publishing', [
                'message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus(),
                'context' => $e->getContext(),
            ]);

            $statusCode = $e->getHttpStatus() >= 400 && $e->getHttpStatus() < 600
                ? $e->getHttpStatus()
                : 500;

            return response()->json([
                'error' => 'Failed to publish prices to external API',
                'exception' => [
                    'message' => $e->getMessage(),
                    'http_status' => $e->getHttpStatus(),
                    'context' => $e->getContext(),
                ],
            ], $statusCode);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $priceData
     *
     * @return array{stored_count: int, total_count: int, errors: array<int, string>}
     */
    private function storePriceData(array $priceData): array {
        $storedCount = 0;
        $errors = [];

        foreach ($priceData as $index => $priceRecord) {
            try {
                // Validate required fields
                $requiredFields = ['yielding_date', 'car_group', 'type', 'start_date', 'end_date', 'yield', 'yield_code', 'price', 'pool'];
                foreach ($requiredFields as $requiredField) {
                    if (! isset($priceRecord[$requiredField])) {
                        $errors[] = sprintf("Record %s: Missing required field '%s'", $index, $requiredField);

                        continue 2; // Skip this record
                    }
                }

                // Convert price string to decimal (handle comma as decimal separator)
                $priceValue = str_replace(',', '.', $priceRecord['price']);
                $priceValue = (float) $priceValue;

                VehiclePrice::create([
                    'yielding_date' => $priceRecord['yielding_date'],
                    'car_group' => $priceRecord['car_group'],
                    'type' => $priceRecord['type'],
                    'start_date' => $priceRecord['start_date'],
                    'end_date' => $priceRecord['end_date'],
                    'yield' => $priceRecord['yield'],
                    'yield_code' => $priceRecord['yield_code'],
                    'price' => $priceValue,
                    'pool' => $priceRecord['pool'],
                ]);

                ++$storedCount;
            } catch (\Exception $e) {
                $errors[] = sprintf('Record %s: ', $index) . $e->getMessage();
                Log::error('Failed to store price record ' . $index, [
                    'record' => $priceRecord,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($errors !== []) {
            Log::warning('Some price records failed to store', [
                'stored_count' => $storedCount,
                'total_count' => count($priceData),
                'errors' => $errors,
            ]);
        }

        return [
            'stored_count' => $storedCount,
            'total_count' => count($priceData),
            'errors' => $errors,
        ];
    }
}
