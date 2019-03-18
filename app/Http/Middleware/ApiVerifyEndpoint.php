<?php

namespace App\Http\Middleware;

use Closure;

class ApiVerifyEndpoint
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
        $supportedEndpoints = [
            'batting',
            'managers',
            'parks',
            'pitching',
            'players',
            'teams',
            'teamsfranchises',
        ];
        $uri   = substr($request->getPathInfo(), 1);
        $parts = explode('/', $uri);

        // check if this is an API request, if not pass through
        if (!in_array('api', $parts)) {
            return $next($request);
        }

        if (sizeof($parts) >= 3) {
            $endpoint = $parts[2];
            if (!in_array($endpoint, $supportedEndpoints)) {
                return response([
                    'error'             => 501,
                    'message'           => 'Unsupported Endpoint',
                    'requestedEndpoint' => $endpoint ?: 'API Access Requires Endpoint'
                ], 501);
            }
        } else {
            return response([
                'error'   => 501,
                'message' => 'API Access Requires Endpoint',
            ], 501);
        }

        return $next($request);
    }
}
