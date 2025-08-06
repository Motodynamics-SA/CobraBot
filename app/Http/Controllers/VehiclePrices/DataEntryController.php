<?php

namespace App\Http\Controllers\VehiclePrices;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataEntryController extends Controller {
    public function index(Request $request): Response {
        return Inertia::render('price-updater/data-entry');
    }

    public function store(Request $request): RedirectResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);

        // Ensure session is properly established
        if (! $request->session()->isStarted()) {
            $request->session()->start();
        }

        // Regenerate CSRF token to ensure it's fresh
        $request->session()->regenerateToken();

        $request->session()->put('vehicle_data_entry', $validated['data']);

        // Force a full page redirect to ensure proper session establishment
        return redirect()->route('price-updater.prices.index')->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
