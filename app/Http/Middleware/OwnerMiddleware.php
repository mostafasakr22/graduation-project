<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerMiddleware
{
     public function handle($request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 'owner') {
            return response()->json(['message' => 'Access Denied. Only owner can perform this action'], 403);
        }

        return $next($request);
    }
}
