<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\RolesEnum;
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
        $email = $microsoftUser->getEmail();

        // Check if email domain is allowed
        $allowedDomains = ['motodynamics.gr', 'sixt.gr', 'scify.org'];
        $atPosition = strrchr((string) $email, '@');

        if ($atPosition === false) {
            return redirect()->route('login')->withErrors([
                'email' => 'Invalid email format.',
            ]);
        }

        $emailDomain = substr($atPosition, 1);

        if (! in_array($emailDomain, $allowedDomains)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Access denied. Only @motodynamics.gr and @sixt.gr email addresses are allowed.',
            ]);
        }

        $user = User::updateOrCreate([
            'email' => $email,
        ], [
            'name' => $microsoftUser->getName(),
            'provider_id' => $microsoftUser->getId(),
            'provider' => 'microsoft',
        ]);

        // assign the user manager role to the user
        // if it is the first time the user logs in
        if ($user->roles->isEmpty()) {
            $user->assignRole(RolesEnum::USER_MANAGER->value);
        }

        Auth::login($user);

        return redirect()->route('price-updater.data-entry.index');
    }
}
