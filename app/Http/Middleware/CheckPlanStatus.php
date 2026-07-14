<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanStatus
{
    /** Silently revert a lapsed Pro subscription to the free plan before the request proceeds. */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->downgradeIfExpired();
        }

        return $next($request);
    }
}
