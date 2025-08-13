<?php

declare(strict_types=1);

namespace App\Enums;

enum RolesEnum: string {
    case ADMINISTRATOR = 'admin';

    case USER_MANAGER = 'user-manager';

    case REGISTERED_USER = 'registered-user';

    // extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
    public function label(): string {
        return match ($this) {
            self::ADMINISTRATOR => 'Administrator',
            self::USER_MANAGER => 'User Manager',
            self::REGISTERED_USER => 'Registered User',
        };
    }
}
