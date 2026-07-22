<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = $request->boolean('remember');

        $user = User::where('email', $credentials['email'])->first();

        if ($user && empty($user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'This account uses Google login. Please continue with Google or reset your password.',
                ]);
        }

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Those credentials do not match our records.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(RouteServiceProvider::HOME)
            ->with('status', 'Welcome back to Amadara UNO.');
    }
}