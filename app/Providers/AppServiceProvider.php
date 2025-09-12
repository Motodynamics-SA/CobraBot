<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\RolesEnum;
use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\Provider;

class AppServiceProvider extends ServiceProvider {
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        User::class => UserPolicy::class,
    ];

    public function register(): void {
        $makeRepo = function (Application $application): DatabaseMigrationRepository {
            /** @var DatabaseManager $databaseManager */
            $databaseManager = $application->make(DatabaseManager::class);
            $default = (string) $application->make('config')->get('database.default', 'mysql');

            if ($default === 'sqlsrv') {
                // Azure SQL: use no-prefix connection + schema-qualified table
                $repo = new DatabaseMigrationRepository($databaseManager, 'cobrabot.migrations');
                $repo->setSource('sqlsrv_noprefix');

                return $repo;
            }

            // Dev/CI/etc.
            $repo = new DatabaseMigrationRepository($databaseManager, 'migrations');
            $repo->setSource($default);

            return $repo;
        };

        // IMPORTANT: extend the existing bindings instead of bind()
        $this->app->extend(MigrationRepositoryInterface::class, fn ($_, Application $application): DatabaseMigrationRepository => $makeRepo($application));

        $this->app->extend('migration.repository', fn ($_, Application $application): DatabaseMigrationRepository => $makeRepo($application));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        Vite::prefetch(concurrency: 3);

        $this->configureSecureUrls();

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        // NOSONAR
        Gate::before(fn ($user, $ability): ?true => $user->hasRole(RolesEnum::ADMINISTRATOR) ? true : null);

        // Uncomment the following line to enable strict mode for Eloquent models
        /**
         * Behind the scenes, here's what the shouldBeStrict method does:
         *
         * static::preventLazyLoading($shouldBeStrict);
         * static::preventSilentlyDiscardingAttributes($shouldBeStrict);
         * static::preventAccessingMissingAttributes($shouldBeStrict);
         *
         * The preventLazyLoading method will throw an error if you attempt to read model relationships without having eager loaded them.
         * The preventSilentlyDiscardingAttributes method will throw an error if you attempt to set an attribute that doesn't exist on the model.
         * The preventAccessingMissingAttributes method will throw an error if you attempt to read an attribute that doesn't exist on the model.
         */
        Model::shouldBeStrict();

        // Uncomment the following line to disable mass assignment protection for Eloquent models.
        /**
         * This will allow you to set any attribute on a model using the fill method,
         * without having to define a $fillable or $guarded property on the model.
         */
        // NOSONAR
        // Model::unguard();

        // Uncomment the following line to enable the prohibitDestructiveCommands method for the DB facade.
        /**
         * This will prevent destructive commands (like DROP TABLE) from being executed in production.
         */
        DB::prohibitDestructiveCommands(app()->isProduction());

        // Uncomment the following line to enable the use of CarbonImmutable for date attributes.
        /**
         * This will make all date attributes return instances of CarbonImmutable instead of Carbon.
         * This is useful if you want to prevent date attributes from being modified accidentally.
         *
         * By default, Laravel uses Carbon for date attributes.
         * This means that when you modify a date attribute, the original model will be modified as well.
         * Example:
         * $date = now();
         * $future = $date->addHours(2); // $date will be modified as well
         *
         * If you use CarbonImmutable, the original model will not be modified.
         * Example:
         * $date = now();
         * $future = $date->addHours(2); // $date will not be modified
         */
        Date::use(CarbonImmutable::class);

        Event::listen(function (SocialiteWasCalled $socialiteWasCalled): void {
            $socialiteWasCalled->extendSocialite('microsoft', Provider::class);
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }

    protected function configureSecureUrls(): void {
        // Determine if HTTPS should be enforced
        $enforceHttps = $this->app->environment(['production', 'staging'])
            && ! $this->app->runningUnitTests();

        // Force HTTPS for all generated URLs
        URL::forceHttps($enforceHttps);

        // Ensure proper server variable is set
        if ($enforceHttps) {
            $request = app('request');
            $request->server->set('HTTPS', 'on');
        }

        /**
         * The security headers are added in the AddSecurityHeaders middleware
         *
         * @see app/Http/Middleware/AddSecurityHeaders.php
         * @see bootstrap/app.php
         */
    }
}
