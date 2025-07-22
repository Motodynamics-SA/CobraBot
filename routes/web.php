<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRestoreController;
use App\Http\Controllers\VehiclePrices\DataEntryController;
use App\Http\Controllers\VehiclePrices\VehicleAPIController;
use App\Http\Controllers\VehiclePrices\VehiclePricesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(route('dashboard'))->withHeaders([
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0',
    ]);
})->name('home');

Route::middleware(['auth'])->group(function (): void {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('price-updater/data-entry', [DataEntryController::class, 'index'])->name('price-updater.data-entry.index');
    Route::post('price-updater/data-entry', [DataEntryController::class, 'store'])->name('price-updater.data-entry.store');
    Route::post('price-updater/fetch-prices', [VehicleAPIController::class, 'fetchPrices'])->name('price-updater.fetch-prices');
    Route::post('price-updater/publish-prices', [VehicleAPIController::class, 'publishPrices'])->name('price-updater.publish-prices');
    Route::get('price-updater/prices', [VehiclePricesController::class, 'index'])->name('price-updater.prices.index');

    Route::resource('users', UserController::class);

    Route::put('/users/{user}/restore', UserRestoreController::class)->name('users.restore')->withTrashed();
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
