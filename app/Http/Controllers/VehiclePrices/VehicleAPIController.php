<?php

declare(strict_types=1);

namespace App\Http\Controllers\VehiclePrices;

use App\Exceptions\VehiclePrices\APIRequestException;
use App\Exceptions\VehiclePrices\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Models\VehiclePrice;
use App\Services\VehiclePrices\VehiclePricesService;
use Carbon\Carbon;
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

    public function deletePrices(Request $request): JsonResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode((string) $validated['data'], true);

        // Validate that steerings array exists and is not empty
        if (! isset($inputData['steerings']) || ! is_array($inputData['steerings']) || empty($inputData['steerings'])) {
            return response()->json([
                'error' => 'Missing or empty steerings array in payload',
            ], 400);
        }

        // Check if price data is provided (optional for deletion)
        $priceData = $inputData['price_data'] ?? [];

        try {
            // Use the token service to make authenticated POST request
            $publishUrl = config('services.vehicle_prices_api.base_url') . '/PublishSteerings';
            $response = $this->vehiclePricesService->makeAuthenticatedRequest($publishUrl, $inputData, 'POST');

            Log::info('Delete Prices Response: ' . json_encode($response));

            // Delete corresponding price data from database if provided
            $priceDataDeleted = null;
            if (! empty($priceData)) {
                $priceDataDeleted = $this->deletePriceData($priceData);
            }

            $responseData = [
                'message' => 'Prices deleted successfully',
                'response' => $response,
            ];

            if ($priceDataDeleted !== null) {
                $responseData['price_data_deleted'] = $priceDataDeleted;
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
            Log::error('API request failed for vehicle prices deletion', [
                'message' => $e->getMessage(),
                'http_status' => $e->getHttpStatus(),
                'context' => $e->getContext(),
            ]);

            $statusCode = $e->getHttpStatus() >= 400 && $e->getHttpStatus() < 600
                ? $e->getHttpStatus()
                : 500;

            return response()->json([
                'error' => 'Failed to delete prices from external API',
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

                // make sure yielding date is in YYYY-MM-DD format
                $yieldingDate = Carbon::parse($priceRecord['yielding_date'])->format('Y-m-d');

                VehiclePrice::create([
                    'yielding_date' => $yieldingDate,
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

    /**
     * @param  array<int, array<string, mixed>>  $priceRecords
     *
     * @return array{deleted_count: int, total_count: int, errors: array<int, string>}
     */
    private function deletePriceData(array $priceRecords): array {
        $deletedCount = 0;
        $errors = [];

        foreach ($priceRecords as $index => $priceRecord) {
            try {
                // Extract date range from steering record
                $steerFrom = $priceRecord['steer_from'] ?? null;
                $steerTo = $priceRecord['steer_to'] ?? null;
                $carGroup = $priceRecord['car_group'] ?? null;
                $steerType = $priceRecord['steer_type'] ?? null;
                $pool = $priceRecord['pool'] ?? null;
                $yieldingDate = $priceRecord['yielding_date'] ?? null;
                $yield = $priceRecord['value'] ?? null;
                $yieldCode = $priceRecord['yield_code'] ?? null;

                if (! $steerFrom || ! $steerTo || ! $carGroup) {
                    $errors[] = sprintf('Record %s: Missing required fields for deletion', $index);

                    continue;
                }

                // Convert steering dates to Carbon date objects for proper comparison
                $startDate = Carbon::parse($steerFrom)->format('Y-m-d');
                $endDate = Carbon::parse($steerTo)->format('Y-m-d');

                // make sure yielding date is in YYYY-MM-DD format
                $yieldingDate = Carbon::parse($yieldingDate)->format('Y-m-d');

                // Map steering type to price type
                $steerType = $steerType === 'STEER_TYPE_PEAK' ? 'PEAK' : 'UDA';

                // Find and delete matching records
                $deleted = (int) VehiclePrice::where('car_group', $carGroup)
                    ->where('type', $steerType)
                    ->whereDate('start_date', $startDate)
                    ->whereDate('end_date', $endDate)
                    ->where('pool', $pool ?? '')
                    ->whereDate('yielding_date', $yieldingDate)
                    ->where('yield', $yield)
                    ->where('yield_code', $yieldCode)
                    ->delete();

                $deletedCount += $deleted;

                Log::info(sprintf(
                    "Deleted %d price records with criteria:\n" .
                    "├─ car_group: %s\n" .
                    "├─ type: %s\n" .
                    "├─ start_date: %s\n" .
                    "├─ end_date: %s\n" .
                    "├─ pool: %s\n" .
                    "├─ yielding_date: %s\n" .
                    "├─ yield: %s\n" .
                    '└─ yield_code: %s',
                    $deleted, $carGroup, $steerType, $startDate, $endDate, $pool ?? 'null', $yieldingDate, $yield ?? 'null', $yieldCode ?? 'null'
                ));
            } catch (\Exception $e) {
                $errors[] = sprintf('Record %s: ', $index) . $e->getMessage();
                Log::error('Failed to delete price records for steering record ' . $index, [
                    'record' => $priceRecord,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($errors !== []) {
            Log::warning('Some price records failed to delete', [
                'deleted_count' => $deletedCount,
                'total_count' => count($priceRecords),
                'errors' => $errors,
            ]);
        }

        return [
            'deleted_count' => $deletedCount,
            'total_count' => count($priceRecords),
            'errors' => $errors,
        ];
    }
}
