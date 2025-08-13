<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseApiController extends Controller {
    public function __construct() {
        JsonResource::withoutWrapping();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function success(array $data = [], int $status = 200): JsonResponse {
        return response()->json($data, $status);
    }

    protected function error(string $message, int $status = 400): JsonResponse {
        return response()->json([
            'error' => $message,
        ], $status);
    }
}
