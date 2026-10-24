<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if maintenance mode is enabled in cache
        if (Cache::get('maintenance_mode')) {
            // Allow admin, login, logout, and internal routes
            if ($request->getHost() === config('app.admin_domain') || $request->is('admin*') || $request->is('login') || $request->is('logout') || $request->is('sanctum/*')) {
                return $next($request);
            }

            // Allow all API routes (PayPal callbacks, order cancellation, etc. MUST work during maintenance)
            if ($request->is('api/*')) {
                return $next($request);
            }

            // Allow checkout success page so customers who paid can see their confirmation
            if ($request->is('checkout/success/*')) {
                return $next($request);
            }

            // Allow authenticated users who are admins (double check for safety)
            if (Auth::check() && Auth::user()?->is_admin) {
                return $next($request);
            }

            // Return 503 Maintenance View
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}