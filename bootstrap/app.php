<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Only load .env if APP_ENV isn't already set
if (getenv('APP_ENV') !== 'testing') {
    // Step 1: Load the main .env file
    $dotenv = Dotenv\Dotenv::createMutable(dirname(__DIR__));
    $dotenv->load();

    // Step 2: Only load additional .env file if APP_DEVELOPMENT_ENV is set
    // and if app is in local mode
    $developmentEnv = Env::get('APP_DEVELOPMENT_ENV');

    if ($developmentEnv && (Env::get('APP_ENV') === 'local')) {
        $additionalEnvFile = ".env.{$developmentEnv}";
        $additionalEnvPath = dirname(__DIR__) . "/{$additionalEnvFile}";

        if (file_exists($additionalEnvPath)) {
            $dotenv = Dotenv\Dotenv::createMutable(dirname(__DIR__), $additionalEnvFile);
            $dotenv->load();
        }
    }
}

// Step 3: Configure the application
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append([
            \Illuminate\Http\Middleware\TrustProxies::class,
        ]);

        // Web middleware
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $aliases = [];

        // Production-only middleware
        if (Env::get('APP_ENV') === 'production') {
            $middleware->web(append: [
                AddSecurityHeaders::class,
            ]);

            $aliases['api.restrict'] = \App\Http\Middleware\RestrictApiAccess::class;

            $middleware->group('api', [
                \App\Http\Middleware\RestrictApiAccess::class,
            ]);
        }

        $middleware->alias($aliases);

        $middleware->api([
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'error' => 'Resource not found',
                ], 404);
            }
            // Don't return anything to let Laravel handle it
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Unauthenticated.'], 401);
                }
            }
            // Don't return anything to let Laravel handle it
        });

        $exceptions->render(function (\Exception $e, Request $request) {
            if ($request->is('api/*')) {
                $status = $e->getCode() ?: 500;

                return response()->json([
                    'error' => $e->getMessage() ?: 'Server Error',
                ], $status >= 400 && $status < 600 ? $status : 500);
            }
            // Don't return anything to let Laravel handle it
        });
    })
    ->create();

// Set the storage path for Azure
if (env('APP_STORAGE_PATH')) {
    $app->useStoragePath(env('APP_STORAGE_PATH'));
}

return $app;
