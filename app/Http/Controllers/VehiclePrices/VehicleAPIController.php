<?php

namespace App\Http\Controllers\VehiclePrices;

use App\Exceptions\VehiclePrices\APIRequestException;
use App\Exceptions\VehiclePrices\AuthenticationException;
use App\Http\Controllers\Controller;
use App\Services\VehiclePrices\VehiclePricesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleAPIController extends Controller {
    public function __construct(
        private VehiclePricesService $vehiclePricesService
    ) {}

    public function fetchPrices(Request $request): JsonResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode($validated['data'], true);

        // Validate the required fields in the analyzed data
        $requiredFields = ['location_id', 'location_level', 'steer_from', 'steer_to'];
        foreach ($requiredFields as $field) {
            if (! isset($inputData[$field])) {
                return response()->json([
                    'error' => "Missing required field: {$field}",
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

        $inputData = json_decode($validated['data'], true);

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

            return response()->json([
                'message' => 'Prices published successfully',
                'response' => $response,
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
}
