<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Models\User;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller {
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function index(): Response|RedirectResponse {
        /** @var User $user */
        $user = Auth::user();

        if ($user->hasRole(RolesEnum::REGISTERED_USER->value)) {
            return Inertia::render('dashboard-simple');
        }

        return Inertia::render('dashboard', [
            'stats' => $this->dashboardService->getStats(),
        ]);
    }
}
