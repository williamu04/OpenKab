<?php

namespace Tests\Unit;

use App\Http\Middleware\TwoFactorMiddleware;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class TwoFactorMiddlewareTest extends TestCase
{
    protected $twoFactorService;
    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->twoFactorService = Mockery::mock(TwoFactorService::class);
        $this->middleware = new TwoFactorMiddleware($this->twoFactorService);
        
        // Register test routes
        Route::get('/test-route', function () {
            return response('Test content');
        })->name('test.route');
        
        Route::get('/2fa-test', function () {
            return response('2FA test');
        })->name('2fa.test');
        
        Route::post('/logout', function () {
            return response('Logout');
        })->name('logout');
        
        Route::get('/2fa-challenge', function () {
            return response('2FA Challenge');
        })->name('2fa.challenge');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_request_when_user_is_not_authenticated()
    {
        Auth::shouldReceive('check')->once()->andReturnFalse();
        
        $request = Request::create('/test-route', 'GET');
        $next = function ($req) {
            return new Response('Passed');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Passed', $response->getContent());
    }   

    /** @test */
    public function it_allows_request_when_user_does_not_have_2fa_enabled()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnFalse();
        
        $request = Request::create('/test-route', 'GET');
        
        $next = function ($req) {
            return new Response('Passed');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Passed', $response->getContent());
    }

    /** @test */
    public function it_redirects_to_2fa_challenge_when_user_has_2fa_enabled_and_not_verified()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/test-route', 'GET');
        
        $next = function ($req) {
            return new Response('Should not reach here');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('2fa.challenge'), $response->headers->get('Location'));
    }

    /** @test */
    public function it_allows_logout_request_even_with_2fa_enabled()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/logout', 'POST');
        $request->setRouteResolver(function () use ($request) {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('named')->with('logout')->andReturnTrue();
            return $route;
        });
        
        $next = function ($req) {
            return new Response('Logout allowed');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Logout allowed', $response->getContent());
    }

    /** @test */
    public function it_allows_any_2fa_route_when_2fa_enabled()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/2fa-test', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('named')->with('2fa.*')->andReturnTrue();
            $route->shouldReceive('named')->with('logout')->andReturnFalse();
            return $route;
        });
        
        $next = function ($req) {
            return new Response('2FA route allowed');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('2FA route allowed', $response->getContent());
    }

    /** @test */
    public function it_redirects_to_challenge_page_when_accessing_protected_route()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/dashboard', 'GET');
        
        $next = function ($req) {
            return new Response('Should not reach here');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('2fa.challenge'), $response->headers->get('Location'));
    }

    /** @test */
    public function it_allows_challenge_page_access()
    {
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/2fa-challenge', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = Mockery::mock(\Illuminate\Routing\Route::class);
            $route->shouldReceive('named')->with('2fa.*')->andReturnTrue();
            $route->shouldReceive('named')->with('logout')->andReturnFalse();
            return $route;
        });
        
        $next = function ($req) {
            return new Response('Challenge page allowed');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals('Challenge page allowed', $response->getContent());
    }

    /** @test */
    public function it_handles_middleware_with_proper_priority()
    {
        // Test that middleware checks in the correct order:
        // 1. Is user authenticated?
        // 2. Is 2FA already verified?
        // 3. Does user have 2FA enabled?
        // 4. Is this a 2FA or logout route?
        // 5. Redirect to challenge
        
        $user = Mockery::mock(User::class);
        Auth::shouldReceive('check')->once()->andReturnTrue();
        Auth::shouldReceive('user')->once()->andReturn($user);
        
        $this->twoFactorService
            ->shouldReceive('hasTwoFactorEnabled')
            ->once()
            ->with($user)
            ->andReturnTrue();
        
        $request = Request::create('/protected-route', 'GET');
        
        $next = function ($req) {
            return new Response('Should not reach here');
        };
        
        $response = $this->middleware->handle($request, $next);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals(route('2fa.challenge'), $response->headers->get('Location'));
    }
}