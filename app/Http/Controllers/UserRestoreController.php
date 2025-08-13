<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;

class UserRestoreController extends Controller {
    use AuthorizesRequests;

    public function __construct(
        private UserService $userService
    ) {}

    public function __invoke(User $user): RedirectResponse {
        $this->authorize('restore', $user);

        $this->userService->restore($user);

        return redirect()->route('users.index')
            ->with('success', __('users.messages.restored'));
    }
}
