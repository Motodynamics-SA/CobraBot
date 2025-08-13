<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class UserController extends BaseApiController {
    /**
     * Get user information and permissions.
     *
     *
     * @throws AuthorizationException
     */
    public function userInfo(): JsonResponse {
        /** @var User $user */
        $user = auth()->user();

        $permissions = [];

        if ($user->hasRole([RolesEnum::ADMINISTRATOR->value, RolesEnum::USER_MANAGER->value])) {
            $permissions['dashboard'] = true;
        }

        return $this->success([
            'user' => [
                'name' => $user->name,
            ],
            'permissions' => $permissions,
        ]);
    }
}
