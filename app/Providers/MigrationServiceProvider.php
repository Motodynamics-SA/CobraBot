<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class MigrationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->extend('migration.repository', function ($repository, $app) {
            $table = $app['config']['database.migrations'];
            
            return new DatabaseMigrationRepository(
                $app['db'], 
                'cobrabot.' . $table // Prepend the prefix manually
            );
        });
    }
}
