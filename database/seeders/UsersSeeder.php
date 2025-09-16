<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $password = config('app.default_user_password_for_seeder');

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'cobrabot_admin@sixt.gr',
                'role' => RolesEnum::ADMINISTRATOR->value,
            ],
            [
                'name' => 'User Manager',
                'email' => 'cobrabot_user_manager@sixt.gr',
                'role' => RolesEnum::USER_MANAGER->value,
            ],
            [
                'name' => 'Registered User',
                'email' => 'cobrabot_registered_user@sixt.gr',
                'role' => RolesEnum::REGISTERED_USER->value,
            ],
        ];

        foreach ($users as $u) {
            DB::transaction(function () use ($u, $password) {

                $user = User::updateOrCreate(
                    ['email' => $u['email']],
                    [
                        'name' => $u['name'],
                        'email' => $u['email'],
                        'password' => Hash::make($password),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]
                );
                $user->assignRole($u['role']);
            });
        }
    }
}
