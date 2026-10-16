<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackPageHits
{
    public function handle(Request $request, Closure $next)
    {
        if (!defined('LARAVEL_START')) {
            define('LARAVEL_START', microtime(true));
        }

        // CRITICAL: Explicitly enable the query log so DB queries are recorded
        DB::enableQueryLog();

        $response = $next($request);

        if (config('app.debug') && method_exists($response, 'getContent')) {
            $content = $response->getContent();

            // Use regex to match stylesheet links, script tags, and <img> tags
            preg_match_all('/<link\b[^>]*rel=["\']stylesheet["\'][^>]*>/i', $content, $cssMatches);
            preg_match_all('/<script\b[^>]*>/i', $content, $jsMatches);
            preg_match_all('/<img\b[^>]*>/i', $content, $imgMatches);

            $cssCount = count($cssMatches[0]);
            $jsCount = count($jsMatches[0]);
            $imgCount = count($imgMatches[0]);

            $queries = DB::getQueryLog();
            $queryCount = count($queries);

            // Total estimated hits: 1 (the page itself) + stylesheets + scripts + <img> tags
            $estimatedHits = 1 + $cssCount + $jsCount + $imgCount;

            session()->flash('debug_hits', [
                'queries' => $queryCount,
                'css' => $cssCount,
                'js' => $jsCount,
                'images' => $imgCount,
                'total_hits' => $estimatedHits
            ]);
        }

        return $response;
    }
}