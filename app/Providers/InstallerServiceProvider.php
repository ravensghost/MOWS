<?php

namespace App\Providers;

use App\Http\Middleware\EnsureSystemInstalled;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class InstallerServiceProvider extends ServiceProvider
{
    /**
     * Register installer bindings.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap the First-Run Web Setup Wizard and installation lock checks.
     */
    public function boot(Router $router, Kernel $kernel): void
    {
        $router->aliasMiddleware('ensure.installed', EnsureSystemInstalled::class);

        // Load setup wizard routes if routes/setup.php exists on disk
        $setupRoutesPath = base_path('routes/setup.php');
        if (is_file($setupRoutesPath)) {
            Route::middleware('web')->group($setupRoutesPath);
        }

        // Do not enforce web redirect lock during CLI Artisan execution or PHPUnit runs
        if ($this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        $router->pushMiddlewareToGroup('web', EnsureSystemInstalled::class);
    }

    /**
     * Check whether the MOWS Engine installation lock file exists.
     */
    public static function isInstalled(): bool
    {
        return is_file(storage_path('framework/installed.lock'));
    }
}