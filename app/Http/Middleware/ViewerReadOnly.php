<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ViewerReadOnly
{
    /**
     * Restrict accounts that hold only view_* permissions to read-only (GET/HEAD) admin requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to continue.');
        }

        $user = Auth::user();

        if (!$user || !$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        // Users whose permissions are all view_* are read-only, whatever their role names
        $permissions = $user->permissionNames();
        $readOnly = !$user->isSuperAdmin()
            && collect($permissions)->every(fn (string $name) => str_starts_with($name, 'view_'));

        if ($readOnly && !in_array($request->method(), ['GET', 'HEAD'])) {
            abort(403, 'Unauthorized. Your account has read-only access.');
        }

        return $next($request);
    }
}
