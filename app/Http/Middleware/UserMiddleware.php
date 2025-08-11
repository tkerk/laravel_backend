<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('sanctum')->user() && auth('sanctum')->user()->role === 'user') {
            return $next($request);
        }
        return response()->json(['error' => 'No autorizado'], 403);
    }
}