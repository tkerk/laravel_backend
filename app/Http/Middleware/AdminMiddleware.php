<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('sanctum')->user() && auth('sanctum')->user()->role === 'admin') {
            return $next($request);
        }
        return response()->json(['error' => 'No autorizado'], 403);
    }
}