<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
 class APIAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // check if user is authenticated
        if(Auth::guard('api')->check()) {
            return $next($request);
        } else {
            // return error response
            $json = [
                'status' => [
                    "status" => "false",
                    "message" => "Unauthorized",
                    "http_code" => 401
                ],
                "data" => null
                
            ];
            return response()->json($json, 401);
        }
    }
}
