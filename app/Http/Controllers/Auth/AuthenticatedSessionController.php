<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthenticatedSessionController extends Controller {
    /**
     * Show the login page.
     */
    public function create(Request $request): InertiaResponse|RedirectResponse {
        if (auth()->check()) {
            // User is already logged in, redirect to dashboard
            return redirect()->route(route: 'home');
        }

        return Inertia::render('auth/login', [
            'canResetPassword' => true,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     *
     *
     * @param  LoginRequest  $request  The incoming login request containing credentials
     *
     * @return RedirectResponse Returns RedirectResponse to dashboard
     *                          or an error response if authentication fails
     */
    public function store(LoginRequest $request): RedirectResponse {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->route('price-updater.data-entry.index');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
