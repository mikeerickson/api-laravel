<?php

namespace App\Http\Middleware;

use Closure;
use Log;

class ApiLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::info('API Request: ' .$request->getRequestUri());
        return $next($request);
    }
}
