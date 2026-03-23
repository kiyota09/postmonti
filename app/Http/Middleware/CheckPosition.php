<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPosition
{
    /**
     * Handle an incoming request.
     *
     * @param  string  ...$positions  <-- The "..." allows multiple arguments
     */
    public function handle(Request $request, Closure $next, ...$positions): Response
    {
        // 1. Check if user is logged in
        if (! $request->user()) {
            abort(403, 'Unauthorized.');
        }

        // 2. Normalize all allowed positions to lowercase
        $allowedPositions = array_map('strtolower', $positions);

        // 3. Check if the user's position is in the allowed list
        // Assuming your DB stores position as 'manager', 'staff', etc.
        if (! in_array(strtolower($request->user()->position), $allowedPositions)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
