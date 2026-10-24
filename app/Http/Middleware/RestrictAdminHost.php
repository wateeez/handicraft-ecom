<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictAdminHost
{
    /**
     * When a dedicated admin domain is configured, that host serves only the dashboard,
     * login and health check. Storefront pages 404 there so they are not duplicated, and
     * every response is marked noindex.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminDomain = config('app.admin_domain');

        if (!$adminDomain || $request->getHost() !== $adminDomain) {
            return $next($request);
        }

        if ($request->is('robots.txt')) {
            return response("User-agent: *\nDisallow: /\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        $allowed = $request->is('/', 'admin', 'admin/*', 'login', 'logout', 'up', 'storage/*');
        abort_unless($allowed, 404);

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
