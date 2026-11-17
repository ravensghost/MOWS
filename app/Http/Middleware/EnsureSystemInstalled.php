<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemInstalled
{
    /**
     * Redirect unconfigured installations to /setup and lock /setup with a 404 once installed.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $isInstalled = is_file(storage_path('framework/installed.lock'));
        $isSetupRoute = $request->is('setup', 'setup/*');

        if ($isInstalled) {
            if ($isSetupRoute) {
                abort(404);
            }

            return $next($request);
        }

        // Allow setup wizard routes and framework health checks while uninstalled
        if ($isSetupRoute || $request->is('up')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'   => 'error',
                'message'  => 'System installation is incomplete. Redirecting to Setup Wizard.',
                'data'     => null,
                'redirect' => url('/setup'),
            ], 503);
        }

        return redirect('/setup');
    }
}