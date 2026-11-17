<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TrackBookHits
{
    /**
     * Handle an incoming request and record book asset/query hit telemetry when debugging.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        $isDebug = (bool) config('app.debug');

        if ($isDebug) {
            DB::enableQueryLog();
        }

        $response = $next($request);

        if ($isDebug && ! $request->expectsJson() && method_exists($response, 'getContent')) {
            $content = (string) $response->getContent();

            preg_match_all('/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', $content, $cssMatches);
            preg_match_all('/<script\b[^>]*>/i', $content, $jsMatches);
            preg_match_all('/<img\b[^>]*>/i', $content, $imgMatches);

            $cssCount = count($cssMatches[0]);
            $jsCount = count($jsMatches[0]);
            $imgCount = count($imgMatches[0]);
            $queryCount = count(DB::getQueryLog());

            // Total estimated hits: 1 (the book view itself) + stylesheets + scripts + <img> tags
            $estimatedHits = 1 + $cssCount + $jsCount + $imgCount;

            session()->flash('debug_hits', [
                'queries'    => $queryCount,
                'css'        => $cssCount,
                'js'         => $jsCount,
                'images'     => $imgCount,
                'total_hits' => $estimatedHits,
            ]);
        }

        return $response;
    }
}