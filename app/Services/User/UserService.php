<?php

namespace App\Services\User;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Support\Collection;

class UserService {
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User {
        $role = $data['role'] ?? RolesEnum::REGISTERED_USER->value;
        unset($data['role']);

        $user = User::create($data);
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User {
        $role = $data['role'] ?? $user->roles->first()?->name;
        unset($data['role']);

        $user->update($data);
        $user->syncRoles([$role]);

        return $user;
    }

    public function delete(User $user): ?bool {
        return $user->delete();
    }

    public function restore(User $user): User {
        $user->restore();

        return $user;
    }

    public function findByEmail(string $email): ?User {
        return User::where('email', $email)->first();
    }

    /**
     * Get users based on filters
     *
     * @return Collection<int, User>
     */
    public function getUsers(
        ?string $search = null,
    ): Collection {
        return User::query()
            ->when($search, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withTrashed()
            ->with('roles')
            ->get();
    }

    /**
     * Get the roles for the form.
     *
     * @return Collection<int, array{name: string, label: string}>
     *
     * @phpstan-return Collection<int, array{name: string, label: string}>
     */
    public function getRolesForForm(): Collection {
        $roles = collect(RolesEnum::cases());
        /** @var User $user */
        $user = auth()->user();

        // If user is not admin, further filter available roles
        if (! $user->hasRole(RolesEnum::ADMINISTRATOR->value)) {
            $roles = $roles->filter(
                fn (RolesEnum $role) => in_array($role->value, [
                    RolesEnum::USER_MANAGER->value,
                ])
            );
        }

        return $roles->map(function (RolesEnum $role) {
            return [
                'name' => $role->value,
                'label' => 'roles.' . $role->value,
            ];
        })->values();
    }
}
