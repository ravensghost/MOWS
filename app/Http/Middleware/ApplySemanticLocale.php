<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySemanticLocale
{
    /**
     * Handle an incoming request and apply the resolved semantic lexicon locale.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (function_exists('resolve_lexicon_locale')) {
            app()->setLocale(resolve_lexicon_locale());
        }

        return $response = $next($request);
    }
}