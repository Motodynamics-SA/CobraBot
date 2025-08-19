<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $password = config('app.default_user_password_for_seeder');

        echo 'Password: ' . $password . "\n";

        // Create or update the admin user
        $admin = User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Admin User',
                'email' => 'cobrabot_admin@sixt.gr',
                'password' => Hash::make($password),
            ]
        );
        $admin->assignRole(RolesEnum::ADMINISTRATOR->value);

        // Create or update the user manager
        $userManager = User::updateOrCreate(
            ['id' => 2],
            [
                'name' => 'User Manager',
                'email' => 'cobrabot_user_manager@sixt.gr',
                'password' => Hash::make($password),
            ]
        );
        $userManager->assignRole(RolesEnum::USER_MANAGER->value);

        // Create or update the registered user
        $registeredUser = User::updateOrCreate(
            ['id' => 3],
            [
                'name' => 'Registered User',
                'email' => 'cobrabot_registered_user@sixt.gr',
                'password' => Hash::make($password),
            ]
        );
        $registeredUser->assignRole(RolesEnum::REGISTERED_USER->value);
    }
}
