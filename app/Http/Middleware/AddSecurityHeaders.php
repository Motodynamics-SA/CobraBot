<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders {
    /**
     * Handle an incoming request by adding security headers.
     */
    public function handle(Request $request, Closure $next): Response {
        $response = $next($request);

        /**
         * Content-Security-Policy (CSP)
         * Prevents XSS and data injection by defining approved sources of content.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP
         *
         * - 'self': allows content only from same origin
         * - 'unsafe-inline' and 'unsafe-eval': needed for some legacy scripts (use with caution!)
         * - Specific domains: permit loading scripts, images, and styles from trusted CDNs
         */
        $response->headers->set(
            'Content-Security-Policy',
            // Specifies the default policy for fetching any type of resource
            // 'self' allows resources only from the same origin
            "default-src 'self'; " .

            // Controls which scripts can be executed
            // 'unsafe-inline' allows inline <script> tags
            // 'unsafe-eval' allows JavaScript eval() usage (should be avoided when possible)
            // External scripts are allowed only from specific trusted CDNs
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://cdn.jsdelivr.net; " .

            // Controls which stylesheets can be loaded
            // 'unsafe-inline' needed if inline <style> or style="" attributes are used
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .

            // Controls allowed image sources
            // 'data:' allows base64-encoded images
            // Used here for Google Analytics tracking beacons
            "img-src 'self' data: https://www.google-analytics.com; " .

            // Controls allowed font sources
            // Only self-hosted fonts are permitted
            "font-src 'self'; " .

            // Controls which URLs the page can fetch data from (AJAX, WebSocket, etc.)
            "connect-src 'self' https://www.google-analytics.com; " .

            // Restricts which parent frames can embed this page via <frame>, <iframe>, <object>, <embed>, or <applet>
            // 'self' ensures only same-origin sites can embed this page
            // Helps prevent clickjacking attacks
            // Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/frame-ancestors
            "frame-ancestors 'self'; " .

            // Limits the valid targets for form submissions
            // 'self' ensures forms can only be submitted to the same origin
            // Prevents data exfiltration to third-party sites
            // Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/form-action
            "form-action 'self'; " .

            // Defines valid sources for workers and shared workers (e.g. web workers, service workers)
            // 'blob:' allows creating workers from blob URLs
            // 'self' ensures only scripts from same origin can spawn workers
            // Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/worker-src
            "worker-src 'self' blob:;"
        );

        /**
         * X-Content-Type-Options
         * Prevents browsers from MIME-sniffing a response away from the declared content-type.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Content-Type-Options
         */
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        /**
         * X-Frame-Options
         * Prevents the site from being embedded in iframes to stop clickjacking attacks.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
         *
         * SAMEORIGIN: only allow framing by pages on the same origin
         */
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        /**
         * Referrer-Policy
         * Controls how much referrer information is included with requests.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy
         *
         * strict-origin-when-cross-origin: full referrer for same-origin, origin-only for cross-origin HTTPS
         */
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        /**
         * Strict-Transport-Security (HSTS)
         * Forces HTTPS by telling browsers to only use secure connections for future requests.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security
         *
         * max-age=31536000: enforce for 1 year
         * includeSubDomains: apply to subdomains
         * preload: allow inclusion in browser preload list (https://hstspreload.org/)
         */
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        /**
         * X-Permitted-Cross-Domain-Policies
         * Used by Adobe Flash and Acrobat to restrict cross-domain data loading.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Permitted-Cross-Domain-Policies
         *
         * none: disallow all cross-domain policy files
         */
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        /**
         * Permissions-Policy (previously Feature-Policy)
         * Controls which browser features and APIs can be used in the document or iframe.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Permissions-Policy
         *
         * Disabling unnecessary features enhances privacy and reduces potential abuse.
         */
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(), interest-cohort=()'
        );

        /**
         * Optional: Clear-Site-Data
         * Clears cookies, cache, and storage when logging out — useful for preventing data leakage.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Clear-Site-Data
         *
         * Uncomment if using logout routes and want to enforce fresh sessions.
         */
        if ($request->routeIs('logout') || $request->routeIs('api.logout')) {
            $response->headers->set('Clear-Site-Data', '"cache", "cookies", "storage", "executionContexts"');
        }

        /**
         * Cross-Origin Embedder Policy (COEP)
         * Protects against cross-origin attacks for embedded content (e.g., <iframe>, <script>).
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Embedder-Policy
         */
        $response->headers->set('Cross-Origin-Embedder-Policy', 'require-corp');

        /**
         * Cross-Origin Opener Policy (COOP)
         * Ensures that documents from different origins cannot share the same browsing context.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Opener-Policy
         */
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        /**
         * Cross-Origin Resource Policy (CORP)
         * Restricts which sites can load your resources like images, scripts, etc.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Resource-Policy
         */
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        /**
         * Cache-Control and related headers
         * Disables caching for authenticated users to prevent sensitive data from being stored.
         * Ref: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control
         */
        // if (auth()->check()) {
        //     $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        //     $response->headers->set('Pragma', 'no-cache'); // legacy support
        //     $response->headers->set('Expires', 'Mon, 01 Jan 1990 00:00:00 GMT'); // legacy support
        // }

        return $response;
    }
}
