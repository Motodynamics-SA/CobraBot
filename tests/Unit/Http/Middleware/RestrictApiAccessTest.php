<?php

declare(strict_types=1);

use App\Http\Middleware\RestrictApiAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

test('allows access for allowed origin header', function (): void {
    // Arrange
    Config::set('app.url', 'https://my-frontend.com');

    $request = Request::create('/api/test', 'GET', [], [], [], [
        'HTTP_ORIGIN' => 'https://my-frontend.com',
    ]);

    $middleware = new \App\Http\Middleware\RestrictApiAccess;
    $next = fn ($request): \Symfony\Component\HttpFoundation\Response => new Response('OK');

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($response->getContent())->toBe('OK');
    expect($response->getStatusCode())->toBe(200);
});

test('denies access for unauthorized IP', function (): void {
    // Arrange
    Config::set('api.allowed_ips', '127.0.0.1,192.168.1.1');
    $request = Request::create('/api/test', 'GET');
    $request->server->set('REMOTE_ADDR', '10.0.0.1');

    $middleware = new RestrictApiAccess;
    $next = (fn ($request): \Symfony\Component\HttpFoundation\Response => new Response('OK'));

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($response->getStatusCode())->toBe(403);
    expect(json_decode($response->getContent(), true))->toBe([
        'error' => 'Unauthorized',
    ]);
});

test('handles empty allowed IPs config', function (): void {
    // Arrange
    Config::set('api.allowed_ips', '');
    $request = Request::create('/api/test', 'GET');
    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    $middleware = new RestrictApiAccess;
    $next = (fn ($request): \Symfony\Component\HttpFoundation\Response => new Response('OK'));

    // Act
    $response = $middleware->handle($request, $next);

    // Assert
    expect($response->getStatusCode())->toBe(403);
    expect(json_decode($response->getContent(), true))->toBe([
        'error' => 'Unauthorized',
    ]);
});
