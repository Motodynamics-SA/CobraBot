<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to restrict API access based on allowed IP addresses.
 */
class RestrictApiAccess {
    public function handle(Request $request, Closure $next): Response {
        $origin = $request->header('origin');
        $referer = $request->header('referer');

        $appUrl = rtrim((string) config('app.url'), '/');
        $allowedOrigins = [$appUrl];

        $originAllowed = $origin && in_array($origin, $allowedOrigins);
        $refererAllowed = $referer && str_starts_with($referer, $appUrl);

        if (! $originAllowed && ! $refererAllowed) {
            Log::warning('Blocked API access', [
                'origin' => $origin,
                'referer' => $referer,
            ]);

            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $next($request);
    }
}
