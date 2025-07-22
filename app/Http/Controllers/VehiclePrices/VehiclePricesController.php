<?php

namespace App\Http\Controllers\VehiclePrices;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class VehiclePricesController extends Controller {
    public function index(Request $request): RedirectResponse|InertiaResponse {
        $data = $request->session()->get('vehicle_data_entry');
        if (! $data) {
            return redirect()->route('price-updater.data-entry.index')->with('error', 'No data provided.');
        }

        return Inertia::render('price-updater/prices', [
            'entryData' => $data,
        ]);
    }
}
