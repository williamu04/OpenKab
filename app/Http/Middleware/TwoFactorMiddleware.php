<?php

namespace App\Http\Middleware;

use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    protected $twoFactorService;

    public function __construct(TwoFactorService $twoFactorService)
    {
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Jika user belum login, lanjutkan
        if (!Auth::check()) {
            return $next($request);
        }

        // Jika user sudah melewati verifikasi 2FA, lanjutkan
        if (session('2fa_verified')) {
            return $next($request);
        }

        // Jika user tidak memiliki 2FA aktif, lanjutkan
        if (!$this->twoFactorService->hasTwoFactorEnabled(Auth::user())) {
            return $next($request);
        }

        // Jika request adalah untuk logout atau halaman 2FA, lanjutkan
        if ($request->routeIs('logout') || $request->routeIs('2fa.*')) {
            return $next($request);
        }

        // Redirect ke halaman verifikasi 2FA
        return redirect()->route('2fa.challenge');
    }
}