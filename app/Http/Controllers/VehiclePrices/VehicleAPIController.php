<?php

namespace App\Http\Controllers\VehiclePrices;

use App\Http\Controllers\Controller;
use App\Services\VehiclePrices\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleAPIController extends Controller {
    public function __construct(
        private TokenService $tokenService
    ) {}

    public function fetchPrices(Request $request): JsonResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode($validated['data'], true);

        Log::info('Input Data: ' . json_encode($inputData));

        // Validate the required fields in the analyzed data
        $requiredFields = ['location_id', 'location_level', 'steer_from', 'steer_to', 'limit', 'offset'];
        foreach ($requiredFields as $field) {
            if (! isset($inputData[$field])) {
                return response()->json([
                    'error' => "Missing required field: {$field}",
                ], 400);
            }
        }

        // Use the token service to make authenticated request
        $pricesUrl = config('services.vehicle_prices_api.base_url') . '/GetSteerings';
        $response = $this->tokenService->makeAuthenticatedRequest($pricesUrl, $inputData);

        if ($response === null) {
            return response()->json([
                'error' => 'Failed to fetch prices from external API',
            ], 500);
        }

        return response()->json([
            'prices' => $response,
        ]);
    }

    public function publishPrices(Request $request): JsonResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        $inputData = json_decode($validated['data'], true);

        Log::info('Publish Prices Input Data: ' . json_encode($inputData));

        // Use the token service to make authenticated POST request
        $publishUrl = config('services.vehicle_prices_api.base_url') . '/PublishSteerings';
        $response = $this->tokenService->makeAuthenticatedRequest($publishUrl, $inputData, 'POST');

        Log::info('Publish Prices Response: ' . json_encode($response));

        if ($response === null) {
            return response()->json([
                'error' => 'Failed to publish prices to external API',
            ], 500);
        }

        return response()->json([
            'message' => 'Prices published successfully',
            'response' => $response,
        ]);
    }
}
