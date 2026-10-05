<?php

namespace Tests\Feature;

use App\Models\User;
use App\Http\Middleware\CheckPermission;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminPermissionTest extends TestCase
{
    protected function tearDown(): void
    {
        Auth::clearResolvedInstance('auth');

        parent::tearDown();
    }

    public function test_every_admin_mutation_requires_permission_middleware(): void
    {
        $unprotected = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'admin/'))
            ->filter(fn ($route) => array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']))
            ->reject(fn ($route) => collect($route->gatherMiddleware())
                ->contains(fn ($middleware) => str_starts_with($middleware, 'permission:')));

        $this->assertCount(0, $unprotected);
    }

    public function test_viewer_read_only_requests_still_require_the_route_permission(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturnTrue();
        $guard->shouldReceive('user')->once()->andReturn($this->userWithPermission(false));
        Auth::swap($guard);

        $request = Request::create('/admin/finance', 'GET');
        $request->attributes->set('viewer_readonly', true);

        try {
            app(CheckPermission::class)->handle($request, fn () => response(), 'view_finance');
            $this->fail('Expected the finance permission check to deny the viewer.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_permission_middleware_allows_users_with_the_required_permission(): void
    {
        $guard = Mockery::mock(Guard::class);
        $guard->shouldReceive('check')->once()->andReturnTrue();
        $guard->shouldReceive('user')->once()->andReturn($this->userWithPermission(true));
        Auth::swap($guard);

        $response = app(CheckPermission::class)->handle(
            Request::create('/admin/products', 'POST'),
            fn () => response('', 204),
            'manage_products'
        );

        $this->assertSame(204, $response->getStatusCode());
    }

    private function userWithPermission(bool $hasPermission): User
    {
        $user = new class extends User {
            public bool $hasTestPermission = false;

            public function hasAnyPermission(array $permissions): bool
            {
                return $this->hasTestPermission;
            }
        };

        $user->forceFill(['is_active' => true]);
        $user->hasTestPermission = $hasPermission;

        return $user;
    }
}
