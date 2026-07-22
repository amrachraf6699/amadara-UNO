<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    private const STATE_COOKIE = 'google_oauth_state';

    public function redirect()
    {
        $state = Str::random(40);
        $stateSignature = hash_hmac('sha256', $state, (string) config('app.key'));

        // Keep state outside the application session. This is important on hosts
        // where the session storage or session cookie is not shared reliably
        // between the redirect and the OAuth callback.
        $response = Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();

        return $response->withCookie($this->stateCookie($stateSignature));
    }

    public function callback(Request $request)
    {
        $state = (string) $request->query('state', '');
        $expectedSignature = (string) $request->cookie(self::STATE_COOKIE, '');

        if (! $state || ! $expectedSignature || ! hash_equals(
            $expectedSignature,
            hash_hmac('sha256', $state, (string) config('app.key'))
        )) {
            return redirect()->route('login')
                ->withCookie(Cookie::forget(self::STATE_COOKIE))
                ->with('error', 'Your Google sign-in session expired. Please try again.');
        }

        // State has been validated above, so Socialite does not need the
        // session-backed validator that fails on some production hosts.
        $socialiteUser = Socialite::driver('google')->stateless()->user();

        $user = User::query()
            ->where('provider', 'google')
            ->where('provider_id', $socialiteUser->getId())
            ->first();

        // Do not link an OAuth login to an account registered with a password.
        if (! $user && $socialiteUser->getEmail()) {
            $existingUser = User::where('email', $socialiteUser->getEmail())->first();

            if ($existingUser) {
                return redirect()->route('login')
                    ->withCookie(Cookie::forget(self::STATE_COOKIE))
                    ->with('error', 'You are not registered that way.');
            }
        }

        $user ??= new User;
        $user->fill([
            'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'Google User',
            'email' => $socialiteUser->getEmail(),
            'provider' => 'google',
            'provider_id' => $socialiteUser->getId(),
            'avatar' => $socialiteUser->getAvatar(),
        ]);

        if ($user->email && ! $user->email_verified_at) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME)
            ->withCookie(Cookie::forget(self::STATE_COOKIE))
            ->with('status', 'Welcome back to Amadara UNO.');
    }

    private function stateCookie(string $signature)
    {
        $secure = app()->environment('production') || request()->isSecure();

        return cookie(
            self::STATE_COOKIE,
            $signature,
            10,
            '/',
            config('session.domain'),
            $secure,
            true,
            false,
            'lax'
        );
    }
}
