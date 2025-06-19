<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role The required role ('admin' or 'user')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Check if user is authenticated AND has the required role
        if (!Auth::check() || Auth::user()->role !== $role) {
            // If not, abort with a 404.
            abort(404);
        }

        // If they have the role, allow the request to proceed.
        return $next($request);
    }
}