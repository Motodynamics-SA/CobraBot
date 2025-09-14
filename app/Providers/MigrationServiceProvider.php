<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class MigrationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->extend('migration.repository', function ($repository, $app) {
            // Get the migration table name correctly
            $table = $app['config']->get('database.migrations', 'migrations');
            
            // If it's an array (new Laravel format), get the table name from it
            if (is_array($table)) {
                $table = $table['table'] ?? 'migrations';
            }
            
            // Add prefix to migration table name
            $prefix = $app['config']->get('database.connections.sqlsrv.prefix', '');
            $prefixedTable = $prefix . $table;
            
            return new DatabaseMigrationRepository($app['db'], $prefixedTable);
        });
    }
}
