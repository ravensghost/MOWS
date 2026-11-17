<?php

use App\Http\Middleware\ApplySemanticLocale;
use App\Http\Middleware\CheckBannedIpOrEmail;
use App\Http\Middleware\EnsureSystemInstalled;
use App\Http\Middleware\TrackBookHits;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            if (is_file(base_path('routes/setup.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/setup.php'));
            }

            if (is_file(base_path('routes/api.php'))) {
                Route::prefix('api')
                    ->middleware('api')
                    ->group(base_path('routes/api.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register the canonical middleware pipeline globally for all web requests
        $middleware->web(append: [
            EnsureSystemInstalled::class,
            ApplySemanticLocale::class,
            TrackBookHits::class,
        ]);

        // Register route-level middleware aliases
        $middleware->alias([
            'installed'  => EnsureSystemInstalled::class,
            'not.banned' => CheckBannedIpOrEmail::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();