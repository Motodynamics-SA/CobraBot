<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionsEnum: string {
    case VIEW_USERS = 'view users';

    case CREATE_USERS = 'create users';

    case UPDATE_USERS = 'update users';

    case DELETE_USERS = 'delete users';

    case RESTORE_USERS = 'restore users';

    public function label(): string {
        return match ($this) {
            self::VIEW_USERS => 'View Users',
            self::CREATE_USERS => 'Create Users',
            self::UPDATE_USERS => 'Update Users',
            self::DELETE_USERS => 'Delete Users',
            self::RESTORE_USERS => 'Restore Users',
        };
    }
}
