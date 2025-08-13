<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

if (! function_exists('registerApiRoutes')) {
    function registerApiRoutes(): void {
        Route::middleware(['auth:sanctum'])->group(function (): void {
            Route::get('/user/info', [UserController::class, 'userInfo']);
        });
    }
}

Route::prefix('v1')->group(function (): void {
    // Only apply api.restrict middleware in production
    if (app()->environment('production')) {
        Route::middleware(['api.restrict'])->group(function (): void {
            registerApiRoutes();
        });
    } else {
        registerApiRoutes();
    }
});
