<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\OtpLoginRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class OtpLoginController extends Controller
{
    protected $otpService;
    protected $twoFactorService;

    public function __construct(OtpService $otpService, TwoFactorService $twoFactorService)
    {
        $this->middleware('guest')->except('logout');
        $this->otpService = $otpService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Tampilkan form OTP login
     */
    public function showLoginForm()
    {
        return view('auth.otp-login');
    }

    /**
     * Kirim OTP untuk login
     */
    public function sendOtp(OtpLoginRequest $request)
    {
        // Rate limiting
        $key = 'otp-login:' . $request->ip();
        $maxAttempts = env('OTP_VERIFY_MAX_ATTEMPTS', 5); // Default to 5 if not set in .env
        $decaySeconds = env('OTP_VERIFY_DECAY_SECONDS', 300); // Default to 300 seconds if not set in .env

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.'
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);

        // Cari user berdasarkan identifier
        $user = User::where('otp_enabled', true)
            ->where(function($query) use ($request) {
                $query->where('otp_identifier', $request->identifier)
                    ->orWhere('email', $request->identifier)
                      ->orWhere('username', $request->identifier);
            })
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan atau OTP tidak aktif'
            ], 404);
        }

        // Tentukan channel dan identifier
        $channels = $user->getOtpChannels();
        $channel = $channels[0] ?? 'email'; // Ambil channel pertama
        
        $identifier = $user->otp_identifier;

        $result = $this->otpService->generateAndSend($user->id, $channel, $identifier);

        if ($result['success']) {
            // Simpan user ID di session untuk verifikasi
            $request->session()->put('otp_login_user_id', $user->id);
            $request->session()->put('otp_login_channel', $channel);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Verifikasi OTP dan login
     */
    public function verifyOtp(OtpVerifyRequest $request)
    {

        $userId = $request->session()->get('otp_login_user_id');
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak ditemukan. Silakan mulai dari awal.'
            ], 400);
        }

        // Rate limiting untuk verifikasi
        $key = 'otp-verify-login:' . $request->ip();
        $maxAttempts = env('OTP_VERIFY_MAX_ATTEMPTS', 5); // Default to 5 if not set in .env
        $decaySeconds = env('OTP_VERIFY_DECAY_SECONDS', 300); // Default to 300 seconds if not set in .env

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'message' => 'Terlalu banyak percobaan verifikasi. Coba lagi dalam ' . RateLimiter::availableIn($key) . ' detik.'
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);

        $result = $this->otpService->verify($userId, $request->otp);

        if ($result['success']) {
            $user = User::find($userId);
            
            // Login user
            Auth::login($user, true);
            
            // Clear session
            $request->session()->forget(['otp_login_user_id', 'otp_login_channel']);
            RateLimiter::clear($key);
            
            // Check if user has 2FA enabled
            if ($this->twoFactorService->hasTwoFactorEnabled($user)) {
                // Clear 2FA verification session to require new verification
                session()->forget('2fa_verified');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil. Silakan verifikasi 2FA',
                    'redirect' => route('2fa.challenge')
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'redirect' => \App\Providers\RouteServiceProvider::HOME
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 400);
    }

    /**
     * Kirim ulang OTP
     */
    public function resendOtp(Request $request)
    {
        $userId = $request->session()->get('otp_login_user_id');
        $channel = $request->session()->get('otp_login_channel');
        
        if (!$userId || !$channel) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi login tidak ditemukan.'
            ], 400);
        }

        // Rate limiting untuk resend
        $key = 'otp-resend-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 2)) {
            return response()->json([
                'success' => false,
                'message' => 'Tunggu ' . RateLimiter::availableIn($key) . ' detik sebelum mengirim ulang.'
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $user = User::find($userId);
        $identifier = $user->otp_identifier;

        $result = $this->otpService->generateAndSend($userId, $channel, $identifier);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
