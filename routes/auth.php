<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MicrosoftLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login/microsoft', [MicrosoftLoginController::class, 'redirectToProvider'])->name('login.microsoft');
    Route::get('/login/microsoft/callback', [MicrosoftLoginController::class, 'handleProviderCallback'])->name('login.microsoft.callback');
});
