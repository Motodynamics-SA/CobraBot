<?php

namespace App\Http\Controllers\VehiclePrices;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataEntryController extends Controller {
    public function index(): Response {
        return Inertia::render('price-updater/data-entry');
    }

    public function store(Request $request): RedirectResponse {
        $validated = $request->validate([
            'data' => 'required|json',
        ]);
        $request->session()->put('vehicle_data_entry', $validated['data']);

        return redirect()->route('price-updater.prices.index');
    }
}
