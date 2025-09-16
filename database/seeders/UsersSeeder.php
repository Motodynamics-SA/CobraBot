<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('app.default_user_password_for_seeder');

        $users = [
            ['id' => 1, 'name' => 'Admin User',        'email' => 'cobrabot_admin@sixt.gr',         'role' => RolesEnum::ADMINISTRATOR->value],
            ['id' => 2, 'name' => 'User Manager',      'email' => 'cobrabot_user_manager@sixt.gr',  'role' => RolesEnum::USER_MANAGER->value],
            ['id' => 3, 'name' => 'Registered User',   'email' => 'cobrabot_registered_user@sixt.gr','role' => RolesEnum::REGISTERED_USER->value],
        ];

        $conn   = DB::connection();                   // uses current env's default connection
        $driver = $conn->getDriverName();             // 'sqlsrv', 'mysql', 'sqlite', 'pgsql', etc.
        $table  = $driver === 'sqlsrv' ? 'cobrabot.users' : 'users';
        $now    = Carbon::now();

        if ($driver === 'sqlsrv') {
            // SQL Server: IDENTITY_INSERT is session-scoped; do all writes in one transaction + ON/OFF once.
            $conn->transaction(function () use ($table, $users, $password, $now, $conn) {
                DB::statement("SET IDENTITY_INSERT {$table} ON");

                foreach ($users as $u) {
                    $row = [
                        'id'         => $u['id'],
                        'name'       => $u['name'],
                        'email'      => $u['email'],
                        'password'   => Hash::make($password),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // Update then insert (avoids connection hops of updateOrCreate during IDENTITY_INSERT)
                    $updated = DB::table($table)->where('id', $u['id'])->update(Arr::except($row, ['id']));
                    if ($updated === 0) {
                        DB::table($table)->insert($row);
                    }
                }

                DB::statement("SET IDENTITY_INSERT {$table} OFF");
            });
        } else {
            // MySQL / SQLite / Postgres: explicit id inserts are fine; use upsert for idempotency.
            $rows = array_map(function ($u) use ($password, $now) {
                return [
                    'id'         => $u['id'],
                    'name'       => $u['name'],
                    'email'      => $u['email'],
                    'password'   => Hash::make($password),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $users);

            // Upsert by primary key id (update name/email/password/updated_at if exists)
            DB::table($table)->upsert(
                $rows,
                ['id'],
                ['name', 'email', 'password', 'updated_at']
            );
        }

        // Assign roles (done AFTER the data writes in both branches)
        foreach ($users as $u) {
            // Use the same connection as above to avoid cross-connection lookups
            $user = \App\Models\User::on($conn->getName())->find($u['id']);
            if ($user) {
                $user->syncRoles([$u['role']]);
            }
        }
    }
}
