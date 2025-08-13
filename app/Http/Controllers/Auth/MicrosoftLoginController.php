<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MicrosoftLoginController extends Controller {
    public function redirectToProvider(): RedirectResponse {
        return Socialite::driver('microsoft')->redirect();
    }

    public function handleProviderCallback(): RedirectResponse {
        $microsoftUser = Socialite::driver('microsoft')->user();

        $user = User::updateOrCreate([
            'email' => $microsoftUser->getEmail(),
        ], [
            'name' => $microsoftUser->getName(),
            'provider_id' => $microsoftUser->getId(),
            'provider' => 'microsoft',
        ]);

        Auth::login($user);

        return redirect()->route('price-updater.data-entry.index');
    }
}
