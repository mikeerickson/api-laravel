<?php

namespace App\Http\Middleware;

use Closure;
use Carbon\Carbon;
use App\Models\APIToken;
use Illuminate\Support\Facades\DB;

class ApiRateLimit
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
//        if(($request->server('REQUEST_METHOD')) === 'GET') {
//            return $next($request);
//        }

        $token = $request->header('API-Token')
            ? $request->header('API-Token')
            : $request->query('token');

        // a little backdoor magic
        if($this->isTokenOverride($token)) {
            return $next($request);
        }

        // check if a trial token has been supplied, if so we can continue without further validation
        if($this->isTrialToken($token)) {
            return $next($request);
        }

        if(is_null($token)) {
            return $next($request);
        }

        $result = DB::table('api_tokens')
            ->where('token','=', $token)->get();
        if(sizeof($result) === 0) {
            return response([
                "error"   => 401,
                "message" => "Invalid Token",
                "token"   => $token
            ], 401);
        }

        if($result[0]->active) {
            if($result[0]->expires < Carbon::now()) {
                $tokenExpirationDate = $result[0]->expires;
                return response([
                    "error"   => 401,
                    "message" => "API Token Expired ${tokenExpirationDate}",
                    "token"   => $token
                ], 401);
            }
        } else {
            return response([
                "error"   => 401,
                "message" => "Account Disabled",
                "token"   => $token
            ], 401);
        }
        return $next($request);
    }

    private function isTokenOverride($token) {
        return (env('API_ADMIN_TOKEN') === $token);
    }

    private function isTrialToken($token) {
        return (env('API_TRIAL_TOKEN') === $token);
    }
}
