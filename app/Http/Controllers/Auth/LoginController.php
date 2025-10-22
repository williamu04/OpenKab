<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\OtpService;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    protected $decayMinutes = 3;

    protected $maxAttempts = 5;
    
    protected $otpService;
    protected $twoFactorService;

    /**
     * Create a new controller instance.
     */
    public function __construct(OtpService $otpService, TwoFactorService $twoFactorService)
    {
        $this->middleware('guest')->except('logout');
        $this->otpService = $otpService;
        $this->twoFactorService = $twoFactorService;
        $this->username = $this->findUsername();
    }
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Login username to be used by the controller.
     *
     * @var string
     */
    protected $username;


    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function findUsername()
    {
        $login = request()->input('login');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        request()->merge([$fieldType => $login]);

        return $fieldType;
    }

    /**
     * Get username property.
     *
     * @return string
     */
    public function username()
    {
        return $this->username;
    }

    /**
     * Attempt to log the user into the application.
     *
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $successLogin = $this->guard()->attempt(
            $this->credentials($request), $request->boolean('remember')
        );

        if ($successLogin) {
            try {
                $request->validate(['password' => ['required', Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                ],
                ]);
                session(['weak_password' => false]);
            } catch (ValidationException  $th) {
                session(['weak_password' => true]);

                return redirect(route('password.change'))->with('success-login', 'Ganti password dengan yang lebih kuat');
            }            
        }

        return $successLogin;
    }
    
    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();
        
        $this->clearLoginAttempts($request);
        
        // Check if user has 2FA enabled
        $user = $this->guard()->user();
        if ($this->twoFactorService->hasTwoFactorEnabled($user)) {
            session()->forget('2fa_verified');
            // If 2FA is enabled, redirect to 2FA challenge
            return redirect()->route('2fa.challenge');
        }
        
        // If weak password, redirect to password change
        if (session('weak_password')) {
            return redirect(route('password.change'))->with('success-login', 'Ganti password dengan yang lebih kuat');
        }

        return redirect()->intended($this->redirectPath());
    }
}
