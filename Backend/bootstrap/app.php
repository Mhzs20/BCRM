<?php

use App\Http\Middleware\SuperAdminMiddleware;
use App\Http\Middleware\CheckPackageFeature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CorsMiddleware;

return Application::configure(basePath: dirname(__DIR__))
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
