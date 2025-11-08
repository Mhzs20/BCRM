<?php

use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\CheckPackageFeature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CorsMiddleware;

/**
 * Create and configure the application instance, then explicitly bind the
 * Console Kernel contract to the app's Kernel implementation. This ensures
 * the framework resolves `Illuminate\Contracts\Console\Kernel` to
 * `App\Console\Kernel`, which is required so scheduled tasks defined in
 * `app/Console/Kernel.php` are loaded.
 */
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            CorsMiddleware::class,
        ]);
        $middleware->api(append: [
            CorsMiddleware::class,
        ]);
        $middleware->alias([
            'superadmin' => SuperAdminMiddleware::class,
            'feature' => CheckPackageFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
    })
    ->create();

// Explicitly bind the Console Kernel contract to the application's Kernel
// implementation so the framework uses our `App\Console\Kernel` class.
$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

return $app;
