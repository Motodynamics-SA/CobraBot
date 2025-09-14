<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class MigrationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Only apply in production
        if (!app()->isProduction()) {
            return;
        }

        $this->app->extend('migration.repository', function ($repository, $app) {
            $table = $app['config']->get('database.migrations', 'migrations');
            
            if (is_array($table)) {
                $table = $table['table'] ?? 'migrations';
            }
            
            $prefix = $app['config']->get('database.connections.sqlsrv.prefix', '');
            $prefixedTable = $prefix . $table;
            
            return new DatabaseMigrationRepository($app['db'], $prefixedTable);
        });
    }
}
