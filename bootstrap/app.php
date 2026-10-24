<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The app sits behind Cloudflare, which terminates TLS. Trust its forwarded
        // headers so route()/url() build https:// links (canonical URLs, sitemap, OG tags).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->prepend(\App\Http\Middleware\RestrictAdminHost::class);
        $middleware->append(\App\Http\Middleware\CheckMaintenanceMode::class);

        // Apply the 'api' rate limiter (RouteServiceProvider) to every API route.
        $middleware->throttleApi();
        
        // Register admin middleware aliases
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'super_admin' => \App\Http\Middleware\IsSuperAdmin::class,
            'admin' => \App\Http\Middleware\IsAdmin::class,
            // Allow viewers to access read-only admin pages
            'viewer.readonly' => \App\Http\Middleware\ViewerReadOnly::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
